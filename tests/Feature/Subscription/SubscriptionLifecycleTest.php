<?php

use App\Enums\Api\V1\SubscriptionEventType;
use App\Enums\Api\V1\SubscriptionStatus;
use App\Enums\MailTemplate;
use App\Enums\UserRole;
use App\Jobs\SendMailJob;
use App\Jobs\Subscription\ProcessExpiredSubscriptionJob;
use App\Jobs\Subscription\SendSubscriptionExpiryReminderJob;
use App\Jobs\Subscription\SendSubscriptionInAppNotificationJob;
use App\Models\Plan;
use App\Models\Subscription;
use App\Models\SubscriptionLog;
use App\Models\User;
use App\Services\Subscription\SubscriptionLifecycleService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Queue;

uses(RefreshDatabase::class);

function createLifecyclePlan(): Plan
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

function createManufacturerWithSubscription(array $subscriptionAttributes = [], ?string $email = null): array
{
    $manufacturer = User::factory()->create([
        'role' => UserRole::MANUFACTURER->value,
        'email' => $email ?? fake()->unique()->safeEmail(),
    ]);

    $plan = createLifecyclePlan();

    $subscription = Subscription::query()->create(array_merge([
        'manufacturer_id' => $manufacturer->id,
        'plan_id' => $plan->id,
        'billing_interval' => 'month',
        'status' => SubscriptionStatus::ACTIVE->value,
        'starts_at' => now()->subMonth(),
        'ends_at' => now()->addDays(7),
        'trial_ends_at' => null,
        'auto_renew' => true,
        'source' => 'purchase',
    ], $subscriptionAttributes));

    return [$manufacturer, $plan, $subscription];
}

test('expiry reminders command queues jobs for subscriptions ending in seven days', function () {
    Queue::fake();

    [, , $subscription] = createManufacturerWithSubscription([
        'ends_at' => now()->addDays(7),
        'expiry_reminder_sent_at' => null,
    ]);

    createManufacturerWithSubscription([
        'ends_at' => now()->addDays(14),
        'expiry_reminder_sent_at' => null,
    ]);

    Artisan::call('subscriptions:send-expiry-reminders');

    Queue::assertPushed(SendSubscriptionExpiryReminderJob::class, 1);
    Queue::assertPushed(SendSubscriptionExpiryReminderJob::class, function (SendSubscriptionExpiryReminderJob $job) use ($subscription): bool {
        return $job->subscriptionId === $subscription->id;
    });
});

test('expiry reminder job sends email once and records reminder timestamp', function () {
    Queue::fake([SendMailJob::class, SendSubscriptionInAppNotificationJob::class]);

    [$manufacturer, , $subscription] = createManufacturerWithSubscription([
        'ends_at' => now()->addDays(7),
        'expiry_reminder_sent_at' => null,
    ]);

    $service = app(SubscriptionLifecycleService::class);

    expect($service->sendExpiryReminder($subscription))->toBeTrue();
    expect($service->sendExpiryReminder($subscription->fresh()))->toBeFalse();

    $subscription->refresh();
    expect($subscription->expiry_reminder_sent_at)->not->toBeNull();

    Queue::assertPushed(SendMailJob::class, function (SendMailJob $job) use ($manufacturer): bool {
        return $job->recipient === $manufacturer->email
            && $job->template === MailTemplate::SubscriptionExpiryReminder->value;
    });

    Queue::assertPushed(SendSubscriptionInAppNotificationJob::class, function (SendSubscriptionInAppNotificationJob $job) use ($manufacturer): bool {
        return $job->recipientId === $manufacturer->id
            && $job->type === 'plan.subscription.expiry_reminder';
    });
});

test('process expired command queues jobs for past-due subscriptions', function () {
    Queue::fake();

    [, , $subscription] = createManufacturerWithSubscription([
        'ends_at' => now()->subDay(),
        'status' => SubscriptionStatus::ACTIVE->value,
    ]);

    Artisan::call('subscriptions:process-expired');

    Queue::assertPushed(ProcessExpiredSubscriptionJob::class, 1);
    Queue::assertPushed(ProcessExpiredSubscriptionJob::class, function (ProcessExpiredSubscriptionJob $job) use ($subscription): bool {
        return $job->subscriptionId === $subscription->id;
    });
});

