<?php

use App\Models\Company;
use App\Models\Industry;
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
});

test('buyer cannot save product when manufacturer has no active subscription', function (): void {
    $buyer = User::factory()->create();
    $manufacturer = User::factory()->manufacturer()->create();

    $industry = Industry::query()->create([
        'name' => 'Save Test Industry',
        'slug' => 'save-test-industry-'.uniqid(),
    ]);

    $subCategory = SubCategory::query()->create([
        'industry_id' => $industry->id,
        'name' => 'Save Test Sub',
        'slug' => 'save-test-sub-'.uniqid(),
    ]);

    $product = Product::query()->create([
        'user_id' => $manufacturer->id,
        'name' => 'Unsubscribed Save Product',
        'slug' => 'unsubscribed-save-product',
        'description' => 'No subscription',
        'industry_id' => $industry->id,
        'sub_category_id' => $subCategory->id,
        'status' => 'active',
        'is_approved' => true,
    ]);

    Passport::actingAs($buyer);

    $this->postJson('/api/v1/buyer/products/saved', [
        'product_id' => $product->id,
    ])
        ->assertUnprocessable()
        ->assertJsonValidationErrors(['product_id']);
});

test('buyer saved products list excludes products without subscribed manufacturer', function (): void {
    $buyer = User::factory()->create();
    $subscribedProduct = seedPublicCatalogProduct(['slug' => 'subscribed-saved-product']);

    $unsubscribedManufacturer = User::factory()->manufacturer()->create();
    $hiddenProduct = Product::query()->create([
        'user_id' => $unsubscribedManufacturer->id,
        'name' => 'Hidden Saved Product',
        'slug' => 'hidden-saved-product',
        'description' => 'No subscription',
        'industry_id' => $subscribedProduct->industry_id,
        'sub_category_id' => $subscribedProduct->sub_category_id,
        'status' => 'active',
        'is_approved' => true,
    ]);

    $buyer->savedProducts()->attach([$subscribedProduct->id, $hiddenProduct->id]);

    Passport::actingAs($buyer);

    $this->getJson('/api/v1/buyer/products/saved')
        ->assertOk()
        ->assertJsonCount(1, 'data')
        ->assertJsonPath('data.0.id', $subscribedProduct->id);
});

test('buyer cannot submit rfq for product without subscribed manufacturer', function (): void {
    $buyer = User::factory()->create();
    $manufacturer = User::factory()->manufacturer()->create();

    Company::query()->create([
        'user_id' => $manufacturer->id,
        'company_name' => 'No Subscription Co',
        'company_type' => 'manufacturer',
        'country' => 'China',
        'city' => 'Shanghai',
        'street_address' => '123 Main St',
        'phone' => '+86123456789',
        'zip_code' => '200000',
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
        'name' => 'Unsubscribed RFQ Product',
        'slug' => 'unsubscribed-rfq-product',
        'description' => 'No subscription',
        'industry_id' => $industry->id,
        'sub_category_id' => $subCategory->id,
        'status' => 'active',
        'is_approved' => true,
    ]);

    Passport::actingAs($buyer);

    $this->postJson('/api/v1/buyer/rfqs', [
        'product_id' => $product->id,
        'quantity' => 100,
    ])
        ->assertUnprocessable()
        ->assertJsonValidationErrors(['product_id']);
});
