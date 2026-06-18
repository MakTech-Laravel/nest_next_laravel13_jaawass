<?php

namespace App\Services\Subscription;

use App\Enums\Api\V1\SubscriptionEventType;
use App\Enums\Api\V1\SubscriptionStatus;
use App\Models\Payment;
use App\Models\Plan;
use App\Models\Subscription;
use App\Models\SubscriptionLog;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;

class SubscriptionStatsService
{
    /**
     * @return array<string, mixed>
     */
    public function stats(?string $month = null): array
    {
        $periodStart = $month !== null
            ? Carbon::createFromFormat('Y-m', $month)->startOfMonth()
            : now()->startOfMonth();
        $periodEnd = $periodStart->copy()->endOfMonth();

        $paymentsThisMonth = Payment::query()
            ->where('status', 'paid')
            ->whereBetween('created_at', [$periodStart, $periodEnd]);

        $revenueThisMonth = (float) (clone $paymentsThisMonth)->sum('amount');
        $paymentsCount = (clone $paymentsThisMonth)->count();

        $newSubscriptions = SubscriptionLog::query()
            ->where('event_type', SubscriptionEventType::SUBSCRIPTION_CREATED->value)
            ->whereBetween('created_at', [$periodStart, $periodEnd])
            ->count();

        $upgrades = SubscriptionLog::query()
            ->where('event_type', SubscriptionEventType::SUBSCRIPTION_UPGRADED->value)
            ->whereBetween('created_at', [$periodStart, $periodEnd])
            ->count();

        $cancellations = SubscriptionLog::query()
            ->where('event_type', SubscriptionEventType::SUBSCRIPTION_CANCELLED->value)
            ->whereBetween('created_at', [$periodStart, $periodEnd])
            ->count();

        $revenueByPlan = Payment::query()
            ->select('source_id', DB::raw('SUM(amount) as revenue'), DB::raw('COUNT(*) as count'))
            ->where('status', 'paid')
            ->where('source_type', Plan::class)
            ->whereBetween('created_at', [$periodStart, $periodEnd])
            ->groupBy('source_id')
            ->get()
            ->map(function ($row) {
                $plan = Plan::query()->find($row->source_id);

                return [
                    'plan_id' => (int) $row->source_id,
                    'plan_name' => $plan?->name ?? 'Unknown',
                    'revenue' => (float) $row->revenue,
                    'count' => (int) $row->count,
                ];
            })
            ->values()
            ->all();

        $revenueByMethod = Payment::query()
            ->select('payment_method', DB::raw('SUM(amount) as revenue'), DB::raw('COUNT(*) as count'))
            ->where('status', 'paid')
            ->whereBetween('created_at', [$periodStart, $periodEnd])
            ->groupBy('payment_method')
            ->get()
            ->map(fn ($row) => [
                'payment_method' => $row->payment_method,
                'revenue' => (float) $row->revenue,
                'count' => (int) $row->count,
            ])
            ->values()
            ->all();

        return [
            'period' => [
                'month' => $periodStart->format('Y-m'),
                'from' => $periodStart->toDateString(),
                'to' => $periodEnd->toDateString(),
            ],
            'overview' => [
                'total_active_subscriptions' => Subscription::query()
                    ->where('status', SubscriptionStatus::ACTIVE->value)
                    ->count(),
                'total_revenue_all_time' => (float) Payment::query()->where('status', 'paid')->sum('amount'),
                'auto_renew_enabled_count' => Subscription::query()
                    ->where('auto_renew', true)
                    ->where('status', SubscriptionStatus::ACTIVE->value)
                    ->count(),
            ],
            'this_month' => [
                'new_subscriptions' => $newSubscriptions,
                'upgrades' => $upgrades,
                'cancellations' => $cancellations,
                'revenue' => $revenueThisMonth,
                'payments_count' => $paymentsCount,
            ],
            'revenue_by_plan' => $revenueByPlan,
            'revenue_by_payment_method' => $revenueByMethod,
        ];
    }
}
