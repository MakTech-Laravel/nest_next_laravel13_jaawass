<?php

use App\Enums\Api\V1\SubscriptionSource;
use App\Enums\Api\V1\SubscriptionStatus;
use App\Enums\PromotionUserStatus;
use App\Enums\UserRole;
use App\Models\Company;
use App\Models\Plan;
use App\Models\Promotion;
use App\Models\Subscription;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Laravel\Passport\ClientRepository;
use Laravel\Passport\Passport;
use Tests\TestCase;

uses(RefreshDatabase::class);

beforeEach(function (): void {
    app(ClientRepository::class)->createPersonalAccessGrantClient(
        name: 'Test Personal Access Client',
        provider: config('auth.guards.api.provider')
    );

    $currencyId = \App\Models\Currency::query()->where('code', 'USD')->value('id')
        ?? \App\Models\Currency::query()->create([
            'code' => 'USD',
            'name' => 'US Dollar',
            'symbol' => '$',
            'is_active' => true,
        ])->id;

    if (! Plan::query()->exists()) {
        Plan::query()->insert([
            [
                'currency_id' => $currencyId,
                'name' => 'Starter',
                'description' => 'Starter plan',
                'button_text' => 'Start',
                'monthly_price' => 149,
                'yearly_price' => 1490,
                'is_popular' => false,
                'status' => true,
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'currency_id' => $currencyId,
                'name' => 'Growth',
                'description' => 'Growth plan',
                'button_text' => 'Start',
                'monthly_price' => 299,
                'yearly_price' => 2990,
                'is_popular' => true,
                'status' => true,
                'created_at' => now(),
                'updated_at' => now(),
            ],
        ]);
    }

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
                        'value' => '299.00',
                        'currency_code' => 'USD',
                    ],
                ],
            ],
        ]),
    ]);
});

function growthPlanId(): int
{
    return (int) (Plan::query()->orderBy('id')->skip(1)->value('id')
        ?? Plan::query()->value('id'));
}

function createPromotionTrialManufacturer(Promotion $promotion): User
{
    $manufacturer = User::factory()->manufacturerApproved()->create();
    Company::query()->create([
        'user_id' => $manufacturer->id,
        'company_name' => 'Trial Co',
        'slug' => 'trial-co-'.$manufacturer->id,
        'company_type' => 'manufacturer',
        'country' => 'China',
        'city' => 'Shenzhen',
        'street_address' => '1 Road',
        'phone' => '+86123456789',
        'zip_code' => '518000',
    ]);

    $promotion->users()->attach($manufacturer->id, [
        'status' => PromotionUserStatus::PENDING->value,
        'participated_at' => now(),
    ]);

    return $manufacturer;
}

test('accepting promotion creates trialing subscription with promotion source', function (): void {
    $growthPlanId = growthPlanId();
    $promotion = Promotion::factory()->create(['plan_id' => $growthPlanId]);
    $manufacturer = createPromotionTrialManufacturer($promotion);

    $admin = User::factory()->create(['role' => UserRole::ADMIN->value]);
    Passport::actingAs($admin);

    /** @var TestCase $this */
    $this->patchJson("/api/v1/admin/promotions/{$promotion->id}/participants/{$manufacturer->id}", [
        'status' => PromotionUserStatus::ACCEPTED->value,
    ])->assertOk();

    $this->assertDatabaseHas('subscriptions', [
        'manufacturer_id' => $manufacturer->id,
        'plan_id' => $growthPlanId,
        'status' => SubscriptionStatus::TRIALING->value,
        'source' => SubscriptionSource::PROMOTION->value,
        'promotion_id' => $promotion->id,
    ]);
});

