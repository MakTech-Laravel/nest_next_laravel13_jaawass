<?php

use App\Enums\ReviewStatus;
use App\Models\Company;
use App\Models\Industry;
use App\Models\Product;
use App\Models\Review;
use App\Models\SubCategory;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

uses(RefreshDatabase::class);

function seedPublicCatalogProduct(array $overrides = []): Product
{
    $manufacturer = User::factory()->manufacturer()->create();

    Company::query()->create([
        'user_id' => $manufacturer->id,
        'company_name' => $overrides['company_name'] ?? 'TechVision Electronics',
        'company_type' => 'manufacturer',
        'country' => $overrides['country'] ?? 'China',
        'city' => $overrides['city'] ?? 'Shenzhen',
        'street_address' => '123 Main St',
        'phone' => '+86123456789',
        'zip_code' => '518000',
        'certifications' => json_encode($overrides['certifications'] ?? ['ISO9001', 'CE']),
        'export_markets' => json_encode($overrides['export_markets'] ?? ['North America', 'Europe']),
    ]);

    if (isset($overrides['industry_id'], $overrides['sub_category_id'])) {
        $industryId = $overrides['industry_id'];
        $subCategoryId = $overrides['sub_category_id'];
    } else {
        $industry = Industry::query()->create([
            'name' => $overrides['industry_name'] ?? 'Electronics',
            'slug' => $overrides['industry_slug'] ?? ('electronics-'.uniqid()),
        ]);

        $subCategory = SubCategory::query()->create([
            'industry_id' => $industry->id,
            'name' => $overrides['sub_category_name'] ?? 'Components',
            'slug' => $overrides['sub_category_slug'] ?? ('components-'.uniqid()),
        ]);

        $industryId = $industry->id;
        $subCategoryId = $subCategory->id;
    }

    $product = Product::query()->create([
        'user_id' => $manufacturer->id,
        'name' => $overrides['name'] ?? 'Public Product',
        'slug' => $overrides['slug'] ?? 'public-product-'.uniqid(),
        'description' => $overrides['description'] ?? 'Public catalog product',
        'industry_id' => $industryId,
        'sub_category_id' => $subCategoryId,
        'status' => 'active',
        'is_approved' => true,
        'inquiry_count' => $overrides['inquiry_count'] ?? 10,
        'view_count' => $overrides['view_count'] ?? 5,
    ]);

    $product->pricingQuantities()->create([
        'min_price' => $overrides['min_price'] ?? 100,
        'max_price' => $overrides['max_price'] ?? 200,
        'minimum_order_quantity' => $overrides['minimum_order_quantity'] ?? 500,
        'unit' => 'piece',
        'lead_time' => '15',
        'currency_id' => 1,
        'production_capacity' => 1000,
        'production_duration' => '30',
        'production_unit' => 'Piece',
    ]);

    return $product->fresh();
}

test('public products index is paginated and returns reviews', function (): void {
    $product = seedPublicCatalogProduct();

    Review::query()->create([
        'user_id' => $product->user_id,
        'product_id' => $product->id,
        'reviewer_id' => User::factory()->create()->id,
        'rating' => 5,
        'title' => 'Excellent',
        'comment' => 'Great quality',
        'status' => ReviewStatus::PUBLISHED->value,
    ]);

    /** @var TestCase $this */
    $response = $this->getJson('/api/v1/products?per_page=10&page=1');

    $response->assertOk()
        ->assertJsonPath('success', true)
        ->assertJsonPath('meta.current_page', 1)
        ->assertJsonPath('meta.per_page', 10)
        ->assertJsonPath('meta.total', 1)
        ->assertJsonPath('data.0.id', $product->id)
        ->assertJsonPath('data.0.supplier_name', 'TechVision Electronics')
        ->assertJsonPath('data.0.supplier.name', 'TechVision Electronics')
        ->assertJsonPath('data.0.review_stats.total_reviews', 1)
        ->assertJsonCount(1, 'data.0.reviews');
});

