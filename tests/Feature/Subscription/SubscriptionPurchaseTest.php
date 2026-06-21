<?php

use App\Enums\UserRole;
use App\Jobs\SendMailJob;
use App\Jobs\Subscription\SendSubscriptionInAppNotificationJob;
use App\Models\Payment;
use App\Models\Plan;
use App\Models\Subscription;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Queue;
use Laravel\Passport\ClientRepository;
use Laravel\Passport\Passport;
use Tests\TestCase;

uses(RefreshDatabase::class);

beforeEach(function () {
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

    Http::fake([
        'https://api-m.sandbox.paypal.com/v1/oauth2/token' => Http::response([
            'access_token' => 'test-access-token',
            'token_type' => 'Bearer',
        ]),
        'https://api-m.sandbox.paypal.com/v2/checkout/orders/*' => Http::response([
            'id' => 'ORDER-TEST-123',
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
});

function createTestPlan(): Plan
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

test('manufacturer can subscribe with verified paypal payment', function () {
    $manufacturer = User::factory()->create(['role' => UserRole::MANUFACTURER->value]);
    $plan = createTestPlan();

    Passport::actingAs($manufacturer);

    /** @var TestCase $this */
    $response = $this->postJson('/api/v1/manufacturer/subscriptions/subscribe', [
        'plan_id' => $plan->id,
        'payment_method' => 'paypal',
        'billing_interval' => 'month',
        'payment_id' => 'ORDER-TEST-123',
        'auto_renew' => true,
        'paid_amount' => 99.00,
    ]);

    $response->assertCreated()
        ->assertJsonPath('data.status', 'active')
        ->assertJsonPath('data.auto_renew', true);

    $this->assertDatabaseHas('subscriptions', [
        'manufacturer_id' => $manufacturer->id,
        'plan_id' => $plan->id,
        'status' => 'active',
    ]);

    $this->assertDatabaseHas('payments', [
        'payment_id' => 'ORDER-TEST-123',
        'payment_method' => 'paypal',
        'user_id' => $manufacturer->id,
        'status' => 'paid',
    ]);
});

test('subscribe is idempotent for the same paypal payment id', function () {
    $manufacturer = User::factory()->create(['role' => UserRole::MANUFACTURER->value]);
    $plan = createTestPlan();

    Passport::actingAs($manufacturer);

    $payload = [
        'plan_id' => $plan->id,
        'payment_method' => 'paypal',
        'billing_interval' => 'month',
        'payment_id' => 'ORDER-IDEMPOTENT',
        'auto_renew' => true,
        'paid_amount' => 99.00,
    ];

    /** @var TestCase $this */
    $this->postJson('/api/v1/manufacturer/subscriptions/subscribe', $payload)->assertCreated();
    $this->postJson('/api/v1/manufacturer/subscriptions/subscribe', $payload)->assertOk();

    expect(Subscription::query()->where('manufacturer_id', $manufacturer->id)->count())->toBe(1);
    expect(Payment::query()->where('payment_id', 'ORDER-IDEMPOTENT')->count())->toBe(1);
});

test('subscribe returns conflict when manufacturer already has subscription', function () {
    $manufacturer = User::factory()->create(['role' => UserRole::MANUFACTURER->value]);
    $plan = createTestPlan();

    Subscription::query()->create([
        'manufacturer_id' => $manufacturer->id,
        'plan_id' => $plan->id,
        'billing_interval' => 'month',
        'status' => 'active',
        'starts_at' => now(),
        'ends_at' => now()->addMonth(),
        'auto_renew' => true,
    ]);

    Passport::actingAs($manufacturer);

    /** @var TestCase $this */
    $response = $this->postJson('/api/v1/manufacturer/subscriptions/subscribe', [
        'plan_id' => $plan->id,
        'payment_method' => 'paypal',
        'billing_interval' => 'month',
        'payment_id' => 'ORDER-NEW',
        'auto_renew' => true,
        'paid_amount' => 99.00,
    ]);

    $response->assertStatus(409);
});

test('manufacturer can cancel subscription auto renew', function () {
    $manufacturer = User::factory()->create(['role' => UserRole::MANUFACTURER->value]);
    $plan = createTestPlan();

    Subscription::query()->create([
        'manufacturer_id' => $manufacturer->id,
        'plan_id' => $plan->id,
        'billing_interval' => 'month',
        'status' => 'active',
        'starts_at' => now(),
        'ends_at' => now()->addMonth(),
        'auto_renew' => true,
    ]);

    Passport::actingAs($manufacturer);

    /** @var TestCase $this */
    $response = $this->postJson('/api/v1/manufacturer/subscriptions/cancel');

    $response->assertOk()
        ->assertJsonPath('data.auto_renew', false);
});

test('admin can view subscription stats', function () {
    $admin = User::factory()->create(['role' => UserRole::ADMIN->value]);
    $manufacturer = User::factory()->create(['role' => UserRole::MANUFACTURER->value]);
    $plan = createTestPlan();

    $subscription = Subscription::query()->create([
        'manufacturer_id' => $manufacturer->id,
        'plan_id' => $plan->id,
        'billing_interval' => 'month',
        'status' => 'active',
        'starts_at' => now(),
        'ends_at' => now()->addMonth(),
        'auto_renew' => true,
    ]);

    Payment::query()->create([
        'payment_id' => 'ORDER-ADMIN-STATS',
        'payment_method' => 'paypal',
        'amount' => 99,
        'status' => 'paid',
        'user_id' => $manufacturer->id,
        'subscription_id' => $subscription->id,
        'source_id' => $plan->id,
        'source_type' => Plan::class,
    ]);

    Passport::actingAs($admin);

    /** @var TestCase $this */
    $response = $this->getJson('/api/v1/admin/subscriptions/stats');

    $response->assertOk()
        ->assertJsonPath('data.overview.total_active_subscriptions', 1)
        ->assertJsonPath('data.this_month.payments_count', 1);
});
