<?php

declare(strict_types=1);

use App\Models\RfqSubmission;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Laravel\Passport\ClientRepository;
use Laravel\Passport\Passport;

uses(RefreshDatabase::class);

beforeEach(function (): void {
    app(ClientRepository::class)->createPersonalAccessGrantClient(
        name: 'Test Personal Access Client',
        provider: config('auth.guards.api.provider')
    );
});

test('manufacturer can fetch own order product select options', function (): void {
    $manufacturer = User::factory()->manufacturerApproved()->create();
    $otherManufacturer = User::factory()->manufacturerApproved()->create();

    DB::table('companies')->insert([
        [
            'user_id' => $manufacturer->id,
            'company_name' => 'Zenith Manufacturing',
            'country' => 'China',
            'city' => 'Shenzhen',
            'created_at' => now(),
            'updated_at' => now(),
        ],
        [
            'user_id' => $otherManufacturer->id,
            'company_name' => 'Other Factory',
            'country' => 'China',
            'city' => 'Guangzhou',
            'created_at' => now(),
            'updated_at' => now(),
        ],
    ]);

    $product = seedOrderSelectProduct($manufacturer);
    seedOrderSelectProduct($otherManufacturer, 'Other Product');

    Passport::actingAs($manufacturer);

    $response = $this->getJson('/api/v1/manufacturer/orders/select/products?search=TWS');

    $response->assertOk()
        ->assertJsonPath('success', true)
        ->assertJsonCount(1, 'data')
        ->assertJsonPath('data.0.value', $product->id)
        ->assertJsonPath('data.0.label', 'TWS Earbuds Pro')
        ->assertJsonPath('data.0.manufacturer_id', $manufacturer->id)
        ->assertJsonPath('data.0.manufacturer_name', 'Zenith Manufacturing');
});

test('manufacturer can fetch order buyer select options for product rfqs', function (): void {
    $buyer = User::factory()->create();
    $otherBuyer = User::factory()->create();
    $manufacturer = User::factory()->manufacturerApproved()->create();
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
    $this->postJson('/api/v1/buyer/rfqs', [
        'product_id' => $product->id,
        'quantity' => 5000,
    ])->assertCreated();

    Passport::actingAs($otherBuyer);
    $this->postJson('/api/v1/buyer/rfqs', [
        'product_id' => $product->id,
        'quantity' => 1000,
    ])->assertCreated();

    Passport::actingAs($manufacturer);

    $response = $this->getJson("/api/v1/manufacturer/orders/select/buyers?product_id={$product->id}&search=ABC");

    $response->assertOk()
        ->assertJsonPath('success', true)
        ->assertJsonCount(1, 'data')
        ->assertJsonPath('data.0.value', $buyer->id)
        ->assertJsonPath('data.0.label', 'ABC Imports LLC - '.$buyer->first_name.' '.$buyer->last_name)
        ->assertJsonPath('data.0.email', $buyer->email)
        ->assertJsonPath('data.0.company_name', 'ABC Imports LLC')
        ->assertJsonPath('data.0.location', 'United States');
});

test('manufacturer order buyer select excludes buyers without rfq for product', function (): void {
    $buyer = User::factory()->create();
    $manufacturer = User::factory()->manufacturerApproved()->create();
    $product = seedOrderSelectProduct($manufacturer);
    $otherProduct = seedOrderSelectProduct($manufacturer, 'Another Product');

    DB::table('companies')->insert([
        'user_id' => $manufacturer->id,
        'company_name' => 'Zenith Manufacturing',
        'country' => 'China',
        'city' => 'Shenzhen',
        'created_at' => now(),
        'updated_at' => now(),
    ]);

    Passport::actingAs($buyer);
    $this->postJson('/api/v1/buyer/rfqs', [
        'product_id' => $otherProduct->id,
        'quantity' => 500,
    ])->assertCreated();

    Passport::actingAs($manufacturer);

    $this->getJson("/api/v1/manufacturer/orders/select/buyers?product_id={$product->id}")
        ->assertOk()
        ->assertJsonPath('success', true)
        ->assertJsonCount(0, 'data');

    expect(RfqSubmission::query()->where('product_id', $product->id)->count())->toBe(0);
});

test('manufacturer order select endpoints require manufacturer role', function (): void {
    $buyer = User::factory()->create();
    $manufacturer = User::factory()->manufacturerApproved()->create();
    $product = seedOrderSelectProduct($manufacturer);

    Passport::actingAs($buyer);

    $this->getJson('/api/v1/manufacturer/orders/select/products')->assertForbidden();
    $this->getJson("/api/v1/manufacturer/orders/select/buyers?product_id={$product->id}")->assertForbidden();
});

test('manufacturer order buyer select requires product id', function (): void {
    $manufacturer = User::factory()->manufacturerApproved()->create();

    Passport::actingAs($manufacturer);

    $this->getJson('/api/v1/manufacturer/orders/select/buyers')
        ->assertUnprocessable()
        ->assertJsonValidationErrors(['product_id']);
});
