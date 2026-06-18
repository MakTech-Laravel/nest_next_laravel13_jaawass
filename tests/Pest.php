<?php

use App\Enums\Api\V1\SubscriptionStatus;
use App\Enums\UserRole;
use App\Models\Feature;
use App\Models\Plan;
use App\Models\PlanFeature;
use App\Models\Product;
use App\Models\Subscription;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Laravel\Passport\Passport;
use Tests\TestCase;

/*
|--------------------------------------------------------------------------
| Test Case
|--------------------------------------------------------------------------
|
| The closure you provide to your test functions is always bound to a specific PHPUnit test
| case class. By default, that class is "PHPUnit\Framework\TestCase". Of course, you may
| need to change it using the "pest()" function to bind a different classes or traits.
|
*/

pest()->extend(TestCase::class)
 // ->use(Illuminate\Foundation\Testing\RefreshDatabase::class)
    ->in('Feature');

/*
|--------------------------------------------------------------------------
| Expectations
|--------------------------------------------------------------------------
|
| When you're writing tests, you often need to check that values meet certain conditions. The
| "expect()" function gives you access to a set of "expectations" methods that you can use
| to assert different things. Of course, you may extend the Expectation API at any time.
|
*/

expect()->extend('toBeOne', function () {
    return $this->toBe(1);
});

/*
|--------------------------------------------------------------------------
| Functions
|--------------------------------------------------------------------------
|
| While Pest is very powerful out-of-the-box, you may have some testing code specific to your
| project that you don't want to repeat in every file. Here you can also expose helpers as
| global functions to help you to reduce the number of lines of code in your test files.
|
*/

