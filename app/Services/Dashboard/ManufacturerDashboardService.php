<?php

namespace App\Services\Dashboard;

use App\Enums\DashboardEventType;
use App\Enums\RfqSubmissionStatus;
use App\Enums\OrderStatus;
use App\Models\Company;
use App\Models\DashboardEvent;
use App\Models\Conversation;
use App\Models\Order;
use App\Models\Product;
use App\Models\Review;
use App\Models\RfqSubmission;
use App\Models\User;
use App\Models\UserNotification;
use App\Services\Manufacturer\ManufacturerProductStatsService;

class ManufacturerDashboardService
{
    use BuildsDashboardMetrics;

    public function __construct(
        private readonly ManufacturerProductStatsService $productStatsService,
    ) {}

    /**
     * @return array<string, mixed>
     */
    public function overview(User $manufacturer): array
    {
        $manufacturer->loadMissing('company');

        return [
            'profile_completeness' => $this->profileCompleteness($manufacturer->company),
            'stats' => $this->stats($manufacturer),
            'recent_inquiries' => $this->recentInquiries($manufacturer),
            'response_metrics' => $this->responseMetrics($manufacturer),
            'quick_stats' => $this->quickStats($manufacturer),
            'recent_activity' => $this->recentActivity($manufacturer),
        ];
    }

    /**
     * @return array{percent: int, label: string}
     */
    private function profileCompleteness(?Company $company): array
    {
        if ($company === null) {
            return ['percent' => 0, 'label' => '0% Complete'];
        }

        $fields = [
            $company->company_name,
            $company->short_description,
            $company->long_description,
            $company->country,
            $company->city,
            $company->street_address,
            $company->phone,
            $company->company_logo,
            $company->company_type,
            $company->company_established,
            $company->company_size,
        ];

        $filled = collect($fields)->filter(fn ($value) => is_string($value) && trim($value) !== '')->count();
        $percent = (int) round(($filled / count($fields)) * 100);

        return [
            'percent' => $percent,
            'label' => $percent.'% Complete',
        ];
    }

    /**
     * @return array<string, mixed>
     */
    private function stats(User $manufacturer): array
    {
        $manufacturerId = (int) $manufacturer->id;
        $now = now();
        $currentStart = $now->copy()->subDays(30);
        $previousStart = $now->copy()->subDays(60);
        $previousEnd = $now->copy()->subDays(30);

        $inquiriesCurrent = RfqSubmission::query()
            ->where('manufacturer_id', $manufacturerId)
            ->where('created_at', '>=', $currentStart)
            ->count();

        $inquiriesPrevious = RfqSubmission::query()
            ->where('manufacturer_id', $manufacturerId)
            ->whereBetween('created_at', [$previousStart, $previousEnd])
            ->count();

        $productStats = $this->productStatsService->getStats($manufacturer);
        $profileViews = DashboardEvent::query()
            ->where('counterparty_user_id', $manufacturerId)
            ->where('event_type', DashboardEventType::ProductViewed->value)
            ->where('occurred_at', '>=', $currentStart)
            ->count();

        $quoteValueCurrent = (float) RfqSubmission::query()
            ->where('manufacturer_id', $manufacturerId)
            ->whereNotNull('quoted_price')
            ->where('quoted_at', '>=', $currentStart)
            ->sum('quoted_price');

        $reviews = Review::query()->where('user_id', $manufacturerId)->get(['rating']);
        $reviewCount = $reviews->count();
        $avgRating = $reviewCount > 0 ? round((float) $reviews->avg('rating'), 1) : 0.0;

        $inquiryTrend = $this->metricWithTrend($inquiriesCurrent, $inquiriesPrevious);

        return [
            'new_inquiries_30d' => [
                'value' => $inquiriesCurrent,
                'change' => $inquiryTrend['change'],
                'trend' => $inquiryTrend['trend'],
                'label' => 'New Inquiries (30d)',
            ],
            'profile_views_30d' => [
                'value' => $profileViews,
                'change' => '0.0%',
                'trend' => 'up',
                'label' => 'Profile Views (30d)',
            ],
            'quote_value_30d' => [
                'value' => $quoteValueCurrent,
                'formatted' => $this->formatCurrencyShort($quoteValueCurrent),
                'label' => 'Quote Value (30d)',
            ],
            'average_rating' => [
                'value' => $avgRating,
                'review_count' => $reviewCount,
                'label' => 'Average Rating',
            ],
        ];
    }

