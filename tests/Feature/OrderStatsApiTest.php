<?php

declare(strict_types=1);

use App\Enums\OrderStatus;
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

test('buyer order stats reflect only own purchases', function (): void {
    ['buyer' => $buyer, 'manufacturer' => $manufacturer, 'product' => $product] = seedManufacturerOrderScenario();

    Passport::actingAs($manufacturer);

    test()->postJson('/api/v1/manufacturer/orders/create', [
        'buyer_id' => $buyer->id,
        'product_id' => $product->id,
        'title' => 'Buyer order A',
        'quantity' => 100,
        'total_amount' => 1000,
        'estimated_delivery_at' => now()->addDays(10)->toDateString(),
    ])->assertCreated();

    $otherBuyer = User::factory()->create();
    Passport::actingAs($otherBuyer);

    test()->postJson('/api/v1/buyer/rfqs', [
        'product_id' => $product->id,
        'quantity' => 100,
    ])->assertCreated();

    Passport::actingAs($manufacturer);

    test()->postJson('/api/v1/manufacturer/orders/create', [
        'buyer_id' => $otherBuyer->id,
        'product_id' => $product->id,
        'title' => 'Other buyer order',
        'quantity' => 50,
        'total_amount' => 500,
        'estimated_delivery_at' => now()->addDays(10)->toDateString(),
    ])->assertCreated();

    Passport::actingAs($buyer);

    $this->getJson('/api/v1/buyer/orders/stats')
        ->assertOk()
        ->assertJsonPath('data.total_orders', 1)
        ->assertJsonPath('data.active_orders', 1)
        ->assertJsonPath('data.order_created', 1)
        ->assertJsonPath('data.order_value_by_currency.0.currency_code', 'USD')
        ->assertJsonPath('data.order_value_by_currency.0.total_amount', '1000.00');
});

test('manufacturer order stats reflect only own sales', function (): void {
    ['buyer' => $buyer, 'manufacturer' => $manufacturer, 'product' => $product] = seedManufacturerOrderScenario();

    Passport::actingAs($manufacturer);

    $orderId = test()->postJson('/api/v1/manufacturer/orders/create', [
        'buyer_id' => $buyer->id,
        'product_id' => $product->id,
        'title' => 'Manufacturer order',
        'quantity' => 200,
        'total_amount' => 2000,
        'estimated_delivery_at' => now()->addDays(10)->toDateString(),
    ])->assertCreated()->json('data.id');

    test()->postJson("/api/v1/manufacturer/orders/{$orderId}/status-updates", [
        'status' => OrderStatus::InProduction->value,
        'notes' => 'Started production.',
    ])->assertCreated();

    Passport::actingAs($manufacturer);

    $this->getJson('/api/v1/manufacturer/orders/stats')
        ->assertOk()
        ->assertJsonPath('data.total_orders', 1)
        ->assertJsonPath('data.active_orders', 1)
        ->assertJsonPath('data.order_created', 0)
        ->assertJsonPath('data.in_production', 1);
});

test('admin order stats include all orders buyers and manufacturers', function (): void {
    ['buyer' => $buyer, 'manufacturer' => $manufacturer, 'product' => $product] = seedManufacturerOrderScenario();
    $admin = User::factory()->admin()->create();

    Passport::actingAs($manufacturer);

    test()->postJson('/api/v1/manufacturer/orders/create', [
        'buyer_id' => $buyer->id,
        'product_id' => $product->id,
        'title' => 'Order one',
        'quantity' => 100,
        'total_amount' => 1000,
        'estimated_delivery_at' => now()->addDays(10)->toDateString(),
    ])->assertCreated();

    test()->postJson('/api/v1/manufacturer/orders/create', [
        'buyer_id' => $buyer->id,
        'product_id' => $product->id,
        'title' => 'Order two',
        'quantity' => 200,
        'total_amount' => 2500,
        'estimated_delivery_at' => now()->addDays(15)->toDateString(),
    ])->assertCreated();

    Passport::actingAs($admin);

    $this->getJson('/api/v1/admin/orders/stats')
        ->assertOk()
        ->assertJsonPath('data.total_orders', 2)
        ->assertJsonPath('data.total_buyers', 1)
        ->assertJsonPath('data.total_manufacturers', 1)
        ->assertJsonPath('data.order_created', 2)
        ->assertJsonPath('data.order_value_by_currency.0.total_amount', '3500.00');
});
