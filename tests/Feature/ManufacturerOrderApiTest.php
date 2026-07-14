<?php

declare(strict_types=1);

use App\Jobs\Order\SendOrderInAppNotificationJob;
use App\Jobs\SendMailJob;
use App\Models\Order;
use App\Models\OrderAttachment;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Queue;
use Illuminate\Support\Facades\Storage;
use Laravel\Passport\ClientRepository;
use Laravel\Passport\Passport;

uses(RefreshDatabase::class);

beforeEach(function (): void {
    app(ClientRepository::class)->createPersonalAccessGrantClient(
        name: 'Test Personal Access Client',
        provider: config('auth.guards.api.provider')
    );

    Queue::fake([SendMailJob::class, SendOrderInAppNotificationJob::class]);
    Storage::fake('public');
    config(['broadcasting.default' => 'null']);
});

test('manufacturer can create order for connected buyer and product', function (): void {
    ['buyer' => $buyer, 'manufacturer' => $manufacturer, 'product' => $product] = seedManufacturerOrderScenario();

    Passport::actingAs($manufacturer);

    $response = $this->postJson('/api/v1/manufacturer/orders/create', buildManufacturerOrderCreatePayload(
        buyerId: $buyer->id,
        items: [[
            'product_id' => $product->id,
            'quantity' => 5000,
            'quantity_unit' => 'pieces',
            'unit_price' => 2.5001,
        ]],
        overrides: [
            'title' => 'Premium ceramic mugs - 350ml',
            'total_amount' => 12500.50,
            'currency_code' => 'USD',
            'production_lead' => '30 days',
            'payment_terms' => '50% upfront, 50% on delivery',
            'shipping_terms' => 'FOB Shanghai',
            'destination' => 'Los Angeles, USA',
            'notes' => 'Any additional context about this order.',
        ],
    ));

    $response->assertCreated()
        ->assertJsonPath('success', true)
        ->assertJsonPath('data.title', 'Premium ceramic mugs - 350ml')
        ->assertJsonPath('data.buyer.id', $buyer->id)
        ->assertJsonPath('data.product.id', $product->id)
        ->assertJsonCount(1, 'data.items')
        ->assertJsonPath('data.items.0.product.id', $product->id)
        ->assertJsonPath('data.total_amount', '12500.50')
        ->assertJsonPath('data.currency_code', 'USD');

    expect(Order::query()->count())->toBe(1)
        ->and(Order::query()->firstOrFail()->manufacturer_id)->toBe($manufacturer->id);

    Queue::assertPushed(SendMailJob::class, function (SendMailJob $job) use ($buyer): bool {
        return $job->recipient === $buyer->email
            && $job->template === 'manufacturer-order-created'
            && ($job->data['recipientRole'] ?? null) === 'buyer';
    });

    Queue::assertPushed(SendMailJob::class, function (SendMailJob $job) use ($manufacturer): bool {
        return $job->recipient === $manufacturer->email
            && $job->template === 'order-created-manufacturer'
            && ($job->data['recipientRole'] ?? null) === 'manufacturer';
    });
});

test('manufacturer cannot create order using legacy root product fields', function (): void {
    ['buyer' => $buyer, 'manufacturer' => $manufacturer, 'product' => $product] = seedManufacturerOrderScenario();

    Passport::actingAs($manufacturer);

    $this->postJson('/api/v1/manufacturer/orders/create', [
        'buyer_id' => $buyer->id,
        'product_id' => $product->id,
        'title' => 'Legacy order',
        'quantity' => 5000,
        'total_amount' => 12500.50,
        'estimated_delivery_at' => now()->addDays(30)->toDateString(),
    ])
        ->assertUnprocessable()
        ->assertJsonValidationErrors(['product_id', 'items']);
});

test('manufacturer cannot create order without items array', function (): void {
    ['buyer' => $buyer, 'manufacturer' => $manufacturer] = seedManufacturerOrderScenario();

    Passport::actingAs($manufacturer);

    $this->postJson('/api/v1/manufacturer/orders/create', [
        'buyer_id' => $buyer->id,
        'title' => 'Missing items',
        'total_amount' => 1000,
        'estimated_delivery_at' => now()->addDays(30)->toDateString(),
    ])
        ->assertUnprocessable()
        ->assertJsonValidationErrors(['items']);
});

test('manufacturer cannot create order when buyer has no rfq for product', function (): void {
    $buyer = User::factory()->create();
    $manufacturer = manufacturerWithSubscription();
    $product = seedOrderSelectProduct($manufacturer);

    Passport::actingAs($manufacturer);

    $this->postJson('/api/v1/manufacturer/orders/create', buildManufacturerOrderCreatePayload(
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
    ))
        ->assertUnprocessable()
        ->assertJsonValidationErrors(['buyer_id']);
});

test('manufacturer cannot create order for another manufacturers product', function (): void {
    ['buyer' => $buyer, 'manufacturer' => $manufacturer, 'product' => $product] = seedManufacturerOrderScenario();
    $otherManufacturer = manufacturerWithSubscription();

    Passport::actingAs($otherManufacturer);

    $this->postJson('/api/v1/manufacturer/orders/create', buildManufacturerOrderCreatePayload(
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
    ))
        ->assertUnprocessable()
        ->assertJsonValidationErrors(['items.0.product_id']);
});

