<?php

namespace Database\Seeders;

use App\Enums\DashboardEventType;
use App\Enums\OrderStatus;
use App\Models\Order;
use App\Models\RfqSubmission;
use App\Models\SaveProduct;
use App\Models\SaveSupplier;
use App\Services\Dashboard\EventTrackerService;
use Illuminate\Database\Seeder;

class DashboardEventBackfillSeeder extends Seeder
{
    public function run(): void
    {
        $tracker = app(EventTrackerService::class);

        RfqSubmission::query()
            ->with(['buyer', 'manufacturer'])
            ->orderBy('id')
            ->chunkById(200, function ($rfqs) use ($tracker): void {
                foreach ($rfqs as $rfq) {
                    $tracker->trackOnce(
                        eventType: DashboardEventType::RfqCreated,
                        actor: $rfq->buyer,
                        entityType: 'rfq_submission',
                        entityId: (int) $rfq->id,
                        counterparty: $rfq->manufacturer,
                        metadata: [
                            'product_id' => (int) $rfq->product_id,
                            'manufacturer_id' => (int) $rfq->manufacturer_id,
                        ],
                        occurredAt: $rfq->created_at,
                    );

                    if ($rfq->quoted_at !== null) {
                        $tracker->trackOnce(
                            eventType: DashboardEventType::RfqQuoted,
                            actor: $rfq->manufacturer,
                            entityType: 'rfq_submission',
                            entityId: (int) $rfq->id,
                            counterparty: $rfq->buyer,
                            metadata: [
                                'quoted_price' => $rfq->quoted_price,
                                'quote_currency_code' => $rfq->quote_currency_code,
                            ],
                            occurredAt: $rfq->quoted_at,
                        );
                    }
                }
            });

        SaveProduct::query()
            ->with(['user', 'product.user'])
            ->orderBy('id')
            ->chunkById(200, function ($savedProducts) use ($tracker): void {
                foreach ($savedProducts as $savedProduct) {
                    $tracker->trackOnce(
                        eventType: DashboardEventType::ProductSaved,
                        actor: $savedProduct->user,
                        entityType: 'product',
                        entityId: (int) $savedProduct->product_id,
                        counterparty: $savedProduct->product?->user,
                        metadata: ['saved_id' => (int) $savedProduct->id],
                        occurredAt: $savedProduct->created_at,
                    );
                }
            });

        SaveSupplier::query()
            ->with(['user', 'supplier'])
            ->orderBy('id')
            ->chunkById(200, function ($savedSuppliers) use ($tracker): void {
                foreach ($savedSuppliers as $savedSupplier) {
                    $tracker->trackOnce(
                        eventType: DashboardEventType::SupplierSaved,
                        actor: $savedSupplier->user,
                        entityType: 'supplier',
                        entityId: (int) $savedSupplier->supplier_id,
                        counterparty: $savedSupplier->supplier,
                        metadata: ['saved_id' => (int) $savedSupplier->id],
                        occurredAt: $savedSupplier->created_at,
                    );
                }
            });

        Order::query()
            ->with(['manufacturer', 'buyer'])
            ->where(function ($query): void {
                $query
                    ->whereNotNull('delivered_at')
                    ->orWhere('status', OrderStatus::Completed->value);
            })
            ->orderBy('id')
            ->chunkById(200, function ($orders) use ($tracker): void {
                foreach ($orders as $order) {
                    $deliveredAt = $order->delivered_at ?? $order->updated_at;
                    if ($deliveredAt === null) {
                        continue;
                    }

                    $tracker->trackOnce(
                        eventType: DashboardEventType::OrderDelivered,
                        actor: $order->manufacturer,
                        entityType: 'order',
                        entityId: (int) $order->id,
                        counterparty: $order->buyer,
                        metadata: [
                            'status' => $order->status?->value ?? $order->status,
                            'estimated_delivery_at' => $order->estimated_delivery_at?->toDateString(),
                        ],
                        occurredAt: $deliveredAt,
                    );
                }
            });
    }
}
