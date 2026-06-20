<?php

namespace App\Services\Analytics;

use App\Enums\DashboardEventType;
use App\Enums\RfqSubmissionStatus;
use App\Models\DashboardEvent;
use App\Models\Message;
use App\Models\Order;
use App\Models\Product;
use App\Models\RfqSubmission;
use App\Models\User;
use App\Services\Dashboard\BuildsDashboardMetrics;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Pagination\LengthAwarePaginator as Paginator;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

class ManufacturerAnalyticsService
{
    use BuildsDashboardMetrics;

    /**
     * @param  array{
     *     current_start: Carbon,
     *     current_end: Carbon,
     *     previous_start: Carbon,
     *     previous_end: Carbon,
     *     period: string
     * }  $range
     * @return array<string, mixed>
     */
    public function metrics(User $manufacturer, array $range): array
    {
        $manufacturerId = (int) $manufacturer->id;

        $profileViewsCurrent = $this->countProfileViews($manufacturerId, $range['current_start'], $range['current_end']);
        $profileViewsPrevious = $this->countProfileViews($manufacturerId, $range['previous_start'], $range['previous_end']);

        $inquiriesCurrent = $this->countInquiries($manufacturerId, $range['current_start'], $range['current_end']);
        $inquiriesPrevious = $this->countInquiries($manufacturerId, $range['previous_start'], $range['previous_end']);

        $messagesCurrent = $this->countIncomingMessages($manufacturerId, $range['current_start'], $range['current_end']);
        $messagesPrevious = $this->countIncomingMessages($manufacturerId, $range['previous_start'], $range['previous_end']);

        $quoteRequestsCurrent = $this->countQuoteRequests($manufacturerId, $range['current_start'], $range['current_end']);
        $quoteRequestsPrevious = $this->countQuoteRequests($manufacturerId, $range['previous_start'], $range['previous_end']);

        return [
            'period' => $range['period'],
            'date_from' => $range['current_start']->toDateString(),
            'date_to' => $range['current_end']->toDateString(),
            'metrics' => [
                $this->metricCard(
                    key: 'profile_views',
                    label: 'Profile Views',
                    rawValue: $profileViewsCurrent,
                    trend: $this->metricWithTrend($profileViewsCurrent, $profileViewsPrevious),
                ),
                $this->metricCard(
                    key: 'inquiries_received',
                    label: 'Inquiries Received',
                    rawValue: $inquiriesCurrent,
                    trend: $this->metricWithTrend($inquiriesCurrent, $inquiriesPrevious),
                ),
                $this->metricCard(
                    key: 'messages',
                    label: 'Messages',
                    rawValue: $messagesCurrent,
                    trend: $this->metricWithTrend($messagesCurrent, $messagesPrevious),
                ),
                $this->metricCard(
                    key: 'quote_requests',
                    label: 'Quote Requests',
                    rawValue: $quoteRequestsCurrent,
                    trend: $this->metricWithTrend($quoteRequestsCurrent, $quoteRequestsPrevious),
                ),
            ],
        ];
    }

    /**
     * Performance overview rows for chart/table (paginated).
     *
     * @param  array{
     *     current_start: Carbon,
     *     current_end: Carbon,
     *     previous_start: Carbon,
     *     previous_end: Carbon,
     *     period: string
     * }  $range
     */
    public function performance(
        User $manufacturer,
        array $range,
        ?string $search,
        string $orderBy,
        string $orderDirection,
        int $perPage,
        int $page,
    ): LengthAwarePaginator {
        $manufacturerId = (int) $manufacturer->id;
        $buckets = $this->buildPerformanceBuckets($range);
        $rows = collect();

        foreach ($buckets as $bucket) {
            /** @var Carbon $bucketStart */
            $bucketStart = $bucket['start'];
            /** @var Carbon $bucketEnd */
            $bucketEnd = $bucket['end'];

            if ($search !== null) {
                $needle = strtolower($search);
                $matches = str_contains(strtolower($bucket['label']), $needle)
                    || str_contains(strtolower($bucket['period']), $needle);

                if (! $matches) {
                    continue;
                }
            }

            $rows->push([
                'name' => $bucket['label'],
                'period' => $bucket['period'],
                'date_from' => $bucketStart->toDateString(),
                'date_to' => $bucketEnd->toDateString(),
                'profile_views' => $this->countProfileViews($manufacturerId, $bucketStart, $bucketEnd),
                'inquiries' => $this->countInquiries($manufacturerId, $bucketStart, $bucketEnd),
                'messages' => $this->countIncomingMessages($manufacturerId, $bucketStart, $bucketEnd),
                'quote_requests' => $this->countQuoteRequests($manufacturerId, $bucketStart, $bucketEnd),
            ]);
        }

        $sorted = $this->sortRows($rows, $orderBy, $orderDirection, [
            'period' => 'period',
            'profile_views' => 'profile_views',
            'inquiries' => 'inquiries',
            'messages' => 'messages',
            'quote_requests' => 'quote_requests',
        ]);

        return $this->paginateCollection($sorted, $perPage, $page, 'page');
    }