test('manufacturer can create order with multiple products', function (): void {
    ['buyer' => $buyer, 'manufacturer' => $manufacturer, 'product' => $product] = seedManufacturerOrderScenario();
    $secondProduct = seedOrderSelectProduct($manufacturer, 'Ceramic Travel Mug');

    Passport::actingAs($buyer);
    $this->postJson('/api/v1/buyer/rfqs', [
        'product_id' => $secondProduct->id,
        'quantity' => 2000,
    ])->assertCreated();

    Passport::actingAs($manufacturer);

    $response = $this->postJson('/api/v1/manufacturer/orders/create', buildManufacturerOrderCreatePayload(
        buyerId: $buyer->id,
        items: [
            [
                'product_id' => $product->id,
                'quantity' => 3000,
                'quantity_unit' => 'pieces',
                'unit_price' => 2.50,
                'notes' => '1-color logo print, 24 units per carton',
            ],
            [
                'product_id' => $secondProduct->id,
                'quantity' => 1500,
                'quantity_unit' => 'pieces',
                'unit_price' => 3.00,
            ],
        ],
        overrides: [
            'title' => 'Premium ceramic mugs — 320ml bundle',
            'total_amount' => 12000.00,
            'currency_code' => 'USD',
            'production_lead' => '30 days',
            'payment_terms' => '50% upfront, 50% on delivery',
            'shipping_terms' => 'FOB Shanghai',
            'destination' => 'Los Angeles, USA',
            'notes' => 'Any additional context about this order.',
        ],
    ));

    $response->assertCreated()
        ->assertJsonPath('success', true)
        ->assertJsonPath('data.title', 'Premium ceramic mugs — 320ml bundle')
        ->assertJsonPath('data.total_amount', '12000.00')
        ->assertJsonCount(2, 'data.items')
        ->assertJsonPath('data.items.0.product.id', $product->id)
        ->assertJsonPath('data.items.1.product.id', $secondProduct->id)
        ->assertJsonPath('data.items.0.line_total', '7500.00')
        ->assertJsonPath('data.items.1.line_total', '4500.00');
});

test('manufacturer cannot create order when total amount does not match line totals', function (): void {
    ['buyer' => $buyer, 'manufacturer' => $manufacturer, 'product' => $product] = seedManufacturerOrderScenario();

    Passport::actingAs($manufacturer);

    $this->postJson('/api/v1/manufacturer/orders/create', buildManufacturerOrderCreatePayload(
        buyerId: $buyer->id,
        items: [[
            'product_id' => $product->id,
            'quantity' => 100,
            'unit_price' => 10.00,
        ]],
        overrides: [
            'title' => 'Mismatch order',
            'total_amount' => 500.00,
            'estimated_delivery_at' => now()->addDays(10)->toDateString(),
        ],
    ))
        ->assertUnprocessable()
        ->assertJsonValidationErrors(['total_amount']);
});

test('manufacturer can list and show only own orders', function (): void {
    ['buyer' => $buyer, 'manufacturer' => $manufacturer, 'product' => $product] = seedManufacturerOrderScenario();

    Passport::actingAs($manufacturer);

    $createResponse = $this->postJson('/api/v1/manufacturer/orders/create', buildManufacturerOrderCreatePayload(
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
    ))->assertCreated();

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
        ->assertJsonPath('data.product.name', $product->name)
        ->assertJsonCount(1, 'data.items');
});

test('manufacturer cannot view another manufacturers order', function (): void {
    ['buyer' => $buyer, 'manufacturer' => $manufacturer, 'product' => $product] = seedManufacturerOrderScenario();
    $otherManufacturer = manufacturerWithSubscription();

    Passport::actingAs($manufacturer);

    $orderId = $this->postJson('/api/v1/manufacturer/orders/create', buildManufacturerOrderCreatePayload(
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
    ))->json('data.id');

    Passport::actingAs($otherManufacturer);

    $this->getJson("/api/v1/manufacturer/orders/{$orderId}")->assertNotFound();
});

test('manufacturer order endpoints require manufacturer role', function (): void {
    $buyer = User::factory()->create();
    ['manufacturer' => $manufacturer, 'product' => $product] = seedManufacturerOrderScenario();

    Passport::actingAs($buyer);

    $this->getJson('/api/v1/manufacturer/orders')->assertForbidden();
    $this->postJson('/api/v1/manufacturer/orders/create', buildManufacturerOrderCreatePayload(
        buyerId: $buyer->id,
        items: [[
            'product_id' => $product->id,
            'quantity' => 100,
            'unit_price' => 10.00,
        ]],
        overrides: [
            'title' => 'Test order',
            'estimated_delivery_at' => now()->addDays(10)->toDateString(),
        ],
    ))->assertForbidden();
});

test('manufacturer can create order with document attachments', function (): void {
    ['buyer' => $buyer, 'manufacturer' => $manufacturer, 'product' => $product] = seedManufacturerOrderScenario();

    Passport::actingAs($manufacturer);

    $response = $this->postJson('/api/v1/manufacturer/orders/create', array_merge(
        buildManufacturerOrderCreatePayload(
            buyerId: $buyer->id,
            items: [[
                'product_id' => $product->id,
                'quantity' => 500,
                'unit_price' => 17.60,
            ]],
            overrides: [
                'title' => 'Order with files',
                'total_amount' => 8800,
                'estimated_delivery_at' => now()->addDays(20)->toDateString(),
            ],
        ),
        [
            'attachments' => [
                UploadedFile::fake()->create('po.pdf', 100, 'application/pdf'),
                UploadedFile::fake()->create('packing-list.xlsx', 60, 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet'),
            ],
        ],
    ));

    $response->assertCreated()
        ->assertJsonPath('success', true)
        ->assertJsonCount(2, 'data.attachments');

    expect(OrderAttachment::query()->count())->toBe(2);
});
