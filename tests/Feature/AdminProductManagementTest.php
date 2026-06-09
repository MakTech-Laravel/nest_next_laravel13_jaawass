<?php

use App\Enums\UserRole;
use App\Models\Industry;
use App\Models\Product;
use App\Models\SubCategory;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Passport\ClientRepository;
use Tests\TestCase;

uses(RefreshDatabase::class);

beforeEach(function (): void {
    app(ClientRepository::class)->createPersonalAccessGrantClient(
        name: 'Test Personal Access Client',
        provider: config('auth.guards.api.provider')
    );
});

test('admin can filter products by is_approved and search by product name', function (): void {
    $admin = User::factory()->create([
        'role' => UserRole::ADMIN->value,
    ]);

    $industry = Industry::query()->create([
        'name' => 'Metals',
        'slug' => 'metals',
    ]);

    $subCategory = SubCategory::query()->create([
        'industry_id' => $industry->id,
        'name' => 'Pipes',
        'slug' => 'pipes',
    ]);

    $manufacturer = User::factory()->manufacturer()->create();

    $targetProduct = Product::query()->create([
        'user_id' => $manufacturer->id,
        'name' => 'Steel Pipe',
        'slug' => 'steel-pipe',
        'industry_id' => $industry->id,
        'sub_category_id' => $subCategory->id,
        'is_approved' => true,
    ]);

    Product::query()->create([
        'user_id' => $manufacturer->id,
        'name' => 'Steel Nuts',
        'slug' => 'steel-nuts',
        'industry_id' => $industry->id,
        'sub_category_id' => $subCategory->id,
        'is_approved' => false,
    ]);

    Product::query()->create([
        'user_id' => $manufacturer->id,
        'name' => 'Copper Wire',
        'slug' => 'copper-wire',
        'industry_id' => $industry->id,
        'sub_category_id' => $subCategory->id,
        'is_approved' => true,
    ]);

    $token = $admin->createToken('test')->accessToken;

    /** @var TestCase $this */
    $this->withHeader('Authorization', 'Bearer '.$token)
        ->getJson('/api/v1/admin/products?is_approved=1&search=Steel')
        ->assertOk()
        ->assertJsonPath('message', __('api.products_fetched_successfully'))
        ->assertJsonCount(1, 'data')
        ->assertJsonPath('data.0.id', $targetProduct->id)
        ->assertJsonPath('data.0.is_approved', true);
});

test('admin can update product approval status', function (): void {
    $admin = User::factory()->create([
        'role' => UserRole::ADMIN->value,
    ]);

    $industry = Industry::query()->create([
        'name' => 'Chemicals',
        'slug' => 'chemicals',
    ]);

    $subCategory = SubCategory::query()->create([
        'industry_id' => $industry->id,
        'name' => 'Organic',
        'slug' => 'organic',
    ]);

    $manufacturer = User::factory()->manufacturer()->create();

    $product = Product::query()->create([
        'user_id' => $manufacturer->id,
        'name' => 'Organic Solvent',
        'slug' => 'organic-solvent',
        'industry_id' => $industry->id,
        'sub_category_id' => $subCategory->id,
        'is_approved' => false,
    ]);

    $token = $admin->createToken('test')->accessToken;

    /** @var TestCase $this */
    $this->withHeader('Authorization', 'Bearer '.$token)
        ->patchJson("/api/v1/admin/products/{$product->id}/approval-status", [
            'is_approved' => true,
        ])
        ->assertOk()
        ->assertJsonPath('message', __('api.product_approval_status_updated_successfully'))
        ->assertJsonPath('data.id', $product->id)
        ->assertJsonPath('data.is_approved', true);

    expect($product->fresh()->is_approved)->toBeTrue();
});
