<?php

declare(strict_types=1);

use App\Enums\OrderStatus;
use App\Enums\ReviewStatus;
use App\Models\Review;
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

/**
 * @return array{
 *     review: Review,
 *     buyer: User,
 *     manufacturer: User,
 *     product_id: int,
 *     order_id: int
 * }
 */
function createPendingReviewForAdminTests(): array
{
    ['buyer' => $buyer, 'manufacturer' => $manufacturer, 'product_id' => $productId, 'order_id' => $orderId] = createCompletedOrderForReview();

    Passport::actingAs($buyer);

    test()->postJson("/api/v1/buyer/products/{$productId}/reviews", [
        'order_id' => $orderId,
        'rating' => 5,
        'title' => 'Perfect for sustainable brands',
        'comment' => 'EcoThread has been our go-to supplier for organic cotton products.',
    ])->assertCreated();

    $review = Review::query()->firstOrFail();

    return [
        'review' => $review,
        'buyer' => $buyer,
        'manufacturer' => $manufacturer,
        'product_id' => $productId,
        'order_id' => $orderId,
    ];
}

test('admin can fetch review stats', function (): void {
    createPendingReviewForAdminTests();

    $admin = User::factory()->admin()->create();
    Passport::actingAs($admin);

    $this->getJson('/api/v1/admin/reviews/stats')
        ->assertOk()
        ->assertJsonPath('data.total_reviews', 1)
        ->assertJsonPath('data.pending_review', 1)
        ->assertJsonPath('data.published', 0)
        ->assertJsonPath('data.flagged', 0)
        ->assertJsonPath('data.labels.total_reviews', 'Total Reviews')
        ->assertJsonStructure([
            'data' => [
                'labels' => ['total_reviews', 'published', 'pending_review', 'flagged', 'hidden'],
                'status_options' => [
                    ['value', 'label'],
                ],
            ],
        ]);
});

test('admin can list reviews with search status and rating filters', function (): void {
    ['review' => $review, 'buyer' => $buyer, 'manufacturer' => $manufacturer, 'product_id' => $productId] = createPendingReviewForAdminTests();

    Passport::actingAs($manufacturer);

    $secondOrderId = test()->postJson('/api/v1/manufacturer/orders/create', [
        'buyer_id' => $buyer->id,
        'product_id' => $productId,
        'title' => 'Second order',
        'quantity' => 200,
        'total_amount' => 2200,
        'estimated_delivery_at' => now()->addDays(10)->toDateString(),
    ])->assertCreated()->json('data.id');

    test()->postJson("/api/v1/manufacturer/orders/{$secondOrderId}/status-updates", [
        'status' => OrderStatus::Completed->value,
        'notes' => 'Second order complete.',
    ])->assertCreated();

    Passport::actingAs($buyer);

    test()->postJson("/api/v1/buyer/products/{$productId}/reviews", [
        'order_id' => $secondOrderId,
        'rating' => 4,
        'title' => 'Reliable auto parts supplier',
        'comment' => 'Reliable auto parts supplier with consistent quality.',
    ])->assertCreated();

    Review::query()->where('order_id', $secondOrderId)->update([
        'status' => ReviewStatus::PUBLISHED->value,
    ]);

    $admin = User::factory()->admin()->create();
    Passport::actingAs($admin);

    $this->getJson('/api/v1/admin/reviews?status=pending&rating=5&search=sustainable')
        ->assertOk()
        ->assertJsonCount(1, 'data')
        ->assertJsonPath('data.0.id', $review->id)
        ->assertJsonPath('data.0.status', ReviewStatus::PENDING->value)
        ->assertJsonPath('data.0.reviewer.full_name', trim("{$buyer->first_name} {$buyer->last_name}"));
});

test('admin can view update hide publish flag and delete reviews', function (): void {
    ['review' => $review] = createPendingReviewForAdminTests();
    $admin = User::factory()->admin()->create();

    Passport::actingAs($admin);

    $this->getJson("/api/v1/admin/reviews/{$review->id}")
        ->assertOk()
        ->assertJsonPath('data.id', $review->id)
        ->assertJsonPath('data.status', ReviewStatus::PENDING->value)
        ->assertJsonStructure(['data' => ['reviewer', 'supplier', 'product', 'order']]);

    $this->patchJson("/api/v1/admin/reviews/{$review->id}", [
        'status' => ReviewStatus::PUBLISHED->value,
    ])
        ->assertOk()
        ->assertJsonPath('data.status', ReviewStatus::PUBLISHED->value);

    $this->patchJson("/api/v1/admin/reviews/{$review->id}", [
        'status' => ReviewStatus::HIDDEN->value,
        'rating' => 4,
        'title' => 'Updated review title',
        'comment' => 'Updated review content for moderation.',
    ])
        ->assertOk()
        ->assertJsonPath('data.status', ReviewStatus::HIDDEN->value)
        ->assertJsonPath('data.rating', 4)
        ->assertJsonPath('data.title', 'Updated review title');

    $this->patchJson("/api/v1/admin/reviews/{$review->id}", [
        'status' => ReviewStatus::FLAGGED->value,
    ])
        ->assertOk()
        ->assertJsonPath('data.status', ReviewStatus::FLAGGED->value);

    $this->deleteJson("/api/v1/admin/reviews/{$review->id}")
        ->assertOk()
        ->assertJsonPath('data.id', $review->id);

    expect(Review::query()->whereKey($review->id)->exists())->toBeFalse();
});

test('non admin cannot manage reviews', function (): void {
    ['review' => $review] = createPendingReviewForAdminTests();
    $buyer = User::factory()->create();

    Passport::actingAs($buyer);

    $this->getJson('/api/v1/admin/reviews')->assertForbidden();
    $this->patchJson("/api/v1/admin/reviews/{$review->id}", [
        'status' => ReviewStatus::PUBLISHED->value,
    ])->assertForbidden();
    $this->deleteJson("/api/v1/admin/reviews/{$review->id}")->assertForbidden();
});
