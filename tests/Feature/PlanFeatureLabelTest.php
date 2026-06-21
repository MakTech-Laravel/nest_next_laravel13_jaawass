<?php

use App\Enums\UserRole;
use App\Models\Currency;
use App\Models\Feature;
use App\Models\Plan;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Passport\ClientRepository;
use Laravel\Passport\Passport;
use Tests\TestCase;

uses(RefreshDatabase::class);

beforeEach(function (): void {
    app(ClientRepository::class)->createPersonalAccessGrantClient(
        name: 'Test Personal Access Client',
        provider: config('auth.guards.api.provider')
    );

    if (! Currency::query()->where('code', 'USD')->exists()) {
        Currency::query()->create([
            'code' => 'USD',
            'name' => 'US Dollar',
            'symbol' => '$',
            'is_active' => true,
        ]);
    }
});

function createCatalogFeature(string $name = 'Product Limit', string $key = 'product_limit'): Feature
{
    return Feature::query()->create([
        'name' => $name,
        'key' => $key,
    ]);
}

function planPayload(array $features, array $overrides = []): array
{
    return array_merge([
        'name' => 'Test Plan',
        'description' => 'Test description',
        'button_text' => 'Subscribe',
        'monthly_price' => 99,
        'yearly_price' => 990,
        'currency_code' => 'USD',
        'features' => $features,
    ], $overrides);
}

test('create plan with custom feature label stores and returns label', function (): void {
    $admin = User::factory()->create(['role' => UserRole::ADMIN->value]);
    $feature = createCatalogFeature();

    Passport::actingAs($admin);

    /** @var TestCase $this */
    $response = $this->postJson('/api/v1/admin/plans/create', planPayload([
        [
            'id' => $feature->id,
            'input_type' => 'text',
            'value' => '500',
            'label' => 'Up to 500 active product listings',
        ],
    ], ['name' => 'Growth']));

    $response->assertOk()
        ->assertJsonPath('data.features.0.label', 'Up to 500 active product listings');

    $this->assertDatabaseHas('plan_feature', [
        'feature_id' => $feature->id,
        'label' => 'Up to 500 active product listings',
        'value' => '500',
    ]);
});

test('create plan without feature label returns catalog feature name as label', function (): void {
    $admin = User::factory()->create(['role' => UserRole::ADMIN->value]);
    $feature = createCatalogFeature('Company Profile', 'company_profile');

    Passport::actingAs($admin);

    /** @var TestCase $this */
    $response = $this->postJson('/api/v1/admin/plans/create', planPayload([
        [
            'id' => $feature->id,
            'input_type' => 'boolean',
            'value' => '1',
        ],
    ]));

    $response->assertOk()
        ->assertJsonPath('data.features.0.label', 'Company Profile');

    $this->assertDatabaseHas('plan_feature', [
        'feature_id' => $feature->id,
        'label' => null,
    ]);
});

test('same feature can have different labels on different plans', function (): void {
    $admin = User::factory()->create(['role' => UserRole::ADMIN->value]);
    $feature = createCatalogFeature();

    Passport::actingAs($admin);

    /** @var TestCase $this */
    $starter = $this->postJson('/api/v1/admin/plans/create', planPayload([
        [
            'id' => $feature->id,
            'input_type' => 'text',
            'value' => '100',
            'label' => 'Up to 100 products',
        ],
    ], ['name' => 'Starter']))->assertOk();

    $growth = $this->postJson('/api/v1/admin/plans/create', planPayload([
        [
            'id' => $feature->id,
            'input_type' => 'text',
            'value' => '500',
            'label' => 'Up to 500 products',
        ],
    ], ['name' => 'Growth']))->assertOk();

    expect($starter->json('data.features.0.label'))->toBe('Up to 100 products')
        ->and($growth->json('data.features.0.label'))->toBe('Up to 500 products');
});

test('update plan replaces feature labels', function (): void {
    $admin = User::factory()->create(['role' => UserRole::ADMIN->value]);
    $feature = createCatalogFeature();

    Passport::actingAs($admin);

    /** @var TestCase $this */
    $create = $this->postJson('/api/v1/admin/plans/create', planPayload([
        [
            'id' => $feature->id,
            'input_type' => 'text',
            'value' => '100',
            'label' => 'Old label',
        ],
    ]))->assertOk();

    $planId = $create->json('data.id');

    $this->putJson("/api/v1/admin/plans/{$planId}", planPayload([
        [
            'id' => $feature->id,
            'input_type' => 'text',
            'value' => '200',
            'label' => 'New label',
        ],
    ], [
        'name' => 'Updated Plan',
        'status' => true,
        'is_popular' => false,
    ]))
        ->assertOk()
        ->assertJsonPath('data.features.0.label', 'New label');

    $this->assertDatabaseHas('plan_feature', [
        'plan_id' => $planId,
        'feature_id' => $feature->id,
        'label' => 'New label',
        'value' => '200',
    ]);

    $this->assertDatabaseMissing('plan_feature', [
        'plan_id' => $planId,
        'feature_id' => $feature->id,
        'label' => 'Old label',
    ]);
});

test('public plans listing includes resolved feature label', function (): void {
    $feature = createCatalogFeature('Basic Analytics', 'basic_analytics');
    $currencyId = Currency::query()->where('code', 'USD')->value('id');

    $plan = Plan::query()->create([
        'currency_id' => $currencyId,
        'name' => 'Growth',
        'description' => 'Growth plan',
        'button_text' => 'Start',
        'monthly_price' => 299,
        'yearly_price' => 2990,
        'is_popular' => true,
        'status' => true,
    ]);

    $plan->planFeatures()->create([
        'feature_id' => $feature->id,
        'input_type' => 'boolean',
        'value' => '1',
        'label' => 'Full analytics dashboard',
    ]);

    /** @var TestCase $this */
    $this->getJson('/api/v1/plans')
        ->assertOk()
        ->assertJsonPath('data.0.features.0.label', 'Full analytics dashboard');
});
