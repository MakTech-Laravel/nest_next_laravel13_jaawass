<?php

use App\Enums\Api\V1\SubscriptionStatus;
use App\Enums\UserRole;
use App\Jobs\SendMailJob;
use App\Jobs\Subscription\SendSubscriptionInAppNotificationJob;
use App\Models\Plan;
use App\Models\Subscription;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
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
    ]);
});

function createToggleSubscription(array $overrides = []): array
{
    $manufacturer = User::factory()->create(['role' => UserRole::MANUFACTURER->value]);
    $plan = Plan::query()->create([
        'name' => 'Growth',
        'description' => 'Test',
        'button_text' => 'Subscribe',
        'monthly_price' => 49,
        'yearly_price' => 490,
        'is_popular' => true,
        'status' => true,
    ]);

    $subscription = Subscription::query()->create(array_merge([
        'manufacturer_id' => $manufacturer->id,
        'plan_id' => $plan->id,
        'billing_interval' => 'month',
        'status' => SubscriptionStatus::ACTIVE->value,
        'starts_at' => now()->subDays(5),
        'ends_at' => now()->addDays(25),
        'auto_renew' => true,
        'payment_method' => 'paypal',
        'paypal_vault_id' => 'VAULT-EXISTING',
        'paypal_payer_id' => 'PAYER-1',
    ], $overrides));

    return [$manufacturer, $subscription];
}

test('manufacturer can disable auto renew on current plan without paypal', function (): void {
    [$manufacturer, $subscription] = createToggleSubscription();
    $token = $manufacturer->createToken('test')->accessToken;

    $this->withToken($token)
        ->postJson('/api/v1/manufacturer/subscriptions/auto-renew', [
            'enabled' => false,
        ])
        ->assertOk()
        ->assertJsonPath('data.auto_renew', false)
        ->assertJsonPath('data.has_reusable_payment_method', true);

    expect($subscription->fresh()->auto_renew)->toBeFalse()
        ->and($subscription->fresh()->paypal_vault_id)->toBe('VAULT-EXISTING');
});

test('manufacturer can re-enable auto renew when vault already exists', function (): void {
    [$manufacturer, $subscription] = createToggleSubscription([
        'auto_renew' => false,
    ]);
    $token = $manufacturer->createToken('test')->accessToken;

    $this->withToken($token)
        ->postJson('/api/v1/manufacturer/subscriptions/auto-renew', [
            'enabled' => true,
        ])
        ->assertOk()
        ->assertJsonPath('data.auto_renew', true)
        ->assertJsonPath('data.has_reusable_payment_method', true);

    expect($subscription->fresh()->auto_renew)->toBeTrue();
});

test('enable auto renew without vault requires setup token', function (): void {
    [$manufacturer] = createToggleSubscription([
        'auto_renew' => false,
        'paypal_vault_id' => null,
        'paypal_payer_id' => null,
    ]);
    $token = $manufacturer->createToken('test')->accessToken;

    $this->withToken($token)
        ->postJson('/api/v1/manufacturer/subscriptions/auto-renew', [
            'enabled' => true,
        ])
        ->assertStatus(422)
        ->assertJsonValidationErrors(['auto_renew']);
});

test('enable auto renew exchanges vault setup token and stores vault id', function (): void {
    Http::fake([
        'https://api-m.sandbox.paypal.com/v1/oauth2/token' => Http::response([
            'access_token' => 'ACCESS',
            'expires_in' => 3600,
        ], 200),
        'https://api-m.sandbox.paypal.com/v3/vault/payment-tokens' => Http::response([
            'id' => 'VAULT-FROM-SETUP',
            'customer' => ['id' => 'CUST-9'],
            'payment_source' => [
                'paypal' => ['payer_id' => 'PAYER-9'],
            ],
        ], 200),
    ]);

    [$manufacturer, $subscription] = createToggleSubscription([
        'auto_renew' => false,
        'paypal_vault_id' => null,
        'paypal_payer_id' => null,
    ]);
    $token = $manufacturer->createToken('test')->accessToken;

    $this->withToken($token)
        ->postJson('/api/v1/manufacturer/subscriptions/auto-renew', [
            'enabled' => true,
            'vault_setup_token' => 'SETUP-TOKEN-1',
        ])
        ->assertOk()
        ->assertJsonPath('data.auto_renew', true)
        ->assertJsonPath('data.has_reusable_payment_method', true);

    $fresh = $subscription->fresh();
    expect($fresh->auto_renew)->toBeTrue()
        ->and($fresh->paypal_vault_id)->toBe('VAULT-FROM-SETUP')
        ->and($fresh->payment_method)->toBe('paypal');
});
