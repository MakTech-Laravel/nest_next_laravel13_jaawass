<?php

namespace App\Services\Analytics;

use App\Enums\UserManuFactureStatus;
use App\Enums\UserRole;
use App\Enums\UserStatus;
use App\Models\Industry;
use App\Models\Message;
use App\Models\Payment;
use App\Models\Product;
use App\Models\RfqSubmission;
use App\Models\User;
use App\Services\Dashboard\BuildsDashboardMetrics;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Pagination\LengthAwarePaginator as Paginator;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;

class AdminAnalyticsService
{
    use BuildsDashboardMetrics;

    /**
     * Key metric cards for the analytics overview (maps to frontend metrics grid).
     *
     * @return array{
     *     period: string,
     *     date_from: string,
     *     date_to: string,
     *     metrics: array<int, array<string, mixed>>
     * }
     */
    public function metrics(array $range): array
    {
        $currentStart = $range['current_start'];
        $currentEnd = $range['current_end'];
        $previousStart = $range['previous_start'];
        $previousEnd = $range['previous_end'];

        $revenueCurrent = $this->sumPaidRevenue($currentStart, $currentEnd);
        $revenuePrevious = $this->sumPaidRevenue($previousStart, $previousEnd);
        $revenueTotal = $this->sumPaidRevenue();

        $activeUsers = $this->countActiveUsers();
        $activeUsersCurrent = $this->countActiveUsersRegisteredBetween($currentStart, $currentEnd);
        $activeUsersPrevious = $this->countActiveUsersRegisteredBetween($previousStart, $previousEnd);

        $activeSuppliers = $this->countActiveSuppliers();
        $suppliersCurrent = $this->countActiveSuppliersRegisteredBetween($currentStart, $currentEnd);
        $suppliersPrevious = $this->countActiveSuppliersRegisteredBetween($previousStart, $previousEnd);

        $productsListed = $this->countListedProducts();
        $productsCurrent = $this->countProductsCreatedBetween($currentStart, $currentEnd);
        $productsPrevious = $this->countProductsCreatedBetween($previousStart, $previousEnd);

        $rfqsCurrent = $this->countRfqsBetween($currentStart, $currentEnd);
        $rfqsPrevious = $this->countRfqsBetween($previousStart, $previousEnd);

        $messagesCurrent = $this->countMessagesBetween($currentStart, $currentEnd);
        $messagesPrevious = $this->countMessagesBetween($previousStart, $previousEnd);
        $messagesTotal = Message::query()->count();

        $revenueTrend = $this->metricWithTrend($revenueCurrent, $revenuePrevious);
        $usersTrend = $this->metricWithTrend($activeUsersCurrent, $activeUsersPrevious);
        $suppliersTrend = $this->metricWithTrend($suppliersCurrent, $suppliersPrevious);
        $productsTrend = $this->metricWithTrend($productsCurrent, $productsPrevious);
        $rfqsTrend = $this->metricWithTrend($rfqsCurrent, $rfqsPrevious);
        $messagesTrend = $this->metricWithTrend($messagesCurrent, $messagesPrevious);

        return [
            'period' => $range['period'],
            'date_from' => $currentStart->toDateString(),
            'date_to' => $currentEnd->toDateString(),
            'metrics' => [
                $this->metricCard(
                    key: 'total_revenue',
                    label: 'Total Revenue',
                    rawValue: $revenueTotal,
                    formattedValue: $this->formatCurrency((float) $revenueTotal),
                    trend: $revenueTrend,
                ),
                $this->metricCard(
                    key: 'active_users',
                    label: 'Active Users',
                    rawValue: $activeUsers,
                    formattedValue: number_format($activeUsers),
                    trend: $usersTrend,
                ),
                $this->metricCard(
                    key: 'active_suppliers',
                    label: 'Active Suppliers',
                    rawValue: $activeSuppliers,
                    formattedValue: number_format($activeSuppliers),
                    trend: $suppliersTrend,
                ),
                $this->metricCard(
                    key: 'products_listed',
                    label: 'Products Listed',
                    rawValue: $productsListed,
                    formattedValue: number_format($productsListed),
                    trend: $productsTrend,
                ),
                $this->metricCard(
                    key: 'rfqs_this_month',
                    label: 'RFQs This Month',
                    rawValue: $rfqsCurrent,
                    formattedValue: number_format($rfqsCurrent),
                    trend: $rfqsTrend,
                ),
                $this->metricCard(
                    key: 'messages_sent',
                    label: 'Messages Sent',
                    rawValue: $messagesTotal,
                    formattedValue: number_format($messagesTotal),
                    trend: $messagesTrend,
                ),
            ],
        ];
    }

