<?php

declare(strict_types=1);

use App\Enums\OrderStatus;
use App\Jobs\SendMailJob;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Queue;
use Laravel\Passport\ClientRepository;
use Laravel\Passport\Passport;

uses(RefreshDatabase::class);

beforeEach(function (): void {
    app(ClientRepository::class)->createPersonalAccessGrantClient(
        name: 'Test Personal Access Client',
        provider: config('auth.guards.api.provider')
    );

    Queue::fake([SendMailJob::class]);
});

test('buyer order stats reflect only own purchases', function (): void {
    ['buyer' => $buyer, 'manufacturer' => $manufacturer, 'product' => $product] = seedManufacturerOrderScenario();

    Passport::actingAs($manufacturer);

    test()->postJson('/api/v1/manufacturer/orders/create', buildManufacturerOrderCreatePayload(
        buyerId: $buyer->id,
        items: [[
            'product_id' => $product->id,
            'quantity' => 100,
            'unit_price' => 10.00,
        ]],
        overrides: ['title' => 'Buyer order A'],
    ))->assertCreated();

    $otherBuyer = User::factory()->create();
    Passport::actingAs($otherBuyer);

    test()->postJson('/api/v1/buyer/rfqs', [
        'product_id' => $product->id,
        'quantity' => 100,
    ])->assertCreated();

    Passport::actingAs($manufacturer);

    test()->postJson('/api/v1/manufacturer/orders/create', buildManufacturerOrderCreatePayload(
        buyerId: $otherBuyer->id,
        items: [[
            'product_id' => $product->id,
            'quantity' => 50,
            'unit_price' => 10.00,
        ]],
        overrides: ['title' => 'Other buyer order'],
    ))->assertCreated();

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

    $orderId = test()->postJson('/api/v1/manufacturer/orders/create', buildManufacturerOrderCreatePayload(
        buyerId: $buyer->id,
        items: [[
            'product_id' => $product->id,
            'quantity' => 200,
            'unit_price' => 10.00,
        ]],
        overrides: ['title' => 'Manufacturer order'],
    ))->assertCreated()->json('data.id');

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

    test()->postJson('/api/v1/manufacturer/orders/create', buildManufacturerOrderCreatePayload(
        buyerId: $buyer->id,
        items: [[
            'product_id' => $product->id,
            'quantity' => 100,
            'unit_price' => 10.00,
        ]],
        overrides: ['title' => 'Order one'],
    ))->assertCreated();

    test()->postJson('/api/v1/manufacturer/orders/create', buildManufacturerOrderCreatePayload(
        buyerId: $buyer->id,
        items: [[
            'product_id' => $product->id,
            'quantity' => 200,
            'unit_price' => 12.50,
        ]],
        overrides: [
            'title' => 'Order two',
            'total_amount' => 2500,
        ],
    ))->assertCreated();

    Passport::actingAs($admin);

    $this->getJson('/api/v1/admin/orders/stats')
        ->assertOk()
        ->assertJsonPath('data.total_orders', 2)
        ->assertJsonPath('data.total_buyers', 1)
        ->assertJsonPath('data.total_manufacturers', 1)
        ->assertJsonPath('data.order_created', 2)
        ->assertJsonPath('data.order_value_by_currency.0.total_amount', '3500.00');
});
