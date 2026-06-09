<?php

namespace App\Services\Manufacturer;

use App\Enums\Api\V1\ProductStatusEnum;
use App\Models\Conversation;
use App\Models\Product;
use App\Models\User;

class ManufacturerProductStatsService
{
    /**
     * @return array{total_products: int, active_products: int, total_views: int, total_inquiries: int}
     */
    public function getStats(User $manufacturer): array
    {
        $manufacturerId = (int) $manufacturer->id;

        $productStats = $this->aggregateProductStats($manufacturerId);
        $totalInquiries = $this->countBuyerInitiatedConversations($manufacturer, $manufacturerId);

        return [
            'total_products' => $productStats['total_products'],
            'active_products' => $productStats['active_products'],
            'total_views' => $productStats['total_views'],
            'total_inquiries' => $totalInquiries,
        ];
    }

    /**
     * @return array{total_products: int, active_products: int, total_views: int}
     */
    private function aggregateProductStats(int $manufacturerId): array
    {
        $row = Product::query()
            ->where('user_id', $manufacturerId)
            ->selectRaw('COUNT(*) as total_products')
            ->selectRaw(
                'COALESCE(SUM(CASE WHEN status = ? THEN 1 ELSE 0 END), 0) as active_products',
                [ProductStatusEnum::ACTIVE->value]
            )
            ->selectRaw('COALESCE(SUM(view_count), 0) as total_views')
            ->first();

        return [
            'total_products' => (int) ($row?->total_products ?? 0),
            'active_products' => (int) ($row?->active_products ?? 0),
            'total_views' => (int) ($row?->total_views ?? 0),
        ];
    }

    private function countBuyerInitiatedConversations(User $manufacturer, int $manufacturerId): int
    {
        return Conversation::query()
            ->forParticipant($manufacturer)
            ->whereNotNull('created_by')
            ->where('created_by', '!=', $manufacturerId)
            ->count();
    }
}
