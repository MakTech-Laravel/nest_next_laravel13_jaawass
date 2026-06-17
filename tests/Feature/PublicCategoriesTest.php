<?php

declare(strict_types=1);

use App\Models\Company;
use App\Models\Industry;
use App\Models\Product;
use App\Models\SubCategory;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

function createCategoryProduct(
    int $userId,
    int $industryId,
    int $subCategoryId,
    string $slug,
    string $status = 'active',
): Product {
    return Product::query()->create([
        'user_id' => $userId,
        'name' => 'Product '.$slug,
        'slug' => $slug,
        'description' => 'Test product',
        'industry_id' => $industryId,
        'sub_category_id' => $subCategoryId,
        'status' => $status,
        'is_approved' => false,
    ]);
}

test('public categories index returns supplier count from unique manufacturers with products', function (): void {
    $electronics = Industry::query()->create([
        'name' => 'Electronics',
        'slug' => 'electronics',
    ]);

    $textiles = Industry::query()->create([
        'name' => 'Textiles',
        'slug' => 'textiles',
    ]);

    $electronicsSub = SubCategory::query()->create([
        'industry_id' => $electronics->id,
        'name' => 'Components',
        'slug' => 'components',
    ]);

    $textilesSub = SubCategory::query()->create([
        'industry_id' => $textiles->id,
        'name' => 'Fabric',
        'slug' => 'fabric',
    ]);

    $manufacturerA = User::factory()->manufacturer()->create();
    $manufacturerB = User::factory()->manufacturer()->create();
    $manufacturerC = User::factory()->manufacturer()->create();
    $buyer = User::factory()->create();

    Company::query()->create([
        'user_id' => $manufacturerC->id,
        'company_name' => 'Profile Only Co',
        'company_type' => 'manufacturer',
        'country' => 'China',
        'city' => 'Shenzhen',
        'street_address' => '123 Main St',
        'phone' => '+86123456789',
        'zip_code' => '518000',
    ])->industries()->attach($electronics->id);

    createCategoryProduct($manufacturerA->id, $electronics->id, $electronicsSub->id, 'elec-a-1', 'active');
    createCategoryProduct($manufacturerA->id, $electronics->id, $electronicsSub->id, 'elec-a-2', 'draft');
    createCategoryProduct($manufacturerB->id, $electronics->id, $electronicsSub->id, 'elec-b-1', 'active');
    createCategoryProduct($manufacturerA->id, $textiles->id, $textilesSub->id, 'text-a-1', 'active');
    createCategoryProduct($buyer->id, $electronics->id, $electronicsSub->id, 'buyer-prod', 'active');

    $this->getJson('/api/v1/categories')
        ->assertOk()
        ->assertJsonPath('success', true)
        ->assertJsonPath('data.0.slug', 'electronics')
        ->assertJsonPath('data.0.supplier_count', 2)
        ->assertJsonPath('data.1.slug', 'textiles')
        ->assertJsonPath('data.1.supplier_count', 1);
});
