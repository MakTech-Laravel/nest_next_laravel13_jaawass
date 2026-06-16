<?php

use App\Models\Product;
use App\Models\User;
use Illuminate\Support\Facades\DB;
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
