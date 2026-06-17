<?php

use App\Enums\Api\V1\CatalogStatusEnum;
use App\Enums\CertificateStatus;
use App\Enums\CertificateTypeStatus;
use App\Enums\UserManuFactureStatus;
use App\Enums\UserStatus;
use App\Models\Catalog;
use App\Models\Certificate;
use App\Models\CertificateType;
use App\Models\Company;
use App\Models\Industry;
use App\Models\Product;
use App\Models\Review;
use App\Models\SubCategory;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

uses(RefreshDatabase::class);

function seedPublicSupplier(array $overrides = []): User
{
    $manufacturer = User::factory()->manufacturerApproved()->create(array_merge([
        'status' => UserStatus::ACTIVE,
    ], $overrides['user'] ?? []));

    Company::query()->create([
        'user_id' => $manufacturer->id,
        'company_name' => $overrides['company_name'] ?? 'TechVision Electronics',
        'slug' => $overrides['slug'] ?? 'techvision-electronics',
        'company_type' => 'manufacturer',
        'country' => $overrides['country'] ?? 'China',
        'city' => $overrides['city'] ?? 'Shenzhen',
        'street_address' => '123 Main St',
        'phone' => '+86123456789',
        'zip_code' => '518000',
        'certifications' => json_encode($overrides['certifications'] ?? ['ISO9001', 'CE']),
        'export_markets' => json_encode($overrides['export_markets'] ?? ['North America', 'Europe']),
        'short_description' => $overrides['short_description'] ?? 'Leading electronics manufacturer',
        'long_description' => $overrides['long_description'] ?? 'Full profile description',
    ]);

    if (isset($overrides['industry'])) {
        $manufacturer->load('company')->company->industries()->attach($overrides['industry']->id);
    }

    return $manufacturer->fresh(['company']);
}

test('public suppliers index returns approved manufacturers only', function (): void {
    $visible = seedPublicSupplier();

    $pending = User::factory()->manufacturer()->create([
        'status' => UserStatus::ACTIVE,
        'manufacture_status' => UserManuFactureStatus::PENDING,
    ]);

    Company::query()->create([
        'user_id' => $pending->id,
        'company_name' => 'Pending Supplier',
        'slug' => 'pending-supplier',
        'company_type' => 'manufacturer',
        'country' => 'China',
        'city' => 'Beijing',
        'street_address' => '1 Road',
        'phone' => '+86111111111',
        'zip_code' => '100000',
    ]);

    $response = $this->getJson('/api/v1/suppliers');

    $response->assertOk()
        ->assertJsonPath('success', true);

    $ids = collect($response->json('data'))->pluck('id')->all();

    expect($ids)->toContain($visible->id)
        ->and($ids)->not->toContain($pending->id);
});

test('public suppliers index supports search and slug filter fields', function (): void {
    $supplier = seedPublicSupplier([
        'company_name' => 'TechVision Electronics',
        'slug' => 'techvision-electronics',
        'country' => 'China',
    ]);

    $this->getJson('/api/v1/suppliers?search=TechVision')
        ->assertOk()
        ->assertJsonPath('data.0.slug', 'techvision-electronics')
        ->assertJsonPath('data.0.name', 'TechVision Electronics');

    $this->getJson('/api/v1/suppliers?country=China')
        ->assertOk()
        ->assertJsonPath('data.0.id', $supplier->id);

    $this->getJson('/api/v1/suppliers?country=cHiNa')
        ->assertOk()
        ->assertJsonPath('data.0.id', $supplier->id);
});

test('public suppliers index can fetch suppliers by ids for compare', function (): void {
    $first = seedPublicSupplier(['slug' => 'supplier-one']);
    $second = seedPublicSupplier(['slug' => 'supplier-two', 'company_name' => 'Second Supplier']);

    $response = $this->getJson('/api/v1/suppliers?ids='.$first->id.','.$second->id);

    $response->assertOk();

    expect($response->json('data'))->toHaveCount(2);
});

test('public suppliers map endpoint returns predefined country list with supplier counts', function (): void {
    seedPublicSupplier([
        'company_name' => 'Map China Supplier',
        'slug' => 'map-china-supplier',
        'country' => 'China',
    ]);

    seedPublicSupplier([
        'company_name' => 'Map Bangladesh Supplier',
        'slug' => 'map-bangladesh-supplier',
        'country' => 'Bangladesh',
    ]);

    $response = $this->getJson('/api/v1/suppliers/map?per_page=250');

    $response->assertOk()
        ->assertJsonPath('success', true)
        ->assertJsonPath('data.total_countries', 197);

    $countries = collect($response->json('data.countries'));
    $china = $countries->firstWhere('country_code', 'CN');
    $bangladesh = $countries->firstWhere('country_code', 'BD');

    expect($china)->not->toBeNull()
        ->and($china['suppliers_count'])->toBeGreaterThan(0)
        ->and($bangladesh)->not->toBeNull()
        ->and($bangladesh['suppliers_count'])->toBeGreaterThan(0);
});

