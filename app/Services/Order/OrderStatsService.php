<?php

namespace App\Services\Order;

use App\Enums\OrderStatus;
use App\Models\Order;
use Illuminate\Database\Eloquent\Builder;

class OrderStatsService
{
    /**
     * @return array<string, mixed>
     */
    public function forBuyer(int $buyerId): array
    {
        return $this->buildStats(
            Order::query()->where('buyer_id', $buyerId),
        );
    }

    /**
     * @return array<string, mixed>
     */
    public function forManufacturer(int $manufacturerId): array
    {
        return $this->buildStats(
            Order::query()->where('manufacturer_id', $manufacturerId),
        );
    }

    /**
     * @return array<string, mixed>
     */
    public function forAdmin(): array
    {
        $query = Order::query();

        return [
            ...$this->buildStats($query),
            'total_buyers' => (int) (clone $query)->distinct()->count('buyer_id'),
            'total_manufacturers' => (int) (clone $query)->distinct()->count('manufacturer_id'),
        ];
    }

    /**
     * @return array<string, mixed>
     */
    private function buildStats(Builder $query): array
    {
        $statusCounts = (clone $query)
            ->selectRaw('status, COUNT(*) as total')
            ->groupBy('status')
            ->pluck('total', 'status');

        $byStatus = [];

        foreach (OrderStatus::cases() as $status) {
            $byStatus[$status->value] = (int) ($statusCounts[$status->value] ?? 0);
        }

        $activeOrders = $byStatus[OrderStatus::OrderCreated->value]
            + $byStatus[OrderStatus::InProduction->value]
            + $byStatus[OrderStatus::ReadyForShipment->value]
            + $byStatus[OrderStatus::Shipped->value];

        $totalOrders = array_sum($byStatus);
        $completedOrders = $byStatus[OrderStatus::Completed->value];
        $cancelledOrders = $byStatus[OrderStatus::Cancelled->value];
        $orderValueByCurrency = $this->orderValueByCurrency($query);
        $totalValue = array_reduce(
            $orderValueByCurrency,
            static fn (float $carry, array $row): float => $carry + (float) $row['total_amount'],
            0.0,
        );

        return [
            'total_orders' => $totalOrders,
            'total' => $totalOrders,
            'active_orders' => $activeOrders,
            'active' => $activeOrders,
            'completed' => $completedOrders,
            'completed_orders' => $completedOrders,
            'cancelled' => $cancelledOrders,
            'cancelled_orders' => $cancelledOrders,
            'total_value' => round($totalValue, 2),
            ...$byStatus,
            'order_value_by_currency' => $orderValueByCurrency,
        ];
    }

    /**
     * @return list<array{currency_code: string, total_amount: string}>
     */
    private function orderValueByCurrency(Builder $query): array
    {
        return (clone $query)
            ->selectRaw('currency_code, COALESCE(SUM(total_amount), 0) as total_amount')
            ->groupBy('currency_code')
            ->orderBy('currency_code')
            ->get()
            ->map(fn (Order $row): array => [
                'currency_code' => (string) $row->currency_code,
                'total_amount' => number_format((float) $row->total_amount, 2, '.', ''),
            ])
            ->values()
            ->all();
    }
}
