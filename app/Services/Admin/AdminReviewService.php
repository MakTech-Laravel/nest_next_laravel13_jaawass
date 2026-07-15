<?php

namespace App\Services\Admin;

use App\Enums\MailTemplate;
use App\Enums\ReviewStatus;
use App\Http\Requests\Api\V1\Admin\IndexAdminReviewRequest;
use App\Models\Review;
use App\Services\Mailing\MailingService;
use App\Support\Mail\MailNotificationHelper;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Builder;

class AdminReviewService
{
    public function __construct(
        private readonly MailingService $mailingService,
    ) {}

    /**
     * @return array<string, int>
     */
    public function stats(): array
    {
        $counts = Review::query()
            ->selectRaw('status, COUNT(*) as aggregate')
            ->groupBy('status')
            ->pluck('aggregate', 'status');

        return [
            'total_reviews' => (int) Review::query()->count(),
            'published' => (int) ($counts[ReviewStatus::PUBLISHED->value] ?? 0),
            'pending_review' => (int) ($counts[ReviewStatus::PENDING->value] ?? 0),
            'flagged' => (int) ($counts[ReviewStatus::FLAGGED->value] ?? 0),
            'hidden' => (int) ($counts[ReviewStatus::HIDDEN->value] ?? 0),
            'labels' => [
                'total_reviews' => __('review.stats.total_reviews'),
                'published' => __('review.stats.published'),
                'pending_review' => __('review.stats.pending_review'),
                'flagged' => __('review.stats.flagged'),
                'hidden' => __('review.stats.hidden'),
            ],
            'status_options' => ReviewStatus::options(),
        ];
    }

    public function paginate(IndexAdminReviewRequest $request): LengthAwarePaginator
    {
        return $this->listQuery($request)
            ->paginate(
                perPage: $request->perPage(),
                pageName: 'page',
                page: $request->pageNumber(),
            );
    }

    public function find(Review $review): Review
    {
        return $review->load($this->detailRelations());
    }

    /**
     * @param  array<string, mixed>  $attributes
     */
    public function update(Review $review, array $attributes, ?string $sourceLocale = null): Review
    {
        $previousStatus = $review->status;
        $locale = $attributes['locale'] ?? $sourceLocale;
        unset($attributes['locale']);

        $translatable = array_intersect_key(
            $attributes,
            array_flip($review->translatableFields()),
        );

        $review->update($attributes);

        if ($translatable !== []) {
            $review->syncTranslations($translatable, is_string($locale) ? $locale : null);
        }

        $review = $review->fresh($this->detailRelations()) ?? $review;

        $this->notifyWhenPublished($review, $previousStatus);

        return $review;
    }

    public function delete(Review $review): Review
    {
        $review->load($this->detailRelations());
        $review->delete();

        return $review;
    }

    /**
     * @return array<int, string|\Closure>
     */
    public function listRelations(): array
    {
        return [
            'translations',
            'reviewer.company',
            'user.company',
            'product.translations',
            'product.category.translations',
            'order',
        ];
    }

    /**
     * @return array<int, string|\Closure>
     */
    public function detailRelations(): array
    {
        return $this->listRelations();
    }

    private function notifyWhenPublished(Review $review, ?ReviewStatus $previousStatus): void
    {
        if ($review->status !== ReviewStatus::PUBLISHED) {
            return;
        }

        if ($previousStatus === ReviewStatus::PUBLISHED) {
            return;
        }

        $review->loadMissing([
            'reviewer.company',
            'user.company',
            'product',
            'order',
        ]);

        $mailData = $this->reviewPublishedMailData($review);
        $buyer = $review->reviewer;
        $manufacturer = $review->user;

        MailNotificationHelper::sendIfEmail($buyer, function (string $email) use ($mailData, $buyer): void {
            $this->mailingService->send($email, MailTemplate::ReviewApproved, [
                ...$mailData,
                'recipientName' => MailNotificationHelper::displayName($buyer),
                'ctaUrl' => MailNotificationHelper::productReviewsUrl(
                    isset($mailData['productId']) ? (int) $mailData['productId'] : null
                ),
            ]);
        });

        MailNotificationHelper::sendIfEmail($manufacturer, function (string $email) use ($mailData): void {
            $this->mailingService->send($email, MailTemplate::NewProductReview, $mailData);
        });
    }

    /**
     * @return array<string, mixed>
     */
    private function reviewPublishedMailData(Review $review): array
    {
        $reviewer = $review->reviewer;
        $product = $review->product;
        $order = $review->order;
        $localized = $review->localizedData();
        $orderId = $order !== null ? (int) $order->id : ($review->order_id !== null ? (int) $review->order_id : null);
        $orderNumber = $orderId !== null ? sprintf('ORD-%05d', $orderId) : '';
        $productId = $product !== null ? (int) $product->id : ($review->product_id !== null ? (int) $review->product_id : null);
        $buyerName = MailNotificationHelper::displayName($reviewer);
        $buyerCompany = '';
        $buyerCountry = '';

        if ($reviewer !== null) {
            $reviewer->loadMissing('company');
            $buyerCompany = trim((string) ($reviewer->company?->company_name ?? ''));
            $buyerCountry = trim((string) ($reviewer->company?->country ?? ''));
        }

        return [
            'orderId' => $orderId,
            'orderNumber' => $orderNumber,
            'productId' => $productId,
            'productName' => $product?->name ?? '',
            'buyerName' => $buyerName,
            'buyerCompany' => $buyerCompany,
            'buyerCountry' => $buyerCountry,
            'buyerInitials' => MailNotificationHelper::initials($buyerName),
            'rating' => (int) $review->rating,
            'reviewTitle' => $localized['title'] ?? $review->title ?? '',
            'reviewBody' => $localized['comment'] ?? $review->comment ?? '',
            'reviewDate' => $review->created_at?->format('M j, Y') ?? '',
            'ctaUrl' => MailNotificationHelper::productReviewsUrl($productId),
            'manufacturerName' => MailNotificationHelper::companyOrName($review->user),
        ];
    }

    private function listQuery(IndexAdminReviewRequest $request): Builder
    {
        $query = Review::query()
            ->with($this->listRelations())
            ->latest('id');

        if ($request->filled('status')) {
            $query->where('status', $request->string('status')->toString());
        }

        if ($request->filled('rating')) {
            $query->where('rating', $request->integer('rating'));
        }

        if ($request->filled('search')) {
            $searchTerm = trim($request->string('search')->toString());

            $query->where(function (Builder $builder) use ($searchTerm): void {
                $builder
                    ->where('title', 'like', "%{$searchTerm}%")
                    ->orWhere('comment', 'like', "%{$searchTerm}%")
                    ->orWhereHas('translations', function (Builder $translation) use ($searchTerm): void {
                        $translation
                            ->where('title', 'like', "%{$searchTerm}%")
                            ->orWhere('comment', 'like', "%{$searchTerm}%");
                    })
                    ->orWhereHas('reviewer', function (Builder $reviewer) use ($searchTerm): void {
                        $reviewer
                            ->where('first_name', 'like', "%{$searchTerm}%")
                            ->orWhere('last_name', 'like', "%{$searchTerm}%")
                            ->orWhereHas('company', fn (Builder $company) => $company
                                ->where('company_name', 'like', "%{$searchTerm}%"));
                    })
                    ->orWhereHas('user.company', fn (Builder $company) => $company
                        ->where('company_name', 'like', "%{$searchTerm}%"));
            });
        }

        return $query;
    }
}
