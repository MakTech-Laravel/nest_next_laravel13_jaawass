<?php

namespace App\Enums;

enum OrderStatus: string
{
    case OrderCreated = 'order_created';
    case InProduction = 'in_production';
    case ReadyForShipment = 'ready_for_shipment';
    case Shipped = 'shipped';
    case Completed = 'completed';
    case Cancelled = 'cancelled';

    public function label(): string
    {
        return match ($this) {
            self::OrderCreated => __('order_status.order_created'),
            self::InProduction => __('order_status.in_production'),
            self::ReadyForShipment => __('order_status.ready_for_shipment'),
            self::Shipped => __('order_status.shipped'),
            self::Completed => __('order_status.completed'),
            self::Cancelled => __('order_status.cancelled'),
        };
    }

    /**
     * @return array<int, string>
     */
    public static function values(): array
    {
        return array_column(self::cases(), 'value');
    }

    /**
     * @return list<array{value: string, label: string}>
     */
    public static function options(): array
    {
        return array_map(
            fn (self $status): array => [
                'value' => $status->value,
                'label' => $status->label(),
            ],
            self::cases(),
        );
    }
}