    /**
     * @param  array{
     *     current_start: Carbon,
     *     current_end: Carbon,
     *     previous_start: Carbon,
     *     previous_end: Carbon,
     *     period: string
     * }  $range
     */
    public function products(
        User $manufacturer,
        array $range,
        ?string $search,
        string $orderBy,
        string $orderDirection,
        int $perPage,
        int $page,
    ): LengthAwarePaginator {
        $manufacturerId = (int) $manufacturer->id;
        $start = $range['current_start'];
        $end = $range['current_end'];

        $viewCounts = DashboardEvent::query()
            ->where('counterparty_user_id', $manufacturerId)
            ->where('event_type', DashboardEventType::ProductViewed->value)
            ->where('entity_type', 'product')
            ->whereBetween('occurred_at', [$start, $end])
            ->selectRaw('entity_id, COUNT(*) as views_count')
            ->groupBy('entity_id')
            ->pluck('views_count', 'entity_id');

        $inquiryCounts = RfqSubmission::query()
            ->where('manufacturer_id', $manufacturerId)
            ->whereBetween('created_at', [$start, $end])
            ->selectRaw('product_id, COUNT(*) as inquiries_count')
            ->groupBy('product_id')
            ->pluck('inquiries_count', 'product_id');

        $productIds = $viewCounts->keys()
            ->merge($inquiryCounts->keys())
            ->unique()
            ->values();

        $products = Product::query()
            ->where('user_id', $manufacturerId)
            ->whereIn('id', $productIds)
            ->when($search, fn ($query) => $query->where('name', 'like', "%{$search}%"))
            ->get(['id', 'name', 'slug']);

        $rows = $products->map(function (Product $product) use ($viewCounts, $inquiryCounts): array {
            $views = (int) ($viewCounts[$product->id] ?? 0);
            $inquiries = (int) ($inquiryCounts[$product->id] ?? 0);

            return [
                'id' => $product->id,
                'name' => $product->name,
                'slug' => $product->slug,
                'views' => $views,
                'views_formatted' => number_format($views),
                'inquiries' => $inquiries,
            ];
        });

        if ($search !== null && $products->isEmpty()) {
            $rows = Product::query()
                ->where('user_id', $manufacturerId)
                ->where('name', 'like', "%{$search}%")
                ->get(['id', 'name', 'slug'])
                ->map(fn (Product $product): array => [
                    'id' => $product->id,
                    'name' => $product->name,
                    'slug' => $product->slug,
                    'views' => 0,
                    'views_formatted' => '0',
                    'inquiries' => 0,
                ]);
        }

        $sorted = $this->sortRows($rows, $orderBy, $orderDirection, [
            'name' => 'name',
            'views' => 'views',
            'inquiries' => 'inquiries',
        ]);

        return $this->paginateCollection($sorted, $perPage, $page, 'page');
    }

