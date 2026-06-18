<?php

use App\Enums\Api\V1\SubscriptionStatus;
use App\Enums\TicketPriority;
use App\Enums\UserRole;
use App\Models\Company;
use App\Models\Feature;
use App\Models\PlanFeature;
use App\Models\Subscription;
use App\Models\User;
use App\Services\Subscription\PlanEntitlementResolver;
use App\Services\Subscription\PlanEntitlementService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Laravel\Passport\ClientRepository;
use Laravel\Passport\Passport;

uses(RefreshDatabase::class);

beforeEach(function (): void {
    app(ClientRepository::class)->createPersonalAccessGrantClient(
        name: 'Test Personal Access Client',
        provider: config('auth.guards.api.provider')
    );
});

// --- PlanEntitlementService unit-style checks ---

test('plan entitlement service resolves starter package features', function (): void {
    $manufacturer = createSubscribedManufacturer([
        ['key' => 'product_limit', 'input_type' => 'text', 'value' => '100'],
        ['key' => 'basic_analytics', 'input_type' => 'boolean', 'value' => '1'],
        ['key' => 'company_profile', 'input_type' => 'boolean', 'value' => '1'],
    ]);

    $entitlements = app(PlanEntitlementResolver::class)->for($manufacturer);

    expect($entitlements->hasActiveSubscription())->toBeTrue()
        ->and($entitlements->hasFeature('basic_analytics'))->toBeTrue()
        ->and($entitlements->hasFeature('advanced_analytics'))->toBeFalse()
        ->and($entitlements->numericLimit('product_limit'))->toBe(100)
        ->and($entitlements->visibilityScore())->toBe(0);
});

test('plan entitlement service treats unlimited product limit as null', function (): void {
    $manufacturer = createSubscribedManufacturer([
        ['key' => 'product_limit', 'input_type' => 'text', 'value' => 'unlimited'],
    ]);

    $entitlements = app(PlanEntitlementResolver::class)->for($manufacturer);

    expect($entitlements->numericLimit('product_limit'))->toBeNull();

    $entitlements->assertWithinLimit('product_limit', 9999);
    expect(true)->toBeTrue();
});

test('expired subscription is not treated as active', function (): void {
    $manufacturer = createSubscribedManufacturer([], [
        'status' => SubscriptionStatus::ACTIVE->value,
        'ends_at' => now()->subDay(),
    ]);

    $entitlements = app(PlanEntitlementResolver::class)->for($manufacturer);

    expect($entitlements->hasActiveSubscription())->toBeFalse();
});

test('canceled subscription status is not treated as active', function (): void {
    $manufacturer = createSubscribedManufacturer([], [
        'status' => SubscriptionStatus::CANCELED->value,
        'ends_at' => now()->addMonth(),
    ]);

    expect(app(PlanEntitlementResolver::class)->for($manufacturer)->hasActiveSubscription())->toBeFalse();
});

test('trialing subscription is treated as active', function (): void {
    $manufacturer = createSubscribedManufacturer([], [
        'status' => SubscriptionStatus::TRIALING->value,
        'ends_at' => now()->addDays(14),
    ]);

    Passport::actingAs($manufacturer);

    $this->getJson('/api/v1/manufacturer/products')->assertOk();
});

// --- Allowed feature access ---

test('subscribed manufacturer can list products and dashboard with basic analytics', function (): void {
    $manufacturer = createSubscribedManufacturer([
        ['key' => 'basic_analytics', 'input_type' => 'boolean', 'value' => '1'],
        ['key' => 'product_limit', 'input_type' => 'text', 'value' => '10'],
    ]);

    Passport::actingAs($manufacturer);

    $this->getJson('/api/v1/manufacturer/products')->assertOk();
    $this->getJson('/api/v1/manufacturer/dashboard')
        ->assertOk()
        ->assertJsonStructure(['data' => ['stats', 'quick_stats']]);
});

test('subscribed manufacturer can access rfq inbox with feature', function (): void {
    $manufacturer = createSubscribedManufacturer([
        ['key' => 'inquiry_rfq_inbox', 'input_type' => 'boolean', 'value' => '1'],
    ]);

    Passport::actingAs($manufacturer);

    $this->getJson('/api/v1/manufacturer/rfqs')->assertOk();
});

test('buyer conversation access is not blocked by manufacturer subscription middleware', function (): void {
    $buyer = User::factory()->create(['role' => UserRole::BUYER->value]);
    $manufacturer = createSubscribedManufacturer([
        ['key' => 'internal_messaging', 'input_type' => 'boolean', 'value' => '1'],
    ]);

    Passport::actingAs($buyer);

    $this->postJson('/api/v1/conversations', [
        'participant_ids' => [$buyer->id, $manufacturer->id],
    ])->assertCreated();
});

