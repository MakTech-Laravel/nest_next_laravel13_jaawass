<?php

use App\Models\Industry;
use App\Models\Product;
use App\Models\ProductTranslation;
use App\Models\SubCategory;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

uses(RefreshDatabase::class);

test('products index returns spanish name and description when Accept-Language is es', function () {
    $product = Product::factory()->create([
        'name' => 'Canonical English Name',
        'description' => 'Canonical English description.',
    ]);

    ProductTranslation::query()->create([
        'product_id' => $product->id,
        'locale' => 'en',
        'name' => 'English Product',
        'description' => 'English description.',
    ]);

    ProductTranslation::query()->create([
        'product_id' => $product->id,
        'locale' => 'es',
        'name' => 'Producto en español',
        'description' => 'Descripción en español.',
    ]);

    /** @var TestCase $this */
    $response = $this->getJson('/api/v1/products', [
        'Accept-Language' => 'es',
    ]);

    $response->assertOk()
        ->assertJsonPath('message', __('api.products_fetched_successfully', [], 'es'))
        ->assertJsonPath('data.0.name', 'Producto en español')
        ->assertJsonPath('data.0.description', 'Descripción en español.');
});

test('products index returns english translation when Accept-Language is en', function () {
    $product = Product::factory()->create([
        'name' => 'Canonical English Name',
        'description' => 'Canonical English description.',
    ]);

    ProductTranslation::query()->create([
        'product_id' => $product->id,
        'locale' => 'en',
        'name' => 'English Product',
        'description' => 'English description.',
    ]);

    ProductTranslation::query()->create([
        'product_id' => $product->id,
        'locale' => 'es',
        'name' => 'Producto en español',
        'description' => 'Descripción en español.',
    ]);

    /** @var TestCase $this */
    $response = $this->getJson('/api/v1/products', [
        'Accept-Language' => 'en',
    ]);

    $response->assertOk()
        ->assertJsonPath('message', __('api.products_fetched_successfully', [], 'en'))
        ->assertJsonPath('data.0.name', 'English Product')
        ->assertJsonPath('data.0.description', 'English description.');
});

test('products index falls back to english translation when spanish row is missing', function () {
    $product = Product::factory()->create([
        'name' => 'Fallback Base Name',
        'description' => 'Fallback base description.',
    ]);

    ProductTranslation::query()->create([
        'product_id' => $product->id,
        'locale' => 'en',
        'name' => 'Only English Row',
        'description' => 'Only English row description.',
    ]);

    /** @var TestCase $this */
    $response = $this->getJson('/api/v1/products', [
        'Accept-Language' => 'es',
    ]);

    $response->assertOk()
        ->assertJsonPath('data.0.name', 'Only English Row')
        ->assertJsonPath('data.0.description', 'Only English row description.');
});

test('products by category endpoint returns only selected category products', function () {
    $industryA = Industry::query()->create([
        'name' => 'Category A',
        'slug' => 'category-a',
    ]);

    $industryB = Industry::query()->create([
        'name' => 'Category B',
        'slug' => 'category-b',
    ]);

    $subCategoryA = SubCategory::query()->create([
        'industry_id' => $industryA->id,
        'name' => 'Sub A',
        'slug' => 'sub-a',
    ]);

    $subCategoryB = SubCategory::query()->create([
        'industry_id' => $industryB->id,
        'name' => 'Sub B',
        'slug' => 'sub-b',
    ]);

    $user = User::factory()->create();

    Product::query()->create([
        'user_id' => $user->id,
        'name' => 'Product A',
        'slug' => 'product-a',
        'industry_id' => $industryA->id,
        'sub_category_id' => $subCategoryA->id,
        'status' => 'active',
    ]);

    Product::query()->create([
        'user_id' => $user->id,
        'name' => 'Product B',
        'slug' => 'product-b',
        'industry_id' => $industryB->id,
        'sub_category_id' => $subCategoryB->id,
        'status' => 'active',
    ]);

    /** @var TestCase $this */
    $response = $this->getJson("/api/v1/products/category/{$industryA->id}");

    $response->assertOk()
        ->assertJsonCount(1, 'data')
        ->assertJsonPath('data.0.name', 'Product A');
});

test('products by sub category endpoint returns only selected sub category products', function () {
    $industryA = Industry::query()->create([
        'name' => 'Category A',
        'slug' => 'category-a-sub',
    ]);

    $industryB = Industry::query()->create([
        'name' => 'Category B',
        'slug' => 'category-b-sub',
    ]);

    $subCategoryA1 = SubCategory::query()->create([
        'industry_id' => $industryA->id,
        'name' => 'Sub A1',
        'slug' => 'sub-a1',
    ]);

    $subCategoryA2 = SubCategory::query()->create([
        'industry_id' => $industryA->id,
        'name' => 'Sub A2',
        'slug' => 'sub-a2',
    ]);

    $subCategoryB1 = SubCategory::query()->create([
        'industry_id' => $industryB->id,
        'name' => 'Sub B1',
        'slug' => 'sub-b1',
    ]);

    $user = User::factory()->create();

    Product::query()->create([
        'user_id' => $user->id,
        'name' => 'Product A1',
        'slug' => 'product-a1',
        'industry_id' => $industryA->id,
        'sub_category_id' => $subCategoryA1->id,
        'status' => 'active',
    ]);

    Product::query()->create([
        'user_id' => $user->id,
        'name' => 'Product A2',
        'slug' => 'product-a2',
        'industry_id' => $industryA->id,
        'sub_category_id' => $subCategoryA2->id,
        'status' => 'active',
    ]);

    Product::query()->create([
        'user_id' => $user->id,
        'name' => 'Product B1',
        'slug' => 'product-b1',
        'industry_id' => $industryB->id,
        'sub_category_id' => $subCategoryB1->id,
        'status' => 'active',
    ]);

    /** @var TestCase $this */
    $response = $this->getJson("/api/v1/products/sub-category/{$subCategoryA1->id}");

    $response->assertOk()
        ->assertJsonCount(1, 'data')
        ->assertJsonPath('data.0.name', 'Product A1');
});