    /**
     * @param  array{
     *     current_start: Carbon,
     *     current_end: Carbon,
     *     previous_start: Carbon,
     *     previous_end: Carbon,
     *     period: string
     * }  $range
     */
    public function countries(
        User $manufacturer,
        array $range,
        ?string $search,
        string $orderBy,
        string $orderDirection,
        int $perPage,
        int $page,
    ): LengthAwarePaginator {
        $manufacturerId = (int) $manufacturer->id;
        $start = $range['current_start']->copy();
        $end = $range['current_end']->copy();

        $rfqCountryRows = DB::select(
            'SELECT TRIM(companies.country) as country, COUNT(DISTINCT buyers.id) as buyers_count
             FROM rfq_submissions
             INNER JOIN users AS buyers ON buyers.id = rfq_submissions.buyer_id
             INNER JOIN companies ON companies.user_id = buyers.id
             WHERE rfq_submissions.manufacturer_id = ?
               AND rfq_submissions.created_at BETWEEN ? AND ?
               AND companies.country IS NOT NULL
               AND companies.country != \'\'
             GROUP BY TRIM(companies.country)',
            [$manufacturerId, $start, $end],
        );

        $messageCountryRows = [];

        try {
            $messageCountryRows = DB::select(
                'SELECT TRIM(companies.country) as country, COUNT(DISTINCT buyers.id) as buyers_count
                 FROM messages
                 INNER JOIN conversation_participants ON conversation_participants.conversation_id = messages.conversation_id
                     AND conversation_participants.user_id = ?
                 INNER JOIN users AS buyers ON buyers.id = messages.sender_id
                 INNER JOIN companies ON companies.user_id = buyers.id
                 WHERE messages.sender_id != ?
                   AND messages.created_at BETWEEN ? AND ?
                   AND companies.country IS NOT NULL
                   AND companies.country != \'\'
                 GROUP BY TRIM(companies.country)',
                [$manufacturerId, $manufacturerId, $start, $end],
            );
        } catch (\Throwable) {
            $messageCountryRows = [];
        }

        $merged = collect();

        foreach (array_merge($rfqCountryRows, $messageCountryRows) as $row) {
            $country = trim((string) $row->country);

            if ($country === '') {
                continue;
            }

            $merged->put($country, $merged->get($country, 0) + (int) $row->buyers_count);
        }

        $totalBuyers = (int) $merged->sum();

        $rows = $merged
            ->map(function ($count, $country) use ($totalBuyers, $search): ?array {
                if ($search !== null && ! str_contains(strtolower((string) $country), strtolower($search))) {
                    return null;
                }

                $buyerCount = (int) $count;
                $percentage = $totalBuyers > 0 ? round(($buyerCount / $totalBuyers) * 100, 1) : 0.0;

                return [
                    'country' => (string) $country,
                    'country_code' => $this->resolveCountryCode((string) $country),
                    'buyers' => number_format($buyerCount),
                    'raw_buyers' => $buyerCount,
                    'percentage' => $percentage,
                ];
            })
            ->filter(fn (?array $row) => $row !== null)
            ->values();

        $sorted = $this->sortRows($rows, $orderBy, $orderDirection, [
            'country' => 'country',
            'buyers' => 'raw_buyers',
            'percentage' => 'percentage',
        ]);

        $paginator = $this->paginateCollection($sorted, $perPage, $page, 'page');
        $paginator->appends(['total_buyers' => $totalBuyers]);

        return $paginator;
    }

