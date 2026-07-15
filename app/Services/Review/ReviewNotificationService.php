<?php

namespace App\Services\Review;

use App\Enums\DashboardEventType;
use App\Jobs\Support\SendSupportTicketInAppNotificationJob;
use App\Models\Review;
use App\Models\User;
use App\Services\Dashboard\EventTrackerService;
use App\Support\Mail\MailNotificationHelper;

class ReviewNotificationService
{
    public function __construct(
        private readonly EventTrackerService $eventTracker,
    ) {}

    public function notifySubmitted(Review $review): void
    {
        $review->loadMissing(['reviewer', 'product', 'user']);

        $buyer = $review->reviewer;
        $product = $review->product;
        $buyerName = MailNotificationHelper::displayName($buyer);
        $productName = $product?->name ?? __('order.product');
        $adminUrl = MailNotificationHelper::frontendUrl('admin/reviews');

        $this->eventTracker->track(
            eventType: DashboardEventType::ReviewSubmitted,
            actor: $buyer,
            entityType: 'review',
            entityId: (int) $review->id,
            counterparty: $review->user,
            metadata: [
                'product_id' => $review->product_id !== null ? (int) $review->product_id : null,
                'product_name' => $productName,
                'buyer_name' => $buyerName,
                'rating' => (int) $review->rating,
                'order_id' => $review->order_id !== null ? (int) $review->order_id : null,
            ],
            occurredAt: $review->created_at,
        );

        foreach (MailNotificationHelper::adminRecipients() as $admin) {
            $this->dispatchAdminInApp($admin, $buyer, $review, $buyerName, $productName, $adminUrl);
        }
    }

    private function dispatchAdminInApp(
        User $admin,
        ?User $buyer,
        Review $review,
        string $buyerName,
        string $productName,
        string $adminUrl,
    ): void {
        SendSupportTicketInAppNotificationJob::dispatch(
            recipientId: (int) $admin->id,
            type: 'review.submitted.admin',
            title: __('review.notifications.submitted.admin_title'),
            body: __('review.notifications.submitted.admin_body', [
                'buyer' => $buyerName,
                'product' => $productName,
                'rating' => (int) $review->rating,
            ]),
            data: [
                'review_id' => (int) $review->id,
                'product_id' => $review->product_id !== null ? (int) $review->product_id : null,
                'order_id' => $review->order_id !== null ? (int) $review->order_id : null,
                'rating' => (int) $review->rating,
            ],
            actionUrl: $adminUrl,
            senderId: $buyer?->id,
        );
    }
}
