<?php

use App\Enums\UserRole;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Passport\ClientRepository;
use Laravel\Passport\Passport;

uses(RefreshDatabase::class);

beforeEach(function (): void {
    app(ClientRepository::class)->createPersonalAccessGrantClient(
        name: 'Test Personal Access Client',
        provider: config('auth.guards.api.provider')
    );
});

test('manufacturer without subscription cannot create products', function (): void {
    $manufacturer = User::factory()->create(['role' => UserRole::MANUFACTURER->value]);
    Passport::actingAs($manufacturer);

    $this->postJson('/api/v1/manufacturer/products', [
        'name' => 'Test Product',
        'description' => 'Description',
        'category_id' => 1,
        'sub_category_id' => 1,
    ])->assertForbidden()
        ->assertJsonPath('data.code', 'no_active_subscription');
});

test('manufacturer cannot exceed product limit', function (): void {
    $manufacturer = createSubscribedManufacturer([
        ['key' => 'product_limit', 'input_type' => 'text', 'value' => '1'],
        ['key' => 'company_profile', 'input_type' => 'boolean', 'value' => '1'],
    ]);

    seedOrderSelectProduct($manufacturer);

    Passport::actingAs($manufacturer);

    $industryId = (int) \Illuminate\Support\Facades\DB::table('industries')->value('id');
    $subCategoryId = (int) \Illuminate\Support\Facades\DB::table('sub_categories')->value('id');
    $currencyId = (int) \Illuminate\Support\Facades\DB::table('currencies')->where('code', 'USD')->value('id');

    $this->postJson('/api/v1/manufacturer/products', [
        'name' => 'Second Product',
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
        ->assertJsonPath('data.code', 'limit_exceeded');
});

test('manufacturer without catalog upload feature cannot create catalogs', function (): void {
    $manufacturer = createSubscribedManufacturer([
        ['key' => 'product_limit', 'input_type' => 'text', 'value' => '10'],
    ]);

    Passport::actingAs($manufacturer);

    $this->post('/api/v1/manufacturer/catalogs', [
        'name' => 'Catalog',
        'status' => 'active',
    ])->assertForbidden()
        ->assertJsonPath('data.code', 'feature_not_available')
        ->assertJsonPath('data.feature', 'catalog_upload');
});

test('manufacturer without export markets feature cannot update export markets', function (): void {
    $manufacturer = createSubscribedManufacturer([
        ['key' => 'company_profile', 'input_type' => 'boolean', 'value' => '1'],
    ]);

    $manufacturer->company()->create([
        'company_name' => 'Test Co',
        'short_description' => 'Short',
        'long_description' => 'Long',
        'company_type' => 'manufacturer',
        'country' => 'China',
        'city' => 'Shenzhen',
    ]);

    Passport::actingAs($manufacturer);

    $this->putJson('/api/v1/manufacturer/profile/update', [
        'company_name' => 'Test Co',
        'country' => 'China',
        'city' => 'Shenzhen',
        'street_address' => '123 Industrial Road',
        'export_markets' => ['Europe'],
    ])->assertForbidden()
        ->assertJsonPath('data.code', 'feature_not_available')
        ->assertJsonPath('data.feature', 'export_markets_section');
});

test('subscription endpoints remain accessible without active subscription', function (): void {
    $manufacturer = User::factory()->create(['role' => UserRole::MANUFACTURER->value]);
    Passport::actingAs($manufacturer);

    $this->getJson('/api/v1/manufacturer/subscriptions')
        ->assertNotFound();
});
