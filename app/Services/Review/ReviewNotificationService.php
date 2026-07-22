<?php

namespace App\Services\Review;

use App\Enums\DashboardEventType;
use App\Enums\MailTemplate;
use App\Jobs\Support\SendSupportTicketInAppNotificationJob;
use App\Models\Review;
use App\Models\User;
use App\Services\Dashboard\EventTrackerService;
use App\Services\Mailing\MailingService;
use App\Support\Mail\MailNotificationHelper;

class ReviewNotificationService
{
    public function __construct(
        private readonly EventTrackerService $eventTracker,
        private readonly MailingService $mailingService,
    ) {}

    public function notifySubmitted(Review $review): void
    {
        $review->loadMissing(['reviewer.company', 'product', 'user', 'order']);

        $buyer = $review->reviewer;
        $product = $review->product;
        $buyerName = MailNotificationHelper::displayName($buyer);
        $productName = $product?->name ?? __('order.product');
        $adminUrl = MailNotificationHelper::frontendUrl('admin/reviews');
        $mailData = $this->submittedAdminMailData($review, $buyerName, $productName, $adminUrl);

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
            MailNotificationHelper::sendIfEmail($admin, function (string $email) use ($admin, $mailData): void {
                $this->mailingService->send($email, MailTemplate::NewProductReviewAdmin, [
                    ...$mailData,
                    'recipientName' => MailNotificationHelper::displayName($admin),
                ]);
            }, 'review.submitted.admin');

            $this->dispatchAdminInApp($admin, $buyer, $review, $buyerName, $productName, $adminUrl);
        }
    }

    /**
     * @return array<string, mixed>
     */
    private function submittedAdminMailData(
        Review $review,
        string $buyerName,
        string $productName,
        string $adminUrl,
    ): array {
        $buyer = $review->reviewer;
        $buyer->loadMissing('company');
        $orderId = $review->order_id !== null ? (int) $review->order_id : null;
        $localized = $review->localizedData();

        return [
            'productId' => $review->product_id !== null ? (int) $review->product_id : null,
            'productName' => $productName,
            'orderId' => $orderId,
            'orderNumber' => $orderId !== null ? sprintf('ORD-%05d', $orderId) : '',
            'buyerName' => $buyerName,
            'buyerCompany' => trim((string) ($buyer?->company?->company_name ?? '')),
            'buyerCountry' => trim((string) ($buyer?->company?->country ?? '')),
            'buyerInitials' => MailNotificationHelper::initials($buyerName),
            'rating' => (int) $review->rating,
            'reviewTitle' => $localized['title'] ?? $review->title ?? '',
            'reviewBody' => $localized['comment'] ?? $review->comment ?? '',
            'reviewDate' => $review->created_at?->format('M j, Y') ?? '',
            'ctaUrl' => $adminUrl,
            'referenceId' => $orderId !== null ? sprintf('ORD-%05d', $orderId) : (string) $review->id,
        ];
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