    /**
     * @param  array{
     *     current_start: Carbon,
     *     current_end: Carbon,
     *     previous_start: Carbon,
     *     previous_end: Carbon,
     *     period: string
     * }  $range
     * @return array<string, mixed>
     */
    public function funnel(User $manufacturer, array $range): array
    {
        $manufacturerId = (int) $manufacturer->id;
        $start = $range['current_start'];
        $end = $range['current_end'];

        $profileViews = $this->countProfileViews($manufacturerId, $start, $end);
        $messagesStarted = $this->countBuyerInitiatedMessages($manufacturerId, $start, $end);
        $quotesSent = $this->countQuoteRequests($manufacturerId, $start, $end);
        $ordersReceived = Order::query()
            ->where('manufacturer_id', $manufacturerId)
            ->whereBetween('created_at', [$start, $end])
            ->count();

        $messagesConversion = $profileViews > 0
            ? round(($messagesStarted / $profileViews) * 100, 1)
            : 0.0;
        $quotesConversion = $messagesStarted > 0
            ? round(($quotesSent / $messagesStarted) * 100, 1)
            : 0.0;
        $ordersConversion = $quotesSent > 0
            ? round(($ordersReceived / $quotesSent) * 100, 1)
            : 0.0;

        return [
            'period' => $range['period'],
            'date_from' => $start->toDateString(),
            'date_to' => $end->toDateString(),
            'steps' => [
                $this->funnelStep(
                    key: 'profile_views',
                    label: 'Profile Views',
                    value: $profileViews,
                    conversion: null,
                ),
                $this->funnelStep(
                    key: 'messages_started',
                    label: 'Messages Started',
                    value: $messagesStarted,
                    conversion: $messagesConversion,
                ),
                $this->funnelStep(
                    key: 'quotes_sent',
                    label: 'Quotes Sent',
                    value: $quotesSent,
                    conversion: $quotesConversion,
                ),
                $this->funnelStep(
                    key: 'orders_received',
                    label: 'Orders Received',
                    value: $ordersReceived,
                    conversion: $ordersConversion,
                ),
            ],
        ];
    }

    private function countProfileViews(int $manufacturerId, Carbon $start, Carbon $end): int
    {
        return DashboardEvent::query()
            ->where('counterparty_user_id', $manufacturerId)
            ->whereIn('event_type', [
                DashboardEventType::ProductViewed->value,
                DashboardEventType::SupplierViewed->value,
            ])
            ->whereBetween('occurred_at', [$start, $end])
            ->count();
    }

    private function countInquiries(int $manufacturerId, Carbon $start, Carbon $end): int
    {
        return RfqSubmission::query()
            ->where('manufacturer_id', $manufacturerId)
            ->whereBetween('created_at', [$start, $end])
            ->count();
    }

    private function countIncomingMessages(int $manufacturerId, Carbon $start, Carbon $end): int
    {
        return Message::query()
            ->where('sender_id', '!=', $manufacturerId)
            ->whereBetween('created_at', [$start, $end])
            ->whereHas('conversation.participants', fn ($query) => $query->where('users.id', $manufacturerId))
            ->count();
    }

    private function countBuyerInitiatedMessages(int $manufacturerId, Carbon $start, Carbon $end): int
    {
        return (int) DashboardEvent::query()
            ->where('counterparty_user_id', $manufacturerId)
            ->where('event_type', DashboardEventType::MessageSent->value)
            ->where('actor_user_id', '!=', $manufacturerId)
            ->whereBetween('occurred_at', [$start, $end])
            ->distinct('entity_id')
            ->count('entity_id');
    }

    private function countQuoteRequests(int $manufacturerId, Carbon $start, Carbon $end): int
    {
        return RfqSubmission::query()
            ->where('manufacturer_id', $manufacturerId)
            ->whereNotNull('quoted_at')
            ->whereBetween('quoted_at', [$start, $end])
            ->whereIn('status', [
                RfqSubmissionStatus::Quoted->value,
                RfqSubmissionStatus::Accepted->value,
                RfqSubmissionStatus::InReview->value,
            ])
            ->count();
    }