test('public products index filters by search category supplier country and moq', function (): void {
    seedPublicCatalogProduct([
        'name' => 'Steel Valve',
        'slug' => 'steel-valve',
        'industry_slug' => 'machinery-'.uniqid(),
        'industry_name' => 'Machinery',
        'sub_category_slug' => 'valves-'.uniqid(),
        'sub_category_name' => 'Valves',
        'country' => 'Germany',
        'city' => 'Berlin',
        'company_name' => 'GlobalFab Machinery',
        'minimum_order_quantity' => 50,
    ]);

    $electronicsIndustry = Industry::query()->create([
        'name' => 'Electronics',
        'slug' => 'electronics-'.uniqid(),
    ]);

    $electronicsSubCategory = SubCategory::query()->create([
        'industry_id' => $electronicsIndustry->id,
        'name' => 'Audio',
        'slug' => 'audio-'.uniqid(),
    ]);

    seedPublicCatalogProduct([
        'name' => 'Wireless Earbuds',
        'slug' => 'wireless-earbuds',
        'industry_id' => $electronicsIndustry->id,
        'sub_category_id' => $electronicsSubCategory->id,
        'company_name' => 'TechVision Electronics',
        'country' => 'China',
        'minimum_order_quantity' => 1000,
    ]);

    /** @var TestCase $this */
    $this->getJson('/api/v1/products?search=earbuds')
        ->assertOk()
        ->assertJsonPath('meta.total', 1)
        ->assertJsonPath('data.0.slug', 'wireless-earbuds');

    $this->getJson('/api/v1/products?category_slug='.$electronicsIndustry->slug)
        ->assertOk()
        ->assertJsonPath('meta.total', 1)
        ->assertJsonPath('data.0.slug', 'wireless-earbuds');

    $this->getJson('/api/v1/products?supplier=GlobalFab')
        ->assertOk()
        ->assertJsonPath('meta.total', 1)
        ->assertJsonPath('data.0.slug', 'steel-valve');

    $this->getJson('/api/v1/products?country=China')
        ->assertOk()
        ->assertJsonPath('meta.total', 1)
        ->assertJsonPath('data.0.slug', 'wireless-earbuds');

    $this->getJson('/api/v1/products?moq_range=1-100')
        ->assertOk()
        ->assertJsonPath('meta.total', 1)
        ->assertJsonPath('data.0.slug', 'steel-valve');
});

test('public products index sorts by price low to high', function (): void {
    seedPublicCatalogProduct([
        'slug' => 'expensive-product',
        'min_price' => 500,
        'max_price' => 600,
    ]);

    seedPublicCatalogProduct([
        'slug' => 'cheap-product',
        'min_price' => 50,
        'max_price' => 80,
    ]);

    /** @var TestCase $this */
    $this->getJson('/api/v1/products?sort=price-low')
        ->assertOk()
        ->assertJsonPath('data.0.slug', 'cheap-product')
        ->assertJsonPath('data.1.slug', 'expensive-product');
});

test('public product show returns supplier name', function (): void {
    $product = seedPublicCatalogProduct([
        'slug' => 'supplier-show-product',
        'company_name' => 'GlobalFab Machinery',
    ]);

    /** @var TestCase $this */
    $this->getJson("/api/v1/products/{$product->id}")
        ->assertOk()
        ->assertJsonPath('data.supplier_name', 'GlobalFab Machinery')
        ->assertJsonPath('data.supplier.name', 'GlobalFab Machinery')
        ->assertJsonPath('data.supplier.country', 'China');
});

test('supplier name falls back to manufacturer user name when company profile is missing', function (): void {
    $manufacturer = User::factory()->manufacturer()->create([
        'first_name' => 'Jane',
        'last_name' => 'Manufacturer',
    ]);

    $industry = Industry::query()->create([
        'name' => 'Test Industry',
        'slug' => 'test-industry-'.uniqid(),
    ]);

    $subCategory = SubCategory::query()->create([
        'industry_id' => $industry->id,
        'name' => 'Test Sub',
        'slug' => 'test-sub-'.uniqid(),
    ]);

    $product = Product::query()->create([
        'user_id' => $manufacturer->id,
        'name' => 'No Company Product',
        'slug' => 'no-company-product',
        'description' => 'Product without company profile',
        'industry_id' => $industry->id,
        'sub_category_id' => $subCategory->id,
        'status' => 'active',
        'is_approved' => true,
    ]);

    /** @var TestCase $this */
    $this->getJson("/api/v1/products/{$product->id}")
        ->assertOk()
        ->assertJsonPath('data.supplier_name', 'Jane Manufacturer')
        ->assertJsonPath('data.supplier.name', 'Jane Manufacturer');
});

test('unapproved products are excluded from public index', function (): void {
    $approved = seedPublicCatalogProduct(['slug' => 'approved-product']);

    Product::query()->create([
        'user_id' => $approved->user_id,
        'name' => 'Hidden Product',
        'slug' => 'hidden-product',
        'description' => 'Not approved',
        'industry_id' => $approved->industry_id,
        'sub_category_id' => $approved->sub_category_id,
        'status' => 'active',
        'is_approved' => false,
    ]);

    /** @var TestCase $this */
    $this->getJson('/api/v1/products')
        ->assertOk()
        ->assertJsonPath('meta.total', 1)
        ->assertJsonPath('data.0.slug', 'approved-product');
});