    /**
     * Monthly platform growth rows for the bar chart.
     */
    public function growth(
        ?string $search,
        ?int $year,
        int $months,
        string $orderBy,
        string $orderDirection,
        int $perPage,
        int $page,
    ): LengthAwarePaginator {
        $end = $year !== null
            ? Carbon::create($year, 12, 31)->endOfMonth()
            : now()->endOfMonth();
        $start = $end->copy()->subMonths($months - 1)->startOfMonth();

        $rows = collect();
        $cursor = $start->copy();

        while ($cursor <= $end) {
            $monthStart = $cursor->copy()->startOfMonth();
            $monthEnd = $cursor->copy()->endOfMonth();
            $period = $monthStart->format('Y-m');
            $label = $monthStart->format('M');

            if ($search !== null) {
                $needle = strtolower($search);
                $matches = str_contains(strtolower($label), $needle)
                    || str_contains(strtolower($period), $needle)
                    || str_contains((string) $monthStart->year, $needle);

                if (! $matches) {
                    $cursor->addMonth();

                    continue;
                }
            }

            $rows->push([
                'name' => $label,
                'period' => $period,
                'year' => (int) $monthStart->year,
                'users' => $this->countUsersRegisteredBetween($monthStart, $monthEnd),
                'suppliers' => $this->countActiveSuppliersRegisteredBetween($monthStart, $monthEnd),
                'rfqs' => $this->countRfqsBetween($monthStart, $monthEnd),
            ]);

            $cursor->addMonth();
        }

        $sorted = $this->sortRows($rows, $orderBy, $orderDirection, [
            'period' => 'period',
            'users' => 'users',
            'suppliers' => 'suppliers',
            'rfqs' => 'rfqs',
        ]);

        return $this->paginateCollection($sorted, $perPage, $page, 'page');
    }

    /**
     * User distribution by country.
     */
    public function countries(
        ?string $search,
        string $roleFilter,
        string $orderBy,
        string $orderDirection,
        int $perPage,
        int $page,
    ): LengthAwarePaginator {
        $query = User::query()
            ->where('users.status', UserStatus::ACTIVE->value)
            ->where('users.role', '!=', UserRole::ADMIN->value)
            ->join('companies', 'companies.user_id', '=', 'users.id')
            ->whereNotNull('companies.country')
            ->where('companies.country', '!=', '');

        if ($roleFilter === UserRole::BUYER->value) {
            $query->where('users.role', UserRole::BUYER->value);
        } elseif ($roleFilter === UserRole::MANUFACTURER->value) {
            $query->where('users.role', UserRole::MANUFACTURER->value);
        }

        if ($search !== null) {
            $query->where('companies.country', 'like', "%{$search}%");
        }

        $grouped = $query
            ->selectRaw('companies.country as country, COUNT(users.id) as users_count')
            ->groupBy('companies.country')
            ->get();

        $totalUsers = (int) $grouped->sum('users_count');

        $rows = $grouped->map(function ($row) use ($totalUsers): array {
            $count = (int) $row->users_count;
            $percentage = $totalUsers > 0 ? round(($count / $totalUsers) * 100, 1) : 0.0;

            return [
                'country' => (string) $row->country,
                'users' => number_format($count),
                'raw_users' => $count,
                'percentage' => $percentage,
            ];
        });

        $sorted = $this->sortRows($rows, $orderBy, $orderDirection, [
            'country' => 'country',
            'users' => 'raw_users',
            'percentage' => 'percentage',
        ]);

        $paginator = $this->paginateCollection($sorted, $perPage, $page, 'page');
        $paginator->appends([
            'total_users' => $totalUsers,
        ]);

        return $paginator;
    }

