<?php

use App\Enums\Api\V1\SubscriptionEventType;
use App\Enums\Api\V1\SubscriptionStatus;
use App\Enums\UserRole;
use App\Jobs\SendMailJob;
use App\Jobs\Subscription\ProcessSubscriptionAutoRenewJob;
use App\Jobs\Subscription\SendSubscriptionInAppNotificationJob;
use App\Models\Payment;
use App\Models\Plan;
use App\Models\Subscription;
use App\Models\SubscriptionLog;
use App\Models\User;
use App\Services\Subscription\SubscriptionAutoRenewService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Queue;
use Laravel\Passport\ClientRepository;
use Tests\TestCase;

uses(RefreshDatabase::class);

beforeEach(function (): void {
    Queue::fake([SendMailJob::class, SendSubscriptionInAppNotificationJob::class]);

    app(ClientRepository::class)->createPersonalAccessGrantClient(
        name: 'Test Personal Access Client',
        provider: config('auth.guards.api.provider')
    );

    config([
        'services.paypal.client_id' => 'test-client',
        'services.paypal.client_secret' => 'test-secret',
        'services.paypal.mode' => 'sandbox',
        'subscription.auto_renew.max_attempts' => 3,
        'subscription.auto_renew.retry_hours' => 0,
    ]);
});

function createAutoRenewPlan(): Plan
{
    return Plan::query()->create([
        'name' => 'Professional',
        'description' => 'Test plan',
        'button_text' => 'Subscribe',
        'monthly_price' => 99,
        'yearly_price' => 990,
        'is_popular' => true,
        'status' => true,
    ]);
}

function createDuePaypalSubscription(array $overrides = []): array
{
    $manufacturer = User::factory()->create(['role' => UserRole::MANUFACTURER->value]);
    $plan = createAutoRenewPlan();

    $subscription = Subscription::query()->create(array_merge([
        'manufacturer_id' => $manufacturer->id,
        'plan_id' => $plan->id,
        'billing_interval' => 'month',
        'status' => SubscriptionStatus::ACTIVE->value,
        'starts_at' => now()->subMonth(),
        'ends_at' => now()->subHour(),
        'auto_renew' => true,
        'payment_method' => 'paypal',
        'paypal_vault_id' => 'VAULT-TEST-123',
        'paypal_payer_id' => 'PAYER-1',
        'renew_attempts' => 0,
    ], $overrides));

    return [$manufacturer, $plan, $subscription];
}

test('auto renew command queues due paypal subscriptions and skips ineligible ones', function (): void {
    Queue::fake([ProcessSubscriptionAutoRenewJob::class]);

    [, , $due] = createDuePaypalSubscription();

    createDuePaypalSubscription([
        'auto_renew' => false,
        'paypal_vault_id' => 'VAULT-CANCELLED',
    ]);

    createDuePaypalSubscription([
        'payment_method' => 'stripe',
        'paypal_vault_id' => null,
    ]);

    Artisan::call('subscriptions:auto-renew');

    Queue::assertPushed(ProcessSubscriptionAutoRenewJob::class, 2);
    Queue::assertPushed(ProcessSubscriptionAutoRenewJob::class, function (ProcessSubscriptionAutoRenewJob $job) use ($due): bool {
        return $job->subscriptionId === $due->id;
    });
});

test('paypal auto renew charges vault extends subscription and records payment', function (): void {
    Http::fake([
        'https://api-m.sandbox.paypal.com/v1/oauth2/token' => Http::response([
            'access_token' => 'test-access-token',
            'token_type' => 'Bearer',
        ]),
        'https://api-m.sandbox.paypal.com/v2/checkout/orders' => Http::response([
            'id' => 'ORDER-AUTO-RENEW-1',
            'status' => 'COMPLETED',
            'purchase_units' => [
                [
                    'amount' => [
                        'value' => '99.00',
                        'currency_code' => 'USD',
                    ],
                    'payments' => [
                        'captures' => [
                            [
                                'status' => 'COMPLETED',
                                'amount' => [
                                    'value' => '99.00',
                                    'currency_code' => 'USD',
                                ],
                            ],
                        ],
                    ],
                ],
            ],
        ]),
    ]);

    [$manufacturer, $plan, $subscription] = createDuePaypalSubscription();
    $service = app(SubscriptionAutoRenewService::class);

    expect($service->process($subscription))->toBe('renewed');

    $subscription->refresh();

    expect($subscription->status)->toBe(SubscriptionStatus::ACTIVE)
        ->and($subscription->ends_at?->isFuture())->toBeTrue()
        ->and($subscription->renew_attempts)->toBe(0)
        ->and($subscription->last_renewed_at)->not->toBeNull()
        ->and($subscription->source?->value ?? $subscription->source)->toBe('purchase');

    expect(Payment::query()->where('payment_id', 'ORDER-AUTO-RENEW-1')->exists())->toBeTrue();

    expect(SubscriptionLog::query()
        ->where('manufacturer_id', $manufacturer->id)
        ->where('event_type', SubscriptionEventType::SUBSCRIPTION_RENEWED->value)
        ->exists())->toBeTrue();

    Queue::assertPushed(SendMailJob::class);
});