test('public suppliers map endpoint supports group filter and pagination', function (): void {
    $response = $this->getJson('/api/v1/suppliers/map?group=Asia&per_page=10&page=1');

    $response->assertOk()
        ->assertJsonPath('success', true)
        ->assertJsonPath('data.filters.group', 'Asia')
        ->assertJsonPath('data.pagination.current_page', 1)
        ->assertJsonPath('data.pagination.per_page', 10)
        ->assertJsonPath('data.pagination.total', 49);

    $countries = collect($response->json('data.countries'));
    expect($countries)->toHaveCount(10)
        ->and($countries->every(fn (array $country) => $country['group'] === 'Asia'))->toBeTrue();
});

test('public suppliers map groups endpoint returns total groups and stats', function (): void {
    seedPublicSupplier([
        'company_name' => 'Map Group Supplier',
        'slug' => 'map-group-supplier',
        'country' => 'Bangladesh',
    ]);

    $response = $this->getJson('/api/v1/suppliers/map/groups');

    $response->assertOk()
        ->assertJsonPath('success', true)
        ->assertJsonPath('data.total_groups', 5);

    $groups = collect($response->json('data.groups'));
    $asia = $groups->firstWhere('group', 'Asia');

    expect($asia)->not->toBeNull()
        ->and($asia['country_count'])->toBe(49)
        ->and($asia['suppliers_count'])->toBeGreaterThan(0);
});

test('public suppliers map top countries endpoint returns sorted manufacturers count with pagination', function (): void {
    seedPublicSupplier([
        'company_name' => 'China One',
        'slug' => 'china-one',
        'country' => 'China',
    ]);
    seedPublicSupplier([
        'company_name' => 'China Two',
        'slug' => 'china-two',
        'country' => 'China',
    ]);
    seedPublicSupplier([
        'company_name' => 'Bangladesh One',
        'slug' => 'bangladesh-one',
        'country' => 'Bangladesh',
    ]);

    $response = $this->getJson('/api/v1/suppliers/map/top-countries?per_page=10&page=1');

    $response->assertOk()
        ->assertJsonPath('success', true)
        ->assertJsonPath('data.pagination.current_page', 1)
        ->assertJsonPath('data.pagination.per_page', 10);

    $countries = collect($response->json('data.countries'));
    $china = $countries->firstWhere('country_code', 'CN');
    $bangladesh = $countries->firstWhere('country_code', 'BD');

    expect($china)->not->toBeNull()
        ->and($bangladesh)->not->toBeNull()
        ->and($china['manufacturers_count'])->toBeGreaterThanOrEqual($bangladesh['manufacturers_count']);
});

test('public supplier show resolves by slug and numeric id', function (): void {
    $supplier = seedPublicSupplier([
        'slug' => 'techvision-electronics',
    ]);

    $this->getJson('/api/v1/suppliers/techvision-electronics')
        ->assertOk()
        ->assertJsonPath('data.slug', 'techvision-electronics')
        ->assertJsonPath('data.reviewed', true)
        ->assertJsonPath('data.company.company_name', 'TechVision Electronics');

    $this->getJson('/api/v1/suppliers/'.$supplier->id)
        ->assertOk()
        ->assertJsonPath('data.id', $supplier->id);
});

test('public supplier show returns 404 for pending manufacturer', function (): void {
    $pending = User::factory()->manufacturer()->create([
        'status' => UserStatus::ACTIVE,
        'manufacture_status' => UserManuFactureStatus::PENDING,
    ]);

    Company::query()->create([
        'user_id' => $pending->id,
        'company_name' => 'Hidden Supplier',
        'slug' => 'hidden-supplier',
        'company_type' => 'manufacturer',
        'country' => 'China',
        'city' => 'Beijing',
        'street_address' => '1 Road',
        'phone' => '+86111111111',
        'zip_code' => '100000',
    ]);

    $this->getJson('/api/v1/suppliers/hidden-supplier')
        ->assertNotFound();
});

test('public supplier products endpoint scopes to supplier catalog', function (): void {
    $supplier = seedPublicSupplier();
    $product = seedPublicCatalogProductForSupplier($supplier);

    $this->getJson('/api/v1/suppliers/techvision-electronics/products')
        ->assertOk()
        ->assertJsonPath('data.0.id', $product->id);
});

test('public supplier reviews endpoint returns stats and paginated reviews', function (): void {
    $supplier = seedPublicSupplier();
    $buyer = User::factory()->create();
    $product = seedPublicCatalogProductForSupplier($supplier);

    Review::query()->create([
        'user_id' => $supplier->id,
        'product_id' => $product->id,
        'reviewer_id' => $buyer->id,
        'rating' => 5,
        'title' => 'Great supplier',
        'comment' => 'Excellent quality',
    ]);

    $response = $this->getJson('/api/v1/suppliers/techvision-electronics/reviews');

    $response->assertOk()
        ->assertJsonPath('data.review_stats.total_reviews', 1)
        ->assertJsonPath('data.review_stats.average_rating', 5)
        ->assertJsonPath('data.reviews.0.title', 'Great supplier');
});