function seedOrderSelectProduct(User $manufacturer, string $name = 'TWS Earbuds Pro'): Product
{
    $currencyId = (int) (DB::table('currencies')->where('code', 'USD')->value('id') ?? 0);

    if ($currencyId === 0) {
        $currencyId = DB::table('currencies')->insertGetId([
            'code' => 'USD',
            'name' => 'US Dollar',
            'symbol' => '$',
            'decimal_places' => 2,
            'is_active' => true,
            'sort_order' => 1,
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }

    $industryId = (int) (DB::table('industries')->where('slug', 'consumer-electronics')->value('id') ?? 0);

    if ($industryId === 0) {
        $industryId = DB::table('industries')->insertGetId([
            'name' => 'Consumer Electronics',
            'slug' => 'consumer-electronics',
            'description' => 'Consumer electronics industry',
            'featured' => false,
            'sort_order' => 1,
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }

    $subCategorySlug = 'wireless-earbuds';
    $subCategoryId = (int) (DB::table('sub_categories')->where('slug', $subCategorySlug)->value('id') ?? 0);

    if ($subCategoryId === 0) {
        $subCategoryId = DB::table('sub_categories')->insertGetId([
            'industry_id' => $industryId,
            'name' => 'Wireless Earbuds',
            'slug' => $subCategorySlug,
            'sort_order' => 1,
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }

    $slug = str($name)->slug()->toString().'-'.uniqid();

    $productId = DB::table('products')->insertGetId([
        'user_id' => $manufacturer->id,
        'currency_id' => $currencyId,
        'name' => $name,
        'description' => 'Noise cancelling earbuds',
        'slug' => $slug,
        'industry_id' => $industryId,
        'sub_category_id' => $subCategoryId,
        'view_count' => 0,
        'inquiry_count' => 0,
        'status' => 'active',
        'is_approved' => true,
        'created_at' => now(),
        'updated_at' => now(),
    ]);

    return Product::query()->findOrFail($productId);
}

/**
 * @return array{industry_id: int, sub_category_id: int, currency_id: int}
 */
function productTaxonomyIds(): array
{
    $currencyId = (int) (DB::table('currencies')->where('code', 'USD')->value('id') ?? 0);

    if ($currencyId === 0) {
        $currencyId = DB::table('currencies')->insertGetId([
            'code' => 'USD',
            'name' => 'US Dollar',
            'symbol' => '$',
            'decimal_places' => 2,
            'is_active' => true,
            'sort_order' => 1,
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }

    $industryId = (int) (DB::table('industries')->where('slug', 'consumer-electronics')->value('id') ?? 0);

    if ($industryId === 0) {
        $industryId = DB::table('industries')->insertGetId([
            'name' => 'Consumer Electronics',
            'slug' => 'consumer-electronics',
            'description' => 'Consumer electronics industry',
            'featured' => false,
            'sort_order' => 1,
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }

    $subCategoryId = (int) (DB::table('sub_categories')->where('slug', 'wireless-earbuds')->value('id') ?? 0);

    if ($subCategoryId === 0) {
        $subCategoryId = DB::table('sub_categories')->insertGetId([
            'industry_id' => $industryId,
            'name' => 'Wireless Earbuds',
            'slug' => 'wireless-earbuds',
            'sort_order' => 1,
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }

    return [
        'industry_id' => $industryId,
        'sub_category_id' => $subCategoryId,
        'currency_id' => $currencyId,
    ];
}

/**
 * @return array{buyer: User, manufacturer: User, product: Product}
 */
function seedManufacturerOrderScenario(): array
{
    $buyer = User::factory()->create();
    $manufacturer = createSubscribedManufacturer();
    $product = seedOrderSelectProduct($manufacturer);

    DB::table('companies')->insert([
        [
            'user_id' => $buyer->id,
            'company_name' => 'ABC Imports LLC',
            'country' => 'United States',
            'city' => 'Los Angeles',
            'created_at' => now(),
            'updated_at' => now(),
        ],
        [
            'user_id' => $manufacturer->id,
            'company_name' => 'Zenith Manufacturing',
            'country' => 'China',
            'city' => 'Shenzhen',
            'created_at' => now(),
            'updated_at' => now(),
        ],
    ]);

    Passport::actingAs($buyer);
    test()->postJson('/api/v1/buyer/rfqs', [
        'product_id' => $product->id,
        'quantity' => 5000,
    ])->assertCreated();

    return [
        'buyer' => $buyer,
        'manufacturer' => $manufacturer,
        'product' => $product,
    ];
}

/**
 * @param  array<int, array{key: string, input_type: string, value: string}>  $features
 * @param  array<string, mixed>  $subscriptionOverrides
 */
function attachActiveSubscription(User $manufacturer, array $features = [], array $subscriptionOverrides = []): void
{
    if ($manufacturer->subscription !== null) {
        $manufacturer->subscription->delete();
    }

    $plan = Plan::query()->create([
        'name' => 'Test Plan '.uniqid(),
        'description' => 'Test plan',
        'button_text' => 'Subscribe',
        'monthly_price' => 299,
        'yearly_price' => 2990,
        'is_popular' => false,
        'status' => true,
    ]);

    $defaultFeatures = $features !== [] ? $features : [
        ['key' => 'product_limit', 'input_type' => 'text', 'value' => '100'],
        ['key' => 'company_profile', 'input_type' => 'boolean', 'value' => '1'],
        ['key' => 'catalog_upload', 'input_type' => 'boolean', 'value' => '1'],
        ['key' => 'inquiry_rfq_inbox', 'input_type' => 'boolean', 'value' => '1'],
        ['key' => 'basic_analytics', 'input_type' => 'boolean', 'value' => '1'],
        ['key' => 'advanced_analytics', 'input_type' => 'boolean', 'value' => '1'],
        ['key' => 'certifications_section', 'input_type' => 'boolean', 'value' => '1'],
        ['key' => 'export_markets_section', 'input_type' => 'boolean', 'value' => '1'],
        ['key' => 'internal_messaging', 'input_type' => 'boolean', 'value' => '1'],
    ];

    foreach ($defaultFeatures as $featureConfig) {
        $feature = Feature::query()->firstOrCreate(
            ['key' => $featureConfig['key']],
            ['name' => ucfirst(str_replace('_', ' ', $featureConfig['key']))],
        );

        PlanFeature::query()->create([
            'plan_id' => $plan->id,
            'feature_id' => $feature->id,
            'input_type' => $featureConfig['input_type'],
            'value' => $featureConfig['value'],
        ]);
    }

    Subscription::query()->create(array_merge([
        'manufacturer_id' => $manufacturer->id,
        'plan_id' => $plan->id,
        'billing_interval' => 'month',
        'status' => SubscriptionStatus::ACTIVE->value,
        'starts_at' => now()->subDay(),
        'ends_at' => now()->addMonth(),
        'auto_renew' => true,
    ], $subscriptionOverrides));
}

/**
 * @param  array<string, mixed>  $userOverrides
 * @param  array<int, array{key: string, input_type: string, value: string}>  $features
 */
function manufacturerWithSubscription(array $userOverrides = [], array $features = [], array $subscriptionOverrides = []): User
{
    $manufacturer = User::factory()->manufacturerApproved()->create($userOverrides);
    attachActiveSubscription($manufacturer, $features, $subscriptionOverrides);

    return $manufacturer->fresh();
}

/**
 * @param  array<int, array{key: string, input_type: string, value: string}>  $features
 * @param  array<string, mixed>  $subscriptionOverrides
 */
function createSubscribedManufacturer(array $features = [], array $subscriptionOverrides = []): User
{
    $manufacturer = User::factory()->create(['role' => UserRole::MANUFACTURER->value]);
    attachActiveSubscription($manufacturer, $features, $subscriptionOverrides);

    return $manufacturer->fresh();
}
