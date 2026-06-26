<?php

declare(strict_types=1);

use App\Enums\OrderStatus;
use App\Enums\ReviewStatus;
use App\Jobs\SendMailJob;
use App\Models\Review;
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
 * @return array{buyer: User, manufacturer: User, product_id: int, order_id: int}
 */
function createCompletedOrderForReview(): array
{
    ['buyer' => $buyer, 'manufacturer' => $manufacturer, 'product' => $product] = seedManufacturerOrderScenario();

    Passport::actingAs($manufacturer);

    $orderId = test()->postJson('/api/v1/manufacturer/orders/create', buildManufacturerOrderCreatePayload(
        buyerId: $buyer->id,
        items: [[
            'product_id' => $product->id,
            'quantity' => 300,
            'unit_price' => 15.00,
        ]],
        overrides: [
            'title' => 'Reviewable order',
            'total_amount' => 4500,
            'estimated_delivery_at' => now()->addDays(20)->toDateString(),
        ],
    ))->assertCreated()->json('data.id');

    test()->postJson("/api/v1/manufacturer/orders/{$orderId}/status-updates", [
        'status' => OrderStatus::Completed->value,
        'notes' => 'Delivered and closed.',
    ])->assertCreated();

    return [
        'buyer' => $buyer,
        'manufacturer' => $manufacturer,
        'product_id' => (int) $product->id,
        'order_id' => (int) $orderId,
    ];
}

test('buyer can review product only after completed purchase', function (): void {
    ['buyer' => $buyer, 'product_id' => $productId, 'order_id' => $orderId] = createCompletedOrderForReview();

    Passport::actingAs($buyer);

    $this->postJson("/api/v1/buyer/products/{$productId}/reviews", [
        'order_id' => $orderId,
        'rating' => 5,
        'title' => 'Exceptional quality and communication',
        'comment' => 'Consistently high quality and responsive team.',
    ])
        ->assertCreated()
        ->assertJsonPath('success', true)
        ->assertJsonPath('data.rating', 5)
        ->assertJsonPath('data.status', 'pending')
        ->assertJsonPath('data.order.id', $orderId);

    expect(Review::query()->count())->toBe(1);
});

test('buyer cannot review without completed purchase of product', function (): void {
    ['buyer' => $buyer, 'manufacturer' => $manufacturer, 'product' => $product] = seedManufacturerOrderScenario();
    $otherBuyer = User::factory()->create();

    Passport::actingAs($manufacturer);
    $orderId = $this->postJson('/api/v1/manufacturer/orders/create', buildManufacturerOrderCreatePayload(
        buyerId: $buyer->id,
        items: [[
            'product_id' => $product->id,
            'quantity' => 100,
            'unit_price' => 10.00,
        ]],
        overrides: [
            'title' => 'Not completed order',
            'estimated_delivery_at' => now()->addDays(10)->toDateString(),
        ],
    ))->assertCreated()->json('data.id');

    Passport::actingAs($otherBuyer);

    $this->postJson("/api/v1/buyer/products/{$product->id}/reviews", [
        'order_id' => $orderId,
        'rating' => 4,
        'comment' => 'Trying to review without purchase.',
    ])->assertUnprocessable()->assertJsonValidationErrors(['order_id']);
});

test('buyer can review same product multiple times with different completed orders', function (): void {
    ['buyer' => $buyer, 'manufacturer' => $manufacturer, 'product_id' => $productId, 'order_id' => $firstOrderId] = createCompletedOrderForReview();

    Passport::actingAs($buyer);
    $this->postJson("/api/v1/buyer/products/{$productId}/reviews", [
        'order_id' => $firstOrderId,
        'rating' => 5,
        'title' => 'Great first batch',
        'comment' => 'First order quality is great.',
    ])->assertCreated();

    Passport::actingAs($manufacturer);
    $secondOrderId = $this->postJson('/api/v1/manufacturer/orders/create', buildManufacturerOrderCreatePayload(
        buyerId: $buyer->id,
        items: [[
            'product_id' => $productId,
            'quantity' => 200,
            'unit_price' => 11.00,
        ]],
        overrides: [
            'title' => 'Second order',
            'total_amount' => 2200,
            'estimated_delivery_at' => now()->addDays(10)->toDateString(),
        ],
    ))->assertCreated()->json('data.id');

    $this->postJson("/api/v1/manufacturer/orders/{$secondOrderId}/status-updates", [
        'status' => OrderStatus::Completed->value,
        'notes' => 'Second order complete.',
    ])->assertCreated();

    Passport::actingAs($buyer);

    $this->postJson("/api/v1/buyer/products/{$productId}/reviews", [
        'order_id' => $secondOrderId,
        'rating' => 4,
        'title' => 'Great second batch',
        'comment' => 'Second batch quality is also strong.',
    ])->assertCreated();

    expect(Review::query()->where('product_id', $productId)->count())->toBe(2);
});

test('buyer cannot review same order twice', function (): void {
    ['buyer' => $buyer, 'product_id' => $productId, 'order_id' => $orderId] = createCompletedOrderForReview();

    Passport::actingAs($buyer);

    $payload = [
        'order_id' => $orderId,
        'rating' => 5,
        'title' => 'Excellent',
        'comment' => 'Excellent product and service.',
    ];

    $this->postJson("/api/v1/buyer/products/{$productId}/reviews", $payload)->assertCreated();

    $this->postJson("/api/v1/buyer/products/{$productId}/reviews", $payload)
        ->assertUnprocessable()
        ->assertJsonValidationErrors(['order_id']);
});

test('guest cannot submit product review', function (): void {
    ['product_id' => $productId, 'order_id' => $orderId] = createCompletedOrderForReview();

    $this->postJson("/api/v1/buyer/products/{$productId}/reviews", [
        'order_id' => $orderId,
        'rating' => 5,
        'comment' => 'Guest review attempt.',
    ])->assertForbidden();
});

test('product details include review summary and review list', function (): void {
    ['buyer' => $buyer, 'product_id' => $productId, 'order_id' => $orderId] = createCompletedOrderForReview();

    Passport::actingAs($buyer);

    $this->postJson("/api/v1/buyer/products/{$productId}/reviews", [
        'order_id' => $orderId,
        'rating' => 5,
        'title' => 'Exceptional quality and communication',
        'comment' => 'Consistently high quality and responsive team.',
    ])->assertCreated();

    auth('api')->forgetUser();

    $this->getJson("/api/v1/products/{$productId}")
        ->assertOk()
        ->assertJsonPath('data.review_stats.total_reviews', 0)
        ->assertJsonPath('data.reviews', []);

    Review::query()->first()?->update(['status' => ReviewStatus::PUBLISHED->value]);

    $this->getJson("/api/v1/products/{$productId}")
        ->assertOk()
        ->assertJsonPath('data.review_stats.total_reviews', 1)
        ->assertJsonPath('data.review_stats.average_rating', 5)
        ->assertJsonPath('data.reviews.0.rating', 5)
        ->assertJsonPath('data.reviews.0.reviewer.id', $buyer->id);
});