test('stripe auto renew is skipped for later implementation', function (): void {
    [, , $subscription] = createDuePaypalSubscription([
        'payment_method' => 'stripe',
        'paypal_vault_id' => null,
    ]);

    $service = app(SubscriptionAutoRenewService::class);

    expect($service->process($subscription))->toBe('skipped_stripe');
    expect(Payment::query()->count())->toBe(0);
    expect($subscription->fresh()->ends_at?->isPast())->toBeTrue();
});

test('cancelled auto renew is not charged', function (): void {
    [, , $subscription] = createDuePaypalSubscription([
        'auto_renew' => false,
    ]);

    $service = app(SubscriptionAutoRenewService::class);

    expect($service->process($subscription))->toBe('ineligible');
    expect(Payment::query()->count())->toBe(0);
});

test('failed paypal renew increments attempts and disables after max', function (): void {
    config(['subscription.auto_renew.max_attempts' => 2]);

    Http::fake([
        'https://api-m.sandbox.paypal.com/v1/oauth2/token' => Http::response([
            'access_token' => 'test-access-token',
            'token_type' => 'Bearer',
        ]),
        'https://api-m.sandbox.paypal.com/v2/checkout/orders' => Http::response([
            'message' => 'INSTRUMENT_DECLINED',
        ], 422),
    ]);

    [, , $subscription] = createDuePaypalSubscription();
    $service = app(SubscriptionAutoRenewService::class);

    expect($service->process($subscription))->toBe('failed');
    expect($subscription->fresh()->renew_attempts)->toBe(1)
        ->and($subscription->fresh()->auto_renew)->toBeTrue();

    expect($service->process($subscription->fresh()))->toBe('failed');
    expect($subscription->fresh()->renew_attempts)->toBe(2)
        ->and($subscription->fresh()->auto_renew)->toBeFalse()
        ->and($subscription->fresh()->status)->toBe(SubscriptionStatus::PAST_DUE);
});

test('expired processing defers while paypal auto renew can still retry', function (): void {
    [, , $subscription] = createDuePaypalSubscription([
        'status' => SubscriptionStatus::ACTIVE->value,
        'renew_attempts' => 0,
    ]);

    $lifecycle = app(\App\Services\Subscription\SubscriptionLifecycleService::class);

    expect($lifecycle->isEligibleForExpiryProcessing($subscription))->toBeFalse();

    $subscription->update([
        'auto_renew' => false,
    ]);

    expect($lifecycle->isEligibleForExpiryProcessing($subscription->fresh()))->toBeTrue();
});

test('subscribe without vault coerces auto_renew to false but stores paypal method', function (): void {
    Http::fake([
        'https://api-m.sandbox.paypal.com/v1/oauth2/token' => Http::response([
            'access_token' => 'test-access-token',
            'token_type' => 'Bearer',
        ]),
        'https://api-m.sandbox.paypal.com/v2/checkout/orders/*' => Http::response([
            'id' => 'ORDER-NO-VAULT',
            'status' => 'COMPLETED',
            'purchase_units' => [
                [
                    'amount' => [
                        'value' => '99.00',
                        'currency_code' => 'USD',
                    ],
                ],
            ],
        ]),
    ]);

    $manufacturer = User::factory()->create(['role' => UserRole::MANUFACTURER->value]);
    $plan = createAutoRenewPlan();

    \Laravel\Passport\Passport::actingAs($manufacturer);

    /** @var TestCase $this */
    $this->postJson('/api/v1/manufacturer/subscriptions/subscribe', [
        'plan_id' => $plan->id,
        'payment_method' => 'paypal',
        'billing_interval' => 'month',
        'payment_id' => 'ORDER-NO-VAULT',
        'auto_renew' => true,
        'paid_amount' => 99.00,
    ])->assertCreated()
        ->assertJsonPath('data.auto_renew', false)
        ->assertJsonPath('data.payment_method', 'paypal')
        ->assertJsonPath('data.has_reusable_payment_method', false);
});
