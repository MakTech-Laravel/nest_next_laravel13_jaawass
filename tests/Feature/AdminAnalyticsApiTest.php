<?php

use App\Enums\UserManuFactureStatus;
use App\Enums\UserRole;
use App\Enums\UserStatus;
use App\Models\Company;
use App\Models\Industry;
use App\Models\Payment;
use App\Models\Product;
use App\Models\SubCategory;
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

    $this->seed([
        \Database\Seeders\LanguageSeeder::class,
        \Database\Seeders\CurrencySeeder::class,
        \Database\Seeders\UserSeeder::class,
        \Database\Seeders\IndustrySeeder::class,
        \Database\Seeders\SubCategorySeeder::class,
    ]);
});

test('admin analytics metrics endpoint returns six metric cards', function (): void {
    Payment::query()->create([
        'payment_id' => 'pay-metrics-1',
        'payment_method' => 'stripe',
        'amount' => 1200,
        'status' => 'paid',
        'user_id' => User::query()->where('role', UserRole::MANUFACTURER->value)->value('id'),
        'source_id' => 1,
        'source_type' => 'App\\Models\\Subscription',
    ]);

    $admin = User::query()->where('email', 'admin@dev.com')->firstOrFail();
    Passport::actingAs($admin);

    $response = $this->getJson('/api/v1/admin/analytics/metrics?period=this_month');

    $response->assertOk()
        ->assertJsonPath('success', true)
        ->assertJsonStructure([
            'data' => [
                'period',
                'date_from',
                'date_to',
                'metrics' => [
                    '*' => ['key', 'label', 'value', 'raw_value', 'change', 'trend'],
                ],
            ],
        ])
        ->assertJsonCount(6, 'data.metrics');

    $keys = collect($response->json('data.metrics'))->pluck('key')->all();
    expect($keys)->toContain('total_revenue', 'active_users', 'messages_sent');
});

test('admin analytics growth endpoint supports pagination and search', function (): void {
    $admin = User::query()->where('email', 'admin@dev.com')->firstOrFail();
    Passport::actingAs($admin);

    $response = $this->getJson('/api/v1/admin/analytics/growth?months=3&per_page=2&page=1&order_by=period&order_direction=desc');

    $response->assertOk()
        ->assertJsonPath('success', true)
        ->assertJsonStructure([
            'data' => [
                '*' => ['name', 'period', 'year', 'users', 'suppliers', 'rfqs'],
            ],
            'links',
            'meta',
        ])
        ->assertJsonPath('meta.per_page', 2);
});

test('admin analytics countries endpoint supports search filter and pagination', function (): void {
    $buyer = User::query()->where('email', 'user@dev.com')->firstOrFail();

    Company::query()->create([
        'user_id' => $buyer->id,
        'company_name' => 'ABC Imports LLC',
        'country' => 'United States',
        'city' => 'Los Angeles',
    ]);

    $admin = User::query()->where('email', 'admin@dev.com')->firstOrFail();
    Passport::actingAs($admin);

    $response = $this->getJson('/api/v1/admin/analytics/countries?search=United&per_page=10&order_by=users&order_direction=desc');

    $response->assertOk()
        ->assertJsonPath('success', true)
        ->assertJsonStructure([
            'data' => [
                '*' => ['country', 'users', 'raw_users', 'percentage'],
            ],
            'links',
            'meta',
        ]);

    $countries = collect($response->json('data'))->pluck('country')->all();
    expect($countries)->toContain('United States');
});

test('admin analytics industries endpoint supports search and pagination', function (): void {
    $manufacturer = User::query()->where('email', 'manufacturer@dev.com')->firstOrFail();
    $industry = Industry::query()->where('slug', 'manufacturing')->firstOrFail();
    $subCategory = SubCategory::query()->where('industry_id', $industry->id)->firstOrFail();

    Product::query()->create([
        'user_id' => $manufacturer->id,
        'name' => 'Analytics Product',
        'slug' => 'analytics-product-'.uniqid(),
        'description' => 'Test product',
        'industry_id' => $industry->id,
        'sub_category_id' => $subCategory->id,
        'status' => 'active',
        'is_approved' => true,
    ]);

    $admin = User::query()->where('email', 'admin@dev.com')->firstOrFail();
    Passport::actingAs($admin);

    $response = $this->getJson('/api/v1/admin/analytics/industries?search=Manufacturing&per_page=5&order_by=products&order_direction=desc');

    $response->assertOk()
        ->assertJsonPath('success', true)
        ->assertJsonStructure([
            'data' => [
                '*' => ['id', 'industry', 'slug', 'suppliers', 'products'],
            ],
            'links',
            'meta',
        ]);

    $match = collect($response->json('data'))->firstWhere('slug', 'manufacturing');
    expect($match)->not->toBeNull()
        ->and($match['products'])->toBeGreaterThan(0);
});

test('buyer cannot access admin analytics endpoints', function (): void {
    $buyer = User::query()->where('email', 'user@dev.com')->firstOrFail();
    Passport::actingAs($buyer);

    $this->getJson('/api/v1/admin/analytics/metrics')->assertForbidden();
    $this->getJson('/api/v1/admin/analytics/growth')->assertForbidden();
    $this->getJson('/api/v1/admin/analytics/countries')->assertForbidden();
    $this->getJson('/api/v1/admin/analytics/industries')->assertForbidden();
});

test('admin analytics growth returns monthly rows for current period', function (): void {
    $admin = User::query()->where('email', 'admin@dev.com')->firstOrFail();
    Passport::actingAs($admin);

    $growth = $this->getJson('/api/v1/admin/analytics/growth?months=1')->assertOk();

    expect($growth->json('data'))->toHaveCount(1)
        ->and($growth->json('data.0'))->toHaveKeys(['name', 'period', 'users', 'suppliers', 'rfqs']);
});