// --- Restricted features ---

test('manufacturer without analytics features cannot access dashboard', function (): void {
    $manufacturer = createSubscribedManufacturer([
        ['key' => 'product_limit', 'input_type' => 'text', 'value' => '10'],
    ]);

    Passport::actingAs($manufacturer);

    $this->getJson('/api/v1/manufacturer/dashboard')
        ->assertForbidden()
        ->assertJsonPath('data.code', 'feature_not_available');
});

test('manufacturer without rfq inbox cannot list rfqs', function (): void {
    $manufacturer = createSubscribedManufacturer([
        ['key' => 'product_limit', 'input_type' => 'text', 'value' => '10'],
    ]);

    Passport::actingAs($manufacturer);

    $this->getJson('/api/v1/manufacturer/rfqs')
        ->assertForbidden()
        ->assertJsonPath('data.feature', 'inquiry_rfq_inbox');
});

test('manufacturer without internal messaging cannot send messages', function (): void {
    $buyer = User::factory()->create();
    $manufacturer = createSubscribedManufacturer([
        ['key' => 'product_limit', 'input_type' => 'text', 'value' => '10'],
    ]);

    $conversation = \App\Models\Conversation::factory()->create();
    $conversation->participants()->attach([$buyer->id, $manufacturer->id]);

    Passport::actingAs($manufacturer);

    $this->postJson("/api/v1/conversations/{$conversation->id}/messages", [
        'body' => 'Blocked',
    ])->assertForbidden()
        ->assertJsonPath('data.feature', 'internal_messaging');
});

test('manufacturer without certifications feature cannot list certificates', function (): void {
    $manufacturer = createSubscribedManufacturer([
        ['key' => 'product_limit', 'input_type' => 'text', 'value' => '10'],
    ]);

    Passport::actingAs($manufacturer);

    $this->getJson('/api/v1/manufacturer/certificate')
        ->assertForbidden()
        ->assertJsonPath('data.feature', 'certifications_section');
});

// --- Product limit boundaries ---

test('manufacturer can create product when below limit', function (): void {
    $manufacturer = createSubscribedManufacturer([
        ['key' => 'product_limit', 'input_type' => 'text', 'value' => '2'],
    ]);

    Passport::actingAs($manufacturer);

    $taxonomy = productTaxonomyIds();

    $this->postJson('/api/v1/manufacturer/products', [
        'name' => 'First Product',
        'description' => 'Description',
        'category_id' => $taxonomy['industry_id'],
        'sub_category_id' => $taxonomy['sub_category_id'],
        'status' => 'draft',
        'min_price' => 10,
        'max_price' => 20,
        'currency_id' => $taxonomy['currency_id'],
        'minimum_order_quantity' => 100,
        'unit' => 'pcs',
    ])->assertCreated();
});

test('manufacturer at exact product limit cannot create another', function (): void {
    $manufacturer = createSubscribedManufacturer([
        ['key' => 'product_limit', 'input_type' => 'text', 'value' => '1'],
    ]);

    seedOrderSelectProduct($manufacturer);
    Passport::actingAs($manufacturer);

    $industryId = (int) DB::table('industries')->value('id');
    $subCategoryId = (int) DB::table('sub_categories')->value('id');
    $currencyId = (int) DB::table('currencies')->where('code', 'USD')->value('id');

    $this->postJson('/api/v1/manufacturer/products', [
        'name' => 'Over limit',
        'description' => 'Description',
        'category_id' => $industryId,
        'sub_category_id' => $subCategoryId,
        'status' => 'draft',
        'min_price' => 10,
        'max_price' => 20,
        'currency_id' => $currencyId,
        'minimum_order_quantity' => 100,
        'unit' => 'pcs',
    ])->assertForbidden()
        ->assertJsonPath('data.code', 'limit_exceeded')
        ->assertJsonPath('data.limit', 1);
});

// --- Lifecycle: upgrade / downgrade ---

test('upgrading plan grants previously unavailable features', function (): void {
    $manufacturer = createSubscribedManufacturer([
        ['key' => 'product_limit', 'input_type' => 'text', 'value' => '10'],
    ]);

    Passport::actingAs($manufacturer);
    $this->getJson('/api/v1/manufacturer/rfqs')->assertForbidden();

    $subscription = $manufacturer->subscription;
    $plan = $subscription->plan;

    $feature = Feature::query()->firstOrCreate(
        ['key' => 'inquiry_rfq_inbox'],
        ['name' => 'Inquiry Inbox'],
    );

    PlanFeature::query()->updateOrCreate(
        [
            'plan_id' => $plan->id,
            'feature_id' => $feature->id,
        ],
        [
            'input_type' => 'boolean',
            'value' => '1',
        ],
    );

    app(PlanEntitlementResolver::class)->forget($manufacturer);

    $this->getJson('/api/v1/manufacturer/rfqs')->assertOk();
});