test('process expired job marks subscription past due logs event and sends email', function () {
    Queue::fake([SendMailJob::class, SendSubscriptionInAppNotificationJob::class]);

    [$manufacturer, $plan, $subscription] = createManufacturerWithSubscription([
        'ends_at' => now()->subHour(),
        'status' => SubscriptionStatus::ACTIVE->value,
    ]);

    $service = app(SubscriptionLifecycleService::class);

    expect($service->processExpiredSubscription($subscription))->toBeTrue();
    expect($subscription->fresh()->status)->toBe(SubscriptionStatus::PAST_DUE);
    expect($subscription->fresh()->isEntitlementActive())->toBeFalse();

    expect(SubscriptionLog::query()
        ->where('manufacturer_id', $manufacturer->id)
        ->where('event_type', SubscriptionEventType::SUBSCRIPTION_EXPIRED->value)
        ->exists())->toBeTrue();

    Queue::assertPushed(SendMailJob::class, function (SendMailJob $job) use ($manufacturer): bool {
        return $job->recipient === $manufacturer->email
            && $job->template === MailTemplate::SubscriptionExpired->value;
    });

    Queue::assertPushed(SendSubscriptionInAppNotificationJob::class, function (SendSubscriptionInAppNotificationJob $job) use ($manufacturer): bool {
        return $job->recipientId === $manufacturer->id
            && $job->type === 'plan.subscription.expired';
    });
});

test('renewal resets expiry reminder timestamp', function () {
    Queue::fake([SendMailJob::class, SendSubscriptionInAppNotificationJob::class]);

    app(\Laravel\Passport\ClientRepository::class)->createPersonalAccessGrantClient(
        name: 'Test Personal Access Client',
        provider: config('auth.guards.api.provider')
    );

    $manufacturer = User::factory()->create(['role' => UserRole::MANUFACTURER->value]);
    $plan = createLifecyclePlan();

    Subscription::query()->create([
        'manufacturer_id' => $manufacturer->id,
        'plan_id' => $plan->id,
        'billing_interval' => 'month',
        'status' => SubscriptionStatus::PAST_DUE->value,
        'starts_at' => now()->subMonths(2),
        'ends_at' => now()->subDay(),
        'trial_ends_at' => null,
        'auto_renew' => true,
        'expiry_reminder_sent_at' => now()->subWeek(),
        'source' => 'purchase',
    ]);

    config([
        'services.paypal.client_id' => 'test-client',
        'services.paypal.client_secret' => 'test-secret',
        'services.paypal.mode' => 'sandbox',
    ]);

    \Illuminate\Support\Facades\Http::fake([
        'https://api-m.sandbox.paypal.com/v1/oauth2/token' => \Illuminate\Support\Facades\Http::response([
            'access_token' => 'test-access-token',
            'token_type' => 'Bearer',
        ]),
        'https://api-m.sandbox.paypal.com/v2/checkout/orders/*' => \Illuminate\Support\Facades\Http::response([
            'id' => 'ORDER-RENEW-LIFECYCLE',
            'status' => 'COMPLETED',
            'purchase_units' => [
                ['amount' => ['value' => '99.00', 'currency_code' => 'USD']],
            ],
        ]),
    ]);

    \Laravel\Passport\Passport::actingAs($manufacturer);

    $response = test()->postJson('/api/v1/manufacturer/subscriptions/subscribe', [
        'plan_id' => $plan->id,
        'payment_method' => 'paypal',
        'billing_interval' => 'month',
        'payment_id' => 'ORDER-RENEW-LIFECYCLE',
        'auto_renew' => true,
        'paid_amount' => 99.00,
    ]);

    $response->assertCreated()
        ->assertJsonPath('data.status', 'active');

    $subscription = Subscription::query()->where('manufacturer_id', $manufacturer->id)->first();
    expect($subscription->expiry_reminder_sent_at)->toBeNull();
    expect($subscription->isEntitlementActive())->toBeTrue();

    Queue::assertPushed(SendMailJob::class, function (SendMailJob $job) use ($manufacturer): bool {
        return $job->recipient === $manufacturer->email
            && $job->template === MailTemplate::SubscriptionRenewed->value;
    });
});