test('public supplier catalogs endpoint returns only active catalogs', function (): void {
    $supplier = seedPublicSupplier();

    Catalog::query()->create([
        'user_id' => $supplier->id,
        'name' => 'Active Catalog',
        'file_path' => 'catalogs/active.pdf',
        'file_size' => 1024,
        'status' => CatalogStatusEnum::ACTIVE->value,
    ]);

    Catalog::query()->create([
        'user_id' => $supplier->id,
        'name' => 'Draft Catalog',
        'file_path' => 'catalogs/draft.pdf',
        'file_size' => 1024,
        'status' => CatalogStatusEnum::DRAFT->value,
    ]);

    $response = $this->getJson('/api/v1/suppliers/techvision-electronics/catalogs');

    $response->assertOk();

    expect($response->json('data'))->toHaveCount(1)
        ->and($response->json('data.0.name'))->toBe('Active Catalog');
});

test('public supplier certifications endpoint merges profile and valid uploads', function (): void {
    $supplier = seedPublicSupplier([
        'certifications' => ['ISO9001'],
    ]);

    $type = CertificateType::query()->create([
        'name' => 'CE Mark',
        'slug' => 'ce-mark-'.uniqid(),
        'status' => CertificateTypeStatus::ACTIVE->value,
    ]);

    Certificate::query()->create([
        'user_id' => $supplier->id,
        'certificate_type_id' => $type->id,
        'issuing_body' => 'EU',
        'certificate_number' => 'CE-123',
        'issue_date' => now()->subYear()->toDateString(),
        'expiry_date' => now()->addYear()->toDateString(),
        'certificate_pdf' => 'certificates/ce-123.pdf',
        'status' => CertificateStatus::VALID->value,
    ]);

    Certificate::query()->create([
        'user_id' => $supplier->id,
        'certificate_type_id' => $type->id,
        'issuing_body' => 'EU',
        'certificate_number' => 'CE-OLD',
        'issue_date' => now()->subYears(2)->toDateString(),
        'expiry_date' => now()->subYear()->toDateString(),
        'certificate_pdf' => 'certificates/ce-old.pdf',
        'status' => CertificateStatus::EXPIRED->value,
    ]);

    $response = $this->getJson('/api/v1/suppliers/techvision-electronics/certifications');

    $response->assertOk()
        ->assertJsonPath('data.profile_certifications.0', 'ISO9001');

    expect($response->json('data.uploaded_certificates'))->toHaveCount(1);
});

test('company slug is generated on manufacturer profile update', function (): void {
    $manufacturer = User::factory()->manufacturerApproved()->create([
        'status' => UserStatus::ACTIVE,
    ]);

  Company::query()->create([
        'user_id' => $manufacturer->id,
        'company_name' => 'Alpha Manufacturing',
        'company_type' => 'manufacturer',
        'country' => 'China',
        'city' => 'Shanghai',
        'street_address' => '1 Road',
        'phone' => '+86111111111',
        'zip_code' => '200000',
    ]);

    $this->actingAs($manufacturer, 'api')
        ->putJson('/api/v1/manufacturer/profile/update', [
            'company_name' => 'Beta Manufacturing Co',
            'company_type' => 'manufacturer',
            'country' => 'China',
            'city' => 'Shanghai',
            'street_address' => '2 Road',
            'phone' => '+86222222222',
            'zip_code' => '200000',
        ])
        ->assertOk();

    expect($manufacturer->fresh()->company?->slug)->toBe('beta-manufacturing-co');
});

function seedPublicCatalogProductForSupplier(User $supplier, array $overrides = []): Product
{
    $industry = Industry::query()->create([
        'name' => $overrides['industry_name'] ?? 'Electronics',
        'slug' => $overrides['industry_slug'] ?? ('electronics-'.uniqid()),
    ]);

    $subCategory = SubCategory::query()->create([
        'industry_id' => $industry->id,
        'name' => $overrides['sub_category_name'] ?? 'Components',
        'slug' => $overrides['sub_category_slug'] ?? ('components-'.uniqid()),
    ]);

    $product = Product::query()->create([
        'user_id' => $supplier->id,
        'name' => $overrides['name'] ?? 'Wireless Earbuds',
        'slug' => $overrides['slug'] ?? 'wireless-earbuds-'.uniqid(),
        'description' => $overrides['description'] ?? 'Public catalog product',
        'industry_id' => $industry->id,
        'sub_category_id' => $subCategory->id,
        'status' => 'active',
        'is_approved' => true,
        'inquiry_count' => $overrides['inquiry_count'] ?? 10,
        'view_count' => $overrides['view_count'] ?? 5,
    ]);

    $product->pricingQuantities()->create([
        'min_price' => 100,
        'max_price' => 200,
        'minimum_order_quantity' => 500,
        'unit' => 'piece',
        'lead_time' => '15',
        'currency_id' => 1,
        'production_capacity' => 1000,
        'production_duration' => '30',
        'production_unit' => 'Piece',
    ]);

    return $product->fresh();
}