test('expired subscription blocks protected endpoints', function (): void {
    $manufacturer = createSubscribedManufacturer([], [
        'ends_at' => now()->subHour(),
    ]);

    Passport::actingAs($manufacturer);

    $this->getJson('/api/v1/manufacturer/products')
        ->assertForbidden()
        ->assertJsonPath('data.code', 'no_active_subscription');
});

// --- Dashboard analytics tiers ---

test('basic analytics plan hides advanced response metrics', function (): void {
    $manufacturer = createSubscribedManufacturer([
        ['key' => 'basic_analytics', 'input_type' => 'boolean', 'value' => '1'],
    ]);

    Passport::actingAs($manufacturer);

    $this->getJson('/api/v1/manufacturer/dashboard')
        ->assertOk()
        ->assertJsonPath('data.response_metrics', null);
});

test('advanced analytics plan includes response metrics', function (): void {
    $manufacturer = createSubscribedManufacturer([
        ['key' => 'advanced_analytics', 'input_type' => 'boolean', 'value' => '1'],
    ]);

    Passport::actingAs($manufacturer);

    $this->getJson('/api/v1/manufacturer/dashboard')
        ->assertOk()
        ->assertJsonStructure(['data' => ['response_metrics' => ['response_rate']]]);
});

// --- Public visibility ---

test('public suppliers exclude manufacturers without active subscription', function (): void {
    $withSub = manufacturerWithSubscription();
    Company::query()->create([
        'user_id' => $withSub->id,
        'company_name' => 'Subscribed Co',
        'slug' => 'subscribed-co',
        'company_type' => 'manufacturer',
        'country' => 'China',
        'city' => 'Shenzhen',
        'street_address' => '1 Road',
        'phone' => '+86123456789',
        'zip_code' => '518000',
    ]);

    $withoutSub = User::factory()->manufacturerApproved()->create();
    Company::query()->create([
        'user_id' => $withoutSub->id,
        'company_name' => 'No Sub Co',
        'slug' => 'no-sub-co',
        'company_type' => 'manufacturer',
        'country' => 'China',
        'city' => 'Beijing',
        'street_address' => '1 Road',
        'phone' => '+86111111111',
        'zip_code' => '100000',
    ]);

    $response = $this->getJson('/api/v1/suppliers')->assertOk();
    $ids = collect($response->json('data'))->pluck('id')->all();

    expect($ids)->toContain($withSub->id)
        ->and($ids)->not->toContain($withoutSub->id);
});

// --- Localization ---

test('subscription restriction message respects locale', function (): void {
    $manufacturer = User::factory()->create([
        'role' => UserRole::MANUFACTURER->value,
        'preferred_language' => 'ar',
    ]);
    Passport::actingAs($manufacturer);

    $response = $this->postJson('/api/v1/manufacturer/products', []);

    $response->assertForbidden();
    expect($response->json('message'))->toContain('اشتراك');
});

// --- Priority support ---

test('manufacturer with priority support gets high ticket priority', function (): void {
    $manufacturer = createSubscribedManufacturer([
        ['key' => 'priority_support', 'input_type' => 'boolean', 'value' => '1'],
    ]);

    Passport::actingAs($manufacturer);

    $this->postJson('/api/v1/customer-supports/tickets', [
        'subject' => 'Need help',
        'department_type' => 'technical',
        'message' => 'Issue details here',
    ])->assertCreated()
        ->assertJsonPath('data.priority', TicketPriority::High->value);
});

// --- Security: middleware cannot be skipped via alternate routes ---

test('direct product update still requires active subscription', function (): void {
    $manufacturer = User::factory()->create(['role' => UserRole::MANUFACTURER->value]);
    $product = seedOrderSelectProduct($manufacturer);

    Passport::actingAs($manufacturer);

    $this->putJson("/api/v1/manufacturer/products/{$product->id}", [
        'name' => 'Hacked',
        'description' => 'Bypass attempt',
        'category_id' => $product->industry_id,
        'sub_category_id' => $product->sub_category_id,
        'status' => 'draft',
    ])->assertForbidden()
        ->assertJsonPath('data.code', 'no_active_subscription');
});
