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

/**
 * @return array{order_id: int, buyer: User, manufacturer: User}
 */
function createOrderForAdminApiTests(): array
{
    ['buyer' => $buyer, 'manufacturer' => $manufacturer, 'product' => $product] = seedManufacturerOrderScenario();

    Passport::actingAs($manufacturer);

    $orderId = test()->postJson('/api/v1/manufacturer/orders/create', buildManufacturerOrderCreatePayload(
        buyerId: $buyer->id,
        items: [[
            'product_id' => $product->id,
            'quantity' => 5000,
            'unit_price' => 2.5001,
        ]],
        overrides: [
            'title' => 'Premium ceramic mugs - 350ml',
            'total_amount' => 12500.50,
        ],
    ))->assertCreated()->json('data.id');

    return [
        'order_id' => $orderId,
        'buyer' => $buyer,
        'manufacturer' => $manufacturer,
    ];
}

test('admin can list all orders with search and filters', function (): void {
    ['order_id' => $orderId, 'buyer' => $buyer, 'manufacturer' => $manufacturer] = createOrderForAdminApiTests();
    $admin = User::factory()->admin()->create();

    Passport::actingAs($admin);

    $this->getJson('/api/v1/admin/orders?search=ceramic&buyer_id='.$buyer->id.'&manufacturer_id='.$manufacturer->id.'&status='.OrderStatus::OrderCreated->value)
        ->assertOk()
        ->assertJsonPath('success', true)
        ->assertJsonCount(1, 'data')
        ->assertJsonPath('data.0.id', $orderId)
        ->assertJsonPath('data.0.buyer.id', $buyer->id)
        ->assertJsonPath('data.0.manufacturer.id', $manufacturer->id);
});

test('admin can view any order with product and progress updates', function (): void {
    ['order_id' => $orderId, 'manufacturer' => $manufacturer] = createOrderForAdminApiTests();
    $admin = User::factory()->admin()->create();

    Passport::actingAs($manufacturer);

    $this->postJson("/api/v1/manufacturer/orders/{$orderId}/status-updates", [
        'status' => OrderStatus::InProduction->value,
        'notes' => 'Assembly started.',
    ])->assertCreated();

    Passport::actingAs($admin);

    $this->getJson("/api/v1/admin/orders/{$orderId}")
        ->assertOk()
        ->assertJsonStructure(['data' => ['product' => ['id'], 'progress_updates', 'status_updates']])
        ->assertJsonCount(2, 'data.progress_updates')
        ->assertJsonCount(2, 'data.status_updates');
});

test('admin cannot post order progress update', function (): void {
    ['order_id' => $orderId] = createOrderForAdminApiTests();
    $admin = User::factory()->admin()->create();

    Passport::actingAs($admin);

    $this->postJson("/api/v1/admin/orders/{$orderId}/status-updates", [
        'status' => OrderStatus::InProduction->value,
        'notes' => 'Should fail.',
    ])->assertNotFound();
});

test('admin order endpoints require admin role', function (): void {
    ['buyer' => $buyer] = createOrderForAdminApiTests();

    Passport::actingAs($buyer);

    $this->getJson('/api/v1/admin/orders')->assertForbidden();
});