    /**
     * Top industries by suppliers and products.
     */
    public function industries(
        ?string $search,
        string $orderBy,
        string $orderDirection,
        int $perPage,
        int $page,
    ): LengthAwarePaginator {
        $rows = Industry::query()
            ->when($search, function ($query) use ($search): void {
                $query->where('name', 'like', "%{$search}%");
            })
            ->get()
            ->map(function (Industry $industry): array {
                $productsQuery = Product::query()
                    ->where('industry_id', $industry->id)
                    ->where('status', 'active')
                    ->where('is_approved', true);

                $suppliersQuery = (clone $productsQuery)
                    ->whereHas('user', function ($query): void {
                        $query->isManufacturer()
                            ->where('status', UserStatus::ACTIVE->value)
                            ->where('manufacture_status', UserManuFactureStatus::APPROVED->value);
                    });

                return [
                    'id' => $industry->id,
                    'industry' => $industry->name,
                    'slug' => $industry->slug,
                    'suppliers' => (int) $suppliersQuery->distinct()->count('user_id'),
                    'products' => (int) $productsQuery->count(),
                ];
            });

        $sorted = $this->sortRows($rows, $orderBy, $orderDirection, [
            'industry' => 'industry',
            'suppliers' => 'suppliers',
            'products' => 'products',
        ]);

        return $this->paginateCollection($sorted, $perPage, $page, 'page');
    }

    private function sumPaidRevenue(?Carbon $start = null, ?Carbon $end = null): float
    {
        $query = Payment::query()->where('status', 'paid');

        if ($start !== null && $end !== null) {
            $query->whereBetween('created_at', [$start, $end]);
        }

        return (float) $query->sum('amount');
    }

    private function countActiveUsers(): int
    {
        return User::query()
            ->where('status', UserStatus::ACTIVE->value)
            ->where('role', '!=', UserRole::ADMIN->value)
            ->count();
    }

    private function countActiveUsersRegisteredBetween(Carbon $start, Carbon $end): int
    {
        return User::query()
            ->where('status', UserStatus::ACTIVE->value)
            ->where('role', '!=', UserRole::ADMIN->value)
            ->whereBetween('created_at', [$start, $end])
            ->count();
    }

    private function countUsersRegisteredBetween(Carbon $start, Carbon $end): int
    {
        return User::query()
            ->where('role', '!=', UserRole::ADMIN->value)
            ->whereBetween('created_at', [$start, $end])
            ->count();
    }

    private function countActiveSuppliers(): int
    {
        return User::query()
            ->isManufacturer()
            ->where('status', UserStatus::ACTIVE->value)
            ->where('manufacture_status', UserManuFactureStatus::APPROVED->value)
            ->count();
    }

    private function countActiveSuppliersRegisteredBetween(Carbon $start, Carbon $end): int
    {
        return User::query()
            ->isManufacturer()
            ->where('status', UserStatus::ACTIVE->value)
            ->where('manufacture_status', UserManuFactureStatus::APPROVED->value)
            ->whereBetween('created_at', [$start, $end])
            ->count();
    }

    private function countListedProducts(): int
    {
        return Product::query()
            ->where('status', 'active')
            ->where('is_approved', true)
            ->count();
    }

    private function countProductsCreatedBetween(Carbon $start, Carbon $end): int
    {
        return Product::query()->whereBetween('created_at', [$start, $end])->count();
    }

    private function countRfqsBetween(Carbon $start, Carbon $end): int
    {
        return RfqSubmission::query()->whereBetween('created_at', [$start, $end])->count();
    }

    private function countMessagesBetween(Carbon $start, Carbon $end): int
    {
        return Message::query()->whereBetween('created_at', [$start, $end])->count();
    }

    /**
     * @param  array{value: int|float, change: string, trend: string}  $trend
     * @return array<string, mixed>
     */
    private function metricCard(
        string $key,
        string $label,
        int|float $rawValue,
        string $formattedValue,
        array $trend,
    ): array {
        return [
            'key' => $key,
            'label' => $label,
            'value' => $formattedValue,
            'raw_value' => $rawValue,
            'change' => $trend['change'],
            'trend' => $trend['trend'],
        ];
    }

    private function formatCurrency(float $amount): string
    {
        if ($amount >= 1_000_000) {
            return '$'.number_format($amount / 1_000_000, 1).'M';
        }

        if ($amount >= 1_000) {
            return '$'.number_format($amount / 1_000, 1).'K';
        }

        return '$'.number_format($amount, 2);
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
