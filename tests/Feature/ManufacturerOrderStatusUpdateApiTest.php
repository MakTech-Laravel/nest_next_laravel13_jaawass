<?php

declare(strict_types=1);

use App\Enums\OrderStatus;
use App\Models\Order;
use App\Models\OrderStatusUpdate;
use App\Models\OrderStatusUpdateAttachment;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Laravel\Passport\ClientRepository;
use Laravel\Passport\Passport;

uses(RefreshDatabase::class);

beforeEach(function (): void {
    app(ClientRepository::class)->createPersonalAccessGrantClient(
        name: 'Test Personal Access Client',
        provider: config('auth.guards.api.provider')
    );

    Storage::fake('public');
});

/**
 * @return array{buyer: User, manufacturer: User, order: Order}
 */
function seedManufacturerOrderWithStatus(): array
{
    ['buyer' => $buyer, 'manufacturer' => $manufacturer, 'product' => $product] = seedManufacturerOrderScenario();

    Passport::actingAs($manufacturer);

    $orderId = test()->postJson('/api/v1/manufacturer/orders/create', [
        'buyer_id' => $buyer->id,
        'product_id' => $product->id,
        'title' => 'Premium ceramic mugs - 350ml',
        'quantity' => 5000,
        'total_amount' => 12500.50,
        'estimated_delivery_at' => now()->addDays(30)->toDateString(),
    ])->assertCreated()->json('data.id');

    return [
        'buyer' => $buyer,
        'manufacturer' => $manufacturer,
        'order' => Order::query()->findOrFail($orderId),
    ];
}

test('manufacturer can fetch order status options', function (): void {
    $manufacturer = manufacturerWithSubscription();

    Passport::actingAs($manufacturer);

    $this->getJson('/api/v1/manufacturer/orders/status-options')
        ->assertOk()
        ->assertJsonPath('success', true)
        ->assertJsonCount(count(OrderStatus::cases()), 'data')
        ->assertJsonPath('data.0.value', OrderStatus::OrderCreated->value)
        ->assertJsonPath('data.0.label', OrderStatus::OrderCreated->label());
});

test('manufacturer can post order progress update with notes and files', function (): void {
    ['manufacturer' => $manufacturer, 'order' => $order] = seedManufacturerOrderWithStatus();

    Passport::actingAs($manufacturer);

    $response = $this->postJson("/api/v1/manufacturer/orders/{$order->id}/status-updates", [
        'status' => OrderStatus::InProduction->value,
        'notes' => 'Production started on line 2.',
        'photos' => [
            UploadedFile::fake()->image('progress.jpg'),
        ],
        'attachments' => [
            UploadedFile::fake()->create('qc-report.pdf', 100, 'application/pdf'),
        ],
    ]);

    $response->assertCreated()
        ->assertJsonPath('success', true)
        ->assertJsonPath('data.status', OrderStatus::InProduction->value)
        ->assertJsonPath('data.product.id', $order->product_id)
        ->assertJsonCount(2, 'data.progress_updates')
        ->assertJsonCount(2, 'data.status_updates')
        ->assertJsonPath('data.progress_updates.0.status', OrderStatus::InProduction->value)
        ->assertJsonPath('data.progress_updates.0.notes', 'Production started on line 2.')
        ->assertJsonPath('data.progress_updates.0.user.role_label', 'Manufacturer');

    expect($order->fresh()->status)->toBe(OrderStatus::InProduction)
        ->and(OrderStatusUpdate::query()->count())->toBe(2)
        ->and(OrderStatusUpdateAttachment::query()->count())->toBe(2);
});

test('manufacturer cannot post progress update without notes or attachments', function (): void {
    ['manufacturer' => $manufacturer, 'order' => $order] = seedManufacturerOrderWithStatus();

    Passport::actingAs($manufacturer);

    $this->postJson("/api/v1/manufacturer/orders/{$order->id}/status-updates", [
        'status' => OrderStatus::InProduction->value,
    ])
        ->assertUnprocessable()
        ->assertJsonValidationErrors(['notes']);
});

test('manufacturer cannot post progress update for another manufacturers order', function (): void {
    ['order' => $order] = seedManufacturerOrderWithStatus();
    $otherManufacturer = manufacturerWithSubscription();

    Passport::actingAs($otherManufacturer);

    $this->postJson("/api/v1/manufacturer/orders/{$order->id}/status-updates", [
        'status' => OrderStatus::InProduction->value,
        'notes' => 'Unauthorized update',
    ])->assertForbidden();
});

test('admin cannot post order progress update', function (): void {
    ['order' => $order] = seedManufacturerOrderWithStatus();
    $admin = User::factory()->admin()->create();

    Passport::actingAs($admin);

    $this->postJson("/api/v1/admin/orders/{$order->id}/status-updates", [
        'status' => OrderStatus::ReadyForShipment->value,
        'notes' => 'Packed and ready for pickup.',
    ])->assertNotFound();
});

test('admin can fetch order with product and progress updates', function (): void {
    ['manufacturer' => $manufacturer, 'order' => $order] = seedManufacturerOrderWithStatus();
    $admin = User::factory()->admin()->create();

    Passport::actingAs($manufacturer);

    $this->postJson("/api/v1/manufacturer/orders/{$order->id}/status-updates", [
        'status' => OrderStatus::InProduction->value,
        'notes' => 'Assembly in progress.',
    ])->assertCreated();

    Passport::actingAs($admin);

    $this->getJson("/api/v1/admin/orders/{$order->id}")
        ->assertOk()
        ->assertJsonPath('data.product.id', $order->product_id)
        ->assertJsonPath('data.product.slug', $order->product->slug)
        ->assertJsonCount(2, 'data.progress_updates');
});

test('new order is created with order created status and initial progress update', function (): void {
    ['order' => $order] = seedManufacturerOrderWithStatus();

    expect($order->status)->toBe(OrderStatus::OrderCreated)
        ->and(OrderStatusUpdate::query()->where('order_id', $order->id)->count())->toBe(1);
});