test('accepting promotion keeps active paid subscription unchanged', function (): void {
    $growthPlanId = growthPlanId();
    $promotion = Promotion::factory()->create(['plan_id' => $growthPlanId]);
    $manufacturer = createPromotionTrialManufacturer($promotion);

    attachActiveSubscription($manufacturer, [], [
        'plan_id' => $growthPlanId,
        'status' => SubscriptionStatus::ACTIVE->value,
        'ends_at' => now()->addMonth(),
        'source' => SubscriptionSource::PURCHASE->value,
    ]);

    $originalEndsAt = $manufacturer->fresh()->subscription->ends_at?->toDateTimeString();

    $admin = User::factory()->create(['role' => UserRole::ADMIN->value]);
    Passport::actingAs($admin);

    /** @var TestCase $this */
    $this->patchJson("/api/v1/admin/promotions/{$promotion->id}/participants/{$manufacturer->id}", [
        'status' => PromotionUserStatus::ACCEPTED->value,
    ])->assertOk();

    $manufacturer->refresh()->load('subscription');

    expect($manufacturer->subscription->status)->toBe(SubscriptionStatus::ACTIVE)
        ->and($manufacturer->subscription->source)->toBe(SubscriptionSource::PURCHASE)
        ->and($manufacturer->subscription->ends_at?->toDateTimeString())->toBe($originalEndsAt)
        ->and(app(\App\Services\Subscription\PlanEntitlementResolver::class)->for($manufacturer)->hasActiveSubscription())->toBeTrue();
});

test('accepting promotion reactivates expired trialing subscription', function (): void {
    $growthPlanId = growthPlanId();
    $promotion = Promotion::factory()->create(['plan_id' => $growthPlanId]);
    $manufacturer = createPromotionTrialManufacturer($promotion);

    Subscription::query()->create([
        'manufacturer_id' => $manufacturer->id,
        'plan_id' => $growthPlanId,
        'billing_interval' => 'month',
        'status' => SubscriptionStatus::TRIALING->value,
        'starts_at' => now()->subMonths(7),
        'ends_at' => now()->subMonth(),
        'trial_ends_at' => now()->subMonth(),
        'auto_renew' => false,
        'source' => SubscriptionSource::PROMOTION->value,
    ]);

    $admin = User::factory()->create(['role' => UserRole::ADMIN->value]);
    Passport::actingAs($admin);

    /** @var TestCase $this */
    $this->patchJson("/api/v1/admin/promotions/{$promotion->id}/participants/{$manufacturer->id}", [
        'status' => PromotionUserStatus::ACCEPTED->value,
    ])->assertOk();

    $manufacturer->refresh()->load('subscription');

    expect($manufacturer->subscription->status)->toBe(SubscriptionStatus::TRIALING)
        ->and($manufacturer->subscription->ends_at?->isFuture())->toBeTrue()
        ->and($manufacturer->subscription->promotion_id)->toBe($promotion->id)
        ->and(app(\App\Services\Subscription\PlanEntitlementResolver::class)->for($manufacturer)->hasActiveSubscription())->toBeTrue();
});

test('expired promotion trial can renew via subscribe on same plan', function (): void {
    $growthPlanId = growthPlanId();
    $manufacturer = User::factory()->manufacturerApproved()->create();

    Subscription::query()->create([
        'manufacturer_id' => $manufacturer->id,
        'plan_id' => $growthPlanId,
        'billing_interval' => 'month',
        'status' => SubscriptionStatus::TRIALING->value,
        'starts_at' => now()->subMonths(7),
        'ends_at' => now()->subDay(),
        'trial_ends_at' => now()->subDay(),
        'auto_renew' => false,
        'source' => SubscriptionSource::PROMOTION->value,
    ]);

    Passport::actingAs($manufacturer);

    /** @var TestCase $this */
    $this->postJson('/api/v1/manufacturer/subscriptions/subscribe', [
        'plan_id' => $growthPlanId,
        'payment_method' => 'paypal',
        'billing_interval' => 'month',
        'payment_id' => 'ORDER-TEST-123',
        'auto_renew' => true,
        'paid_amount' => 299.00,
    ])->assertCreated()
        ->assertJsonPath('data.status', 'active')
        ->assertJsonPath('data.is_active', true)
        ->assertJsonPath('data.source', SubscriptionSource::PURCHASE->value)
        ->assertJsonPath('data.promotion_id', null);

    expect(Subscription::query()->where('manufacturer_id', $manufacturer->id)->count())->toBe(1);

    $this->getJson('/api/v1/manufacturer/products')->assertOk();
});

