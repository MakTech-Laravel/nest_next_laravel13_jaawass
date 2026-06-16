<?php

declare(strict_types=1);

use App\Models\Order;
use App\Models\Product;
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

/**
 * @return array{buyer: User, manufacturer: User, product: Product}
 */
function seedManufacturerOrderScenario(): array
{
    $buyer = User::factory()->create();
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

test('manufacturer can create order for connected buyer and product', function (): void {
    ['buyer' => $buyer, 'manufacturer' => $manufacturer, 'product' => $product] = seedManufacturerOrderScenario();

    Passport::actingAs($manufacturer);

    $response = $this->postJson('/api/v1/manufacturer/orders/create', [
        'buyer_id' => $buyer->id,
        'product_id' => $product->id,
        'title' => 'Premium ceramic mugs - 350ml',
        'quantity' => 5000,
        'quantity_unit' => 'pieces',
        'total_amount' => 12500.50,
        'currency_code' => 'USD',
        'estimated_delivery_at' => now()->addDays(30)->toDateString(),
        'production_lead' => '30 days',
        'payment_terms' => '50% upfront, 50% on delivery',
        'shipping_terms' => 'FOB Shanghai',
        'destination' => 'Los Angeles, USA',
        'notes' => 'Any additional context about this order.',
    ]);

    $response->assertCreated()
        ->assertJsonPath('success', true)
        ->assertJsonPath('data.title', 'Premium ceramic mugs - 350ml')
        ->assertJsonPath('data.buyer.id', $buyer->id)
        ->assertJsonPath('data.product.id', $product->id)
        ->assertJsonPath('data.total_amount', '12500.50')
        ->assertJsonPath('data.currency_code', 'USD');

    expect(Order::query()->count())->toBe(1)
        ->and(Order::query()->firstOrFail()->manufacturer_id)->toBe($manufacturer->id);
});

test('manufacturer cannot create order when buyer has no rfq for product', function (): void {
    $buyer = User::factory()->create();
    $manufacturer = User::factory()->manufacturerApproved()->create();
    $product = seedOrderSelectProduct($manufacturer);

    Passport::actingAs($manufacturer);

    $this->postJson('/api/v1/manufacturer/orders/create', [
        'buyer_id' => $buyer->id,
        'product_id' => $product->id,
        'title' => 'Premium ceramic mugs - 350ml',
        'quantity' => 5000,
        'total_amount' => 12500.50,
        'estimated_delivery_at' => now()->addDays(30)->toDateString(),
    ])
        ->assertUnprocessable()
        ->assertJsonValidationErrors(['buyer_id']);
});

test('manufacturer cannot create order for another manufacturers product', function (): void {
    ['buyer' => $buyer, 'manufacturer' => $manufacturer, 'product' => $product] = seedManufacturerOrderScenario();
    $otherManufacturer = User::factory()->manufacturerApproved()->create();

    Passport::actingAs($otherManufacturer);

    $this->postJson('/api/v1/manufacturer/orders/create', [
        'buyer_id' => $buyer->id,
        'product_id' => $product->id,
        'title' => 'Premium ceramic mugs - 350ml',
        'quantity' => 5000,
        'total_amount' => 12500.50,
        'estimated_delivery_at' => now()->addDays(30)->toDateString(),
    ])
        ->assertUnprocessable()
        ->assertJsonValidationErrors(['product_id']);
});

test('manufacturer can list and show only own orders', function (): void {
    ['buyer' => $buyer, 'manufacturer' => $manufacturer, 'product' => $product] = seedManufacturerOrderScenario();

    Passport::actingAs($manufacturer);

    $createResponse = $this->postJson('/api/v1/manufacturer/orders/create', [
        'buyer_id' => $buyer->id,
        'product_id' => $product->id,
        'title' => 'Premium ceramic mugs - 350ml',
        'quantity' => 5000,
        'total_amount' => 12500.50,
        'estimated_delivery_at' => now()->addDays(30)->toDateString(),
    ])->assertCreated();

    $orderId = $createResponse->json('data.id');

    $this->getJson('/api/v1/manufacturer/orders?search=ceramic&product_id='.$product->id)
        ->assertOk()
        ->assertJsonPath('success', true)
        ->assertJsonCount(1, 'data')
        ->assertJsonPath('data.0.id', $orderId);

    $this->getJson("/api/v1/manufacturer/orders/{$orderId}")
        ->assertOk()
        ->assertJsonPath('success', true)
        ->assertJsonPath('data.id', $orderId)
        ->assertJsonPath('data.product.name', $product->name);
});

test('manufacturer cannot view another manufacturers order', function (): void {
    ['buyer' => $buyer, 'manufacturer' => $manufacturer, 'product' => $product] = seedManufacturerOrderScenario();
    $otherManufacturer = User::factory()->manufacturerApproved()->create();

    Passport::actingAs($manufacturer);

    $orderId = $this->postJson('/api/v1/manufacturer/orders/create', [
        'buyer_id' => $buyer->id,
        'product_id' => $product->id,
        'title' => 'Premium ceramic mugs - 350ml',
        'quantity' => 5000,
        'total_amount' => 12500.50,
        'estimated_delivery_at' => now()->addDays(30)->toDateString(),
    ])->json('data.id');

    Passport::actingAs($otherManufacturer);

    $this->getJson("/api/v1/manufacturer/orders/{$orderId}")->assertNotFound();
});

test('manufacturer order endpoints require manufacturer role', function (): void {
    $buyer = User::factory()->create();
    ['manufacturer' => $manufacturer, 'product' => $product] = seedManufacturerOrderScenario();

    Passport::actingAs($buyer);

    $this->getJson('/api/v1/manufacturer/orders')->assertForbidden();
    $this->postJson('/api/v1/manufacturer/orders/create', [
        'buyer_id' => $buyer->id,
        'product_id' => $product->id,
        'title' => 'Test order',
        'quantity' => 100,
        'total_amount' => 1000,
        'estimated_delivery_at' => now()->addDays(10)->toDateString(),
    ])->assertForbidden();
});