    private function formatCurrencyShort(float $amount): string
    {
        if ($amount >= 1_000_000) {
            return '$'.round($amount / 1_000_000, 1).'M';
        }

        if ($amount >= 1_000) {
            return '$'.round($amount / 1_000).'K';
        }

        return '$'.number_format($amount, 0);
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    private function recentInquiries(User $manufacturer, int $limit = 4): array
    {
        return RfqSubmission::query()
            ->where('manufacturer_id', $manufacturer->id)
            ->with(['buyer.company', 'product'])
            ->latest('id')
            ->limit($limit)
            ->get()
            ->map(function (RfqSubmission $rfq): array {
                $status = $rfq->status instanceof RfqSubmissionStatus
                    ? $rfq->status->value
                    : (string) $rfq->status;

                $buyerName = $rfq->buyer?->company?->company_name
                    ?: trim(($rfq->buyer?->first_name ?? '').' '.($rfq->buyer?->last_name ?? ''));

                $quantity = trim(($rfq->quantity ?? '').' '.($rfq->quantity_unit ?? ''));

                return [
                    'id' => (string) $rfq->id,
                    'buyer' => $buyerName,
                    'product' => $rfq->product?->name,
                    'quantity' => $quantity,
                    'time' => $rfq->created_at?->diffForHumans(),
                    'created_at' => $rfq->created_at?->toIso8601String(),
                    'status' => $this->manufacturerInquiryStatusLabel($status),
                    'status_value' => $status,
                ];
            })
            ->values()
            ->all();
    }

    /**
     * @return array<string, mixed>
     */
    private function responseMetrics(User $manufacturer): array
    {
        $manufacturerId = (int) $manufacturer->id;

        $total = RfqSubmission::query()->where('manufacturer_id', $manufacturerId)->count();
        $responded = RfqSubmission::query()
            ->where('manufacturer_id', $manufacturerId)
            ->whereIn('status', [
                RfqSubmissionStatus::Quoted->value,
                RfqSubmissionStatus::Accepted->value,
                RfqSubmissionStatus::InReview->value,
            ])
            ->count();

        $quoted = RfqSubmission::query()
            ->where('manufacturer_id', $manufacturerId)
            ->where('status', RfqSubmissionStatus::Quoted->value)
            ->count();

        $accepted = RfqSubmission::query()
            ->where('manufacturer_id', $manufacturerId)
            ->where('status', RfqSubmissionStatus::Accepted->value)
            ->count();

        $responseRate = $total > 0 ? (int) round(($responded / $total) * 100) : 0;
        $quoteConversion = $quoted > 0 ? (int) round(($accepted / $quoted) * 100) : 0;
        $onTimeDelivery = $this->onTimeDeliveryRate($manufacturerId);

        return [
            'response_rate' => $responseRate,
            'quote_conversion' => $quoteConversion,
            'on_time_delivery' => $onTimeDelivery,
        ];
    }

    /**
     * @return array<string, mixed>
     */
    private function quickStats(User $manufacturer): array
    {
        $manufacturerId = (int) $manufacturer->id;
        $productStats = $this->productStatsService->getStats($manufacturer);

        $pendingQuotes = RfqSubmission::query()
            ->where('manufacturer_id', $manufacturerId)
            ->where('status', RfqSubmissionStatus::Pending->value)
            ->count();

        $unreadMessages = $this->unreadConversationCount($manufacturer);

        return [
            'active_products' => $productStats['active_products'],
            'pending_quotes' => $pendingQuotes,
            'unread_messages' => $unreadMessages,
            'avg_response_time' => $this->averageResponseTimeLabel($manufacturerId),
            'avg_response_time_seconds' => $this->averageResponseTimeSeconds($manufacturerId),
        ];
    }

    private function unreadConversationCount(User $manufacturer): int
    {
        $userId = (int) $manufacturer->id;

        return (int) Conversation::query()
            ->forParticipant($manufacturer)
            ->whereRaw(
                '(
                    SELECT MAX(m.id) FROM messages m WHERE m.conversation_id = conversations.id
                ) > COALESCE((
                    SELECT cp.last_read_message_id
                    FROM conversation_participants cp
                    WHERE cp.conversation_id = conversations.id AND cp.user_id = ?
                    LIMIT 1
                ), 0)',
                [$userId]
            )
            ->count();
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    private function recentActivity(User $manufacturer, int $limit = 5): array
    {
        $activities = DashboardEvent::query()
            ->where('actor_user_id', $manufacturer->id)
            ->latest('occurred_at')
            ->limit($limit)
            ->get()
            ->map(function (DashboardEvent $event): array {
                return [
                    'type' => $event->event_type,
                    'action' => $this->manufacturerActivityAction($event),
                    'time' => $event->occurred_at?->diffForHumans(),
                    'time_at' => $event->occurred_at?->toIso8601String(),
                ];
            });

        UserNotification::query()
            ->where('user_id', $manufacturer->id)
            ->latest('id')
            ->limit(3)
            ->get()
            ->each(function (UserNotification $notification) use ($activities): void {
                $activities->push([
                    'type' => 'notification',
                    'action' => $notification->title,
                    'time' => $notification->created_at?->diffForHumans(),
                    'time_at' => $notification->created_at?->toIso8601String(),
                ]);
            });

        return $activities
            ->sortByDesc(fn (array $item) => $item['time_at'])
            ->take($limit)
            ->values()
            ->all();
    }

    private function manufacturerActivityAction(DashboardEvent $event): string
    {
        return match ($event->event_type) {
            DashboardEventType::RfqQuoted->value => 'Quote sent for RFQ #'.$event->entity_id,
            DashboardEventType::RfqReplied->value => 'Replied to RFQ #'.$event->entity_id,
            DashboardEventType::MessageSent->value => 'Sent a message',
            DashboardEventType::OrderDelivered->value => 'Order #'.$event->entity_id.' marked delivered',
            DashboardEventType::ProductViewed->value => 'Product #'.$event->entity_id.' viewed',
            default => ucfirst(str_replace('_', ' ', $event->event_type)),
        };
    }

    private function averageResponseTimeSeconds(int $manufacturerId): ?int
    {
        $responses = RfqSubmission::query()
            ->where('manufacturer_id', $manufacturerId)
            ->whereNotNull('first_manufacturer_response_at')
            ->get(['created_at', 'first_manufacturer_response_at'])
            ->map(function (RfqSubmission $rfq): ?int {
                if ($rfq->first_manufacturer_response_at === null || $rfq->created_at === null) {
                    return null;
                }

                return abs($rfq->created_at->diffInSeconds($rfq->first_manufacturer_response_at));
            })
            ->filter(fn (?int $seconds) => $seconds !== null)
            ->values();

        if ($responses->isEmpty()) {
            return null;
        }

        return (int) round((float) $responses->avg());
    }

    private function averageResponseTimeLabel(int $manufacturerId): ?string
    {
        $seconds = $this->averageResponseTimeSeconds($manufacturerId);

        if ($seconds === null) {
            return null;
        }

        if ($seconds < 60) {
            return $seconds.' sec';
        }

        if ($seconds < 3600) {
            return (int) round($seconds / 60).' min';
        }

        return (int) round($seconds / 3600).' hours';
    }

    private function onTimeDeliveryRate(int $manufacturerId): ?int
    {
        $baseQuery = Order::query()
            ->where('manufacturer_id', $manufacturerId)
            ->whereNotNull('estimated_delivery_at')
            ->whereNotNull('delivered_at')
            ->where('status', OrderStatus::Completed->value);

        $total = (clone $baseQuery)->count();
        if ($total === 0) {
            return null;
        }

        $onTime = (clone $baseQuery)
            ->whereRaw('date(delivered_at) <= date(estimated_delivery_at)')
            ->count();

        return (int) round(($onTime / $total) * 100);
    }
}