test('expired promotion trial can renew via upgrade on same plan', function (): void {
    $growthPlanId = growthPlanId();
    $manufacturer = User::factory()->manufacturerApproved()->create();

    Subscription::query()->create([
        'manufacturer_id' => $manufacturer->id,
        'plan_id' => $growthPlanId,
        'billing_interval' => 'month',
        'status' => SubscriptionStatus::TRIALING->value,
        'starts_at' => now()->subMonths(7),
        'ends_at' => now()->subDay(),
        'trial_ends_at' => now()->subDay(),
        'auto_renew' => false,
        'source' => SubscriptionSource::PROMOTION->value,
    ]);

    Passport::actingAs($manufacturer);

    /** @var TestCase $this */
    $this->postJson('/api/v1/manufacturer/subscriptions/upgrade', [
        'plan_id' => $growthPlanId,
        'payment_method' => 'paypal',
        'billing_interval' => 'month',
        'payment_id' => 'ORDER-TEST-123',
        'auto_renew' => true,
        'paid_amount' => 299.00,
    ])->assertOk()
        ->assertJsonPath('data.status', 'active')
        ->assertJsonPath('data.is_active', true);
});

test('subscription show exposes is_active false for expired trial', function (): void {
    $growthPlanId = growthPlanId();
    $manufacturer = User::factory()->manufacturer()->create();

    Subscription::query()->create([
        'manufacturer_id' => $manufacturer->id,
        'plan_id' => $growthPlanId,
        'billing_interval' => 'month',
        'status' => SubscriptionStatus::TRIALING->value,
        'starts_at' => now()->subMonths(7),
        'ends_at' => now()->subDay(),
        'trial_ends_at' => now()->subDay(),
        'auto_renew' => false,
        'source' => SubscriptionSource::PROMOTION->value,
    ]);

    Passport::actingAs($manufacturer);

    /** @var TestCase $this */
    $this->getJson('/api/v1/manufacturer/subscriptions')
        ->assertOk()
        ->assertJsonPath('data.is_active', false)
        ->assertJsonPath('data.status', SubscriptionStatus::TRIALING->value);
});

test('manufacturer with active subscription cannot apply to promotion', function (): void {
    Promotion::factory()->create(['plan_id' => growthPlanId(), 'status' => true]);

    $manufacturer = manufacturerWithSubscription();
    Company::query()->create([
        'user_id' => $manufacturer->id,
        'company_name' => 'Paid Co',
    ]);

    Passport::actingAs($manufacturer);

    /** @var TestCase $this */
    $this->postJson('/api/v1/manufacturer/promotions/apply')
        ->assertUnprocessable()
        ->assertJsonPath('message', __('promotion.already_has_subscription'));
});

test('public suppliers exclude manufacturers with expired promotion trial', function (): void {
    $growthPlanId = growthPlanId();
    $expired = User::factory()->manufacturerApproved()->create();
    Company::query()->create([
        'user_id' => $expired->id,
        'company_name' => 'Expired Trial Co',
        'slug' => 'expired-trial-co',
        'company_type' => 'manufacturer',
        'country' => 'China',
        'city' => 'Shenzhen',
        'street_address' => '1 Road',
        'phone' => '+86123456789',
        'zip_code' => '518000',
    ]);

    Subscription::query()->create([
        'manufacturer_id' => $expired->id,
        'plan_id' => $growthPlanId,
        'billing_interval' => 'month',
        'status' => SubscriptionStatus::TRIALING->value,
        'starts_at' => now()->subMonths(7),
        'ends_at' => now()->subDay(),
        'trial_ends_at' => now()->subDay(),
        'auto_renew' => false,
        'source' => SubscriptionSource::PROMOTION->value,
    ]);

    $active = manufacturerWithSubscription();
    Company::query()->create([
        'user_id' => $active->id,
        'company_name' => 'Active Co',
        'slug' => 'active-co',
        'company_type' => 'manufacturer',
        'country' => 'China',
        'city' => 'Beijing',
        'street_address' => '1 Road',
        'phone' => '+86111111111',
        'zip_code' => '100000',
    ]);

    /** @var TestCase $this */
    $response = $this->getJson('/api/v1/suppliers')->assertOk();
    $ids = collect($response->json('data'))->pluck('id')->all();

    expect($ids)->toContain($active->id)
        ->and($ids)->not->toContain($expired->id);
});
