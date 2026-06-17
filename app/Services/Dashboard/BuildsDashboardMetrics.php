<?php

namespace App\Services\Dashboard;

trait BuildsDashboardMetrics
{
    protected function percentChange(int|float $current, int|float $previous): float
    {
        if ($previous == 0) {
            return $current > 0 ? 100.0 : 0.0;
        }

        return round((($current - $previous) / $previous) * 100, 1);
    }

    protected function trendDirection(float $change): string
    {
        return $change >= 0 ? 'up' : 'down';
    }

    protected function formatTrendBadge(float $change): string
    {
        $prefix = $change >= 0 ? '+' : '';

        return $prefix.number_format($change, 1).'%';
    }

    /**
     * @return array{value: int|float, change: string, trend: string}
     */
    protected function metricWithTrend(int|float $current, int|float $previous): array
    {
        $change = $this->percentChange($current, $previous);

        return [
            'value' => $current,
            'change' => $this->formatTrendBadge($change),
            'trend' => $this->trendDirection($change),
        ];
    }

    protected function rfqStatusLabel(string $status): string
    {
        return match ($status) {
            'pending' => 'Pending',
            'in_review' => 'In Review',
            'quoted' => 'Quoted',
            'accepted' => 'Accepted',
            'cancelled' => 'Cancelled',
            'rejected' => 'Rejected',
            'expired' => 'Expired',
            default => ucfirst(str_replace('_', ' ', $status)),
        };
    }

    protected function manufacturerInquiryStatusLabel(string $status): string
    {
        return match ($status) {
            'pending' => 'New',
            'in_review' => 'In Review',
            'quoted' => 'Quoted',
            default => $this->rfqStatusLabel($status),
        };
    }
}