    /**
     * @param  array{
     *     current_start: Carbon,
     *     current_end: Carbon,
     *     previous_start: Carbon,
     *     previous_end: Carbon,
     *     period: string
     * }  $range
     * @return array<int, array{label: string, period: string, start: Carbon, end: Carbon}>
     */
    private function buildPerformanceBuckets(array $range): array
    {
        $start = $range['current_start']->copy();
        $end = $range['current_end']->copy();
        $period = $range['period'];
        $buckets = [];

        if (in_array($period, ['last_7_days', 'last_30_days', 'custom'], true)) {
            $cursor = $start->copy();

            while ($cursor <= $end) {
                $bucketStart = $cursor->copy()->startOfDay();
                $bucketEnd = $cursor->copy()->endOfDay();
                $buckets[] = [
                    'label' => $cursor->format('M j'),
                    'period' => $cursor->format('Y-m-d'),
                    'start' => $bucketStart,
                    'end' => $bucketEnd,
                ];
                $cursor->addDay();
            }

            return $buckets;
        }

        if ($period === 'last_90_days') {
            $cursor = $start->copy()->startOfWeek();

            while ($cursor <= $end) {
                $bucketStart = $cursor->copy()->startOfDay();
                $bucketEnd = $cursor->copy()->endOfWeek()->endOfDay();
                if ($bucketEnd > $end) {
                    $bucketEnd = $end->copy();
                }
                $buckets[] = [
                    'label' => $bucketStart->format('M j').' - '.$bucketEnd->format('M j'),
                    'period' => $bucketStart->format('Y-m-d').'_'.$bucketEnd->format('Y-m-d'),
                    'start' => $bucketStart,
                    'end' => $bucketEnd,
                ];
                $cursor->addWeek();
            }

            return $buckets;
        }

        $cursor = $start->copy()->startOfMonth();

        while ($cursor <= $end) {
            $bucketStart = $cursor->copy()->startOfMonth();
            $bucketEnd = $cursor->copy()->endOfMonth()->endOfDay();
            if ($bucketEnd > $end) {
                $bucketEnd = $end->copy();
            }
            $buckets[] = [
                'label' => $bucketStart->format('M Y'),
                'period' => $bucketStart->format('Y-m'),
                'start' => $bucketStart,
                'end' => $bucketEnd,
            ];
            $cursor->addMonth();
        }

        return $buckets;
    }

    /**
     * @param  array{value: int|float, change: string, trend: string}  $trend
     * @return array<string, mixed>
     */
    private function metricCard(string $key, string $label, int|float $rawValue, array $trend): array
    {
        return [
            'key' => $key,
            'label' => $label,
            'value' => number_format((float) $rawValue),
            'raw_value' => $rawValue,
            'change' => $trend['change'],
            'trend' => $trend['trend'],
        ];
    }

    /**
     * @return array<string, mixed>
     */
    private function funnelStep(string $key, string $label, int $value, ?float $conversion): array
    {
        return [
            'key' => $key,
            'label' => $label,
            'value' => $value,
            'value_formatted' => number_format($value),
            'conversion' => $conversion,
            'conversion_label' => $conversion !== null ? number_format($conversion, 1).'% conversion' : null,
        ];
    }

    private function resolveCountryCode(string $country): ?string
    {
        $normalized = strtolower(trim($country));

        /** @var array<string, string> $map */
        $map = [
            'united states' => 'US',
            'germany' => 'DE',
            'united kingdom' => 'GB',
            'australia' => 'AU',
            'canada' => 'CA',
            'france' => 'FR',
            'italy' => 'IT',
            'spain' => 'ES',
            'netherlands' => 'NL',
            'china' => 'CN',
            'india' => 'IN',
            'japan' => 'JP',
            'brazil' => 'BR',
            'mexico' => 'MX',
            'bangladesh' => 'BD',
        ];

        return $map[$normalized] ?? null;
    }

    /**
     * @param  Collection<int, array<string, mixed>>  $rows
     * @param  array<string, string>  $columnMap
     * @return Collection<int, array<string, mixed>>
     */
    private function sortRows(
        Collection $rows,
        string $orderBy,
        string $orderDirection,
        array $columnMap,
    ): Collection {
        $column = $columnMap[$orderBy] ?? reset($columnMap);

        return $orderDirection === 'asc'
            ? $rows->sortBy($column)->values()
            : $rows->sortByDesc($column)->values();
    }

    /**
     * @param  Collection<int, array<string, mixed>>  $items
     */
    private function paginateCollection(
        Collection $items,
        int $perPage,
        int $page,
        string $pageName,
    ): LengthAwarePaginator {
        $total = $items->count();
        $results = $items->forPage($page, $perPage)->values();

        return new Paginator(
            $results,
            $total,
            $perPage,
            $page,
            [
                'path' => request()->url(),
                'pageName' => $pageName,
            ],
        );
    }
}
