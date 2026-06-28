<?php

namespace App\Services\Order;

use App\Enums\MailTemplate;
use App\Enums\OrderStatus;
use App\Jobs\Order\SendOrderInAppNotificationJob;
use App\Models\Order;
use App\Models\User;
use App\Services\Mailing\MailingService;

class OrderNotificationService
{
    public function __construct(
        private readonly MailingService $mailingService,
    ) {}

    public function sendOrderCreated(Order $order): void
    {
        $order->loadMissing([
            'buyer.company',
            'manufacturer.company',
            'items.product',
        ]);

        $buyer = $order->buyer;

        if ($buyer !== null && $buyer->email !== null) {
            $this->mailingService->send(
                $buyer->email,
                MailTemplate::ManufacturerOrderCreated,
                $this->orderCreatedMailData($order),
            );
        }

        $this->dispatchOrderCreatedInAppNotifications($order);
    }

    public function sendStatusUpdated(Order $order, OrderStatus $status, User $manufacturer): void
    {
        $order->loadMissing(['buyer.company', 'manufacturer.company']);

        $this->dispatchStatusUpdatedInAppNotifications($order, $status, $manufacturer);
    }

    private function dispatchOrderCreatedInAppNotifications(Order $order): void
    {
        $manufacturer = $order->manufacturer;
        $buyer = $order->buyer;

        if ($manufacturer === null) {
            return;
        }

        $orderNumber = $this->orderNumber($order);
        $manufacturerName = $this->manufacturerDisplayName($order);
        $buyerName = $this->displayName($buyer);
        $notificationData = $this->notificationData($order, $this->statusValue($order->status));

        if ($buyer !== null) {
            $this->dispatchInAppNotification(
                recipient: $buyer,
                type: 'order.created',
                title: __('order.notifications.created.buyer_title'),
                body: __('order.notifications.created.buyer_body', [
                    'manufacturerName' => $manufacturerName,
                    'orderNumber' => $orderNumber,
                ]),
                data: $notificationData,
                actionUrl: $this->buyerOrdersUrl((int) $order->id),
                sender: $manufacturer,
            );
        }

        foreach ($this->adminRecipients() as $admin) {
            $this->dispatchInAppNotification(
                recipient: $admin,
                type: 'order.created',
                title: __('order.notifications.created.admin_title'),
                body: __('order.notifications.created.admin_body', [
                    'manufacturerName' => $manufacturerName,
                    'buyerName' => $buyerName,
                    'orderNumber' => $orderNumber,
                ]),
                data: $notificationData,
                actionUrl: $this->adminOrdersUrl((int) $order->id),
                sender: $manufacturer,
            );
        }
    }

    private function dispatchStatusUpdatedInAppNotifications(
        Order $order,
        OrderStatus $status,
        User $manufacturer,
    ): void {
        $buyer = $order->buyer;

        if ($buyer === null) {
            return;
        }

        $orderNumber = $this->orderNumber($order);
        $manufacturerName = $this->manufacturerDisplayName($order);
        $statusKey = $this->statusTranslationKey($status);
        $notificationData = $this->notificationData($order, $status->value);
        $type = 'order.status.'.$status->value;

        $this->dispatchInAppNotification(
            recipient: $buyer,
            type: $type,
            title: __('order.notifications.status.buyer_title', [
                'orderNumber' => $orderNumber,
            ]),
            body: __('order.notifications.status.buyer_body.'.$statusKey, [
                'manufacturerName' => $manufacturerName,
                'orderNumber' => $orderNumber,
            ]),
            data: $notificationData,
            actionUrl: $this->buyerOrdersUrl((int) $order->id),
            sender: $manufacturer,
        );

        foreach ($this->adminRecipients() as $admin) {
            $this->dispatchInAppNotification(
                recipient: $admin,
                type: $type,
                title: __('order.notifications.status.admin_title', [
                    'orderNumber' => $orderNumber,
                ]),
                body: __('order.notifications.status.admin_body.'.$statusKey, [
                    'manufacturerName' => $manufacturerName,
                    'orderNumber' => $orderNumber,
                ]),
                data: $notificationData,
                actionUrl: $this->adminOrdersUrl((int) $order->id),
                sender: $manufacturer,
            );
        }
    }

    private function statusValue(OrderStatus|string|null $status): string
    {
        if ($status instanceof OrderStatus) {
            return $status->value;
        }

        return (string) ($status ?? OrderStatus::OrderCreated->value);
    }

    /**
     * @return array<string, mixed>
     */
    private function notificationData(Order $order, string $status): array
    {
        return [
            'order_id' => $order->id,
            'order_number' => $this->orderNumber($order),
            'status' => $status,
            'manufacturer_id' => $order->manufacturer_id,
            'buyer_id' => $order->buyer_id,
        ];
    }

    /**
     * @return \Illuminate\Support\Collection<int, User>
     */
    private function adminRecipients()
    {
        return User::query()->isAdmin()->get();
    }

    /**
     * @param  array<string, mixed>  $data
     */
    private function dispatchInAppNotification(
        User $recipient,
        string $type,
        string $title,
        string $body,
        array $data,
        string $actionUrl,
        ?User $sender = null,
    ): void {
        SendOrderInAppNotificationJob::dispatch(
            $recipient->id,
            $type,
            $title,
            $body,
            $data,
            $actionUrl,
            $sender?->id,
        );
    }

    private function orderNumber(Order $order): string
    {
        return sprintf('ORD-%05d', $order->id);
    }

    private function statusTranslationKey(OrderStatus $status): string
    {
        return match ($status) {
            OrderStatus::OrderCreated => 'order_created',
            OrderStatus::InProduction => 'in_production',
            OrderStatus::ReadyForShipment => 'ready_for_shipment',
            OrderStatus::Shipped => 'shipped',
            OrderStatus::Completed => 'completed',
            OrderStatus::Cancelled => 'cancelled',
        };
    }

    /**
     * @return array<string, mixed>
     */
    private function orderCreatedMailData(Order $order): array
    {
        $manufacturerName = $this->manufacturerDisplayName($order);

        $localized = $order->localizedData();

        $items = $order->items->map(fn ($item): array => [
            'productName' => $item->product?->name ?? __('order.product'),
            'quantity' => $item->quantity,
            'quantityUnit' => $item->quantity_unit,
            'unitPrice' => number_format((float) $item->unit_price, 2),
            'lineTotal' => number_format((float) $item->line_total, 2),
            'notes' => $item->notes,
        ])->all();

        return [
            'buyerName' => $this->displayName($order->buyer),
            'manufacturerName' => $manufacturerName,
            'orderNumber' => $this->orderNumber($order),
            'orderTitle' => $localized['title'],
            'totalAmount' => number_format((float) $order->total_amount, 2),
            'currencyCode' => $order->currency_code,
            'estimatedDeliveryAt' => $order->estimated_delivery_at?->format('F j, Y') ?? '',
            'productionLead' => $order->production_lead,
            'paymentTerms' => $order->payment_terms,
            'shippingTerms' => $order->shipping_terms,
            'destination' => $order->destination,
            'notes' => $localized['notes'],
            'items' => $items,
            'ordersUrl' => $this->buyerOrdersUrl((int) $order->id),
        ];
    }

    private function manufacturerDisplayName(Order $order): string
    {
        $name = $order->manufacturer?->company?->company_name
            ?? trim(($order->manufacturer?->first_name ?? '').' '.($order->manufacturer?->last_name ?? ''));

        return $name !== '' ? $name : __('order.manufacturer');
    }

    private function displayName(?User $user): string
    {
        if ($user === null) {
            return 'there';
        }

        $name = trim($user->first_name.' '.$user->last_name);

        return $name !== '' ? $name : 'there';
    }

    private function buyerOrdersUrl(int $orderId): string
    {
        $frontendUrl = rtrim((string) config('app.frontend_url', config('app.url')), '/');

        return $frontendUrl.'/dashboard/buyer/orders/'.$orderId;
    }

    private function adminOrdersUrl(int $orderId): string
    {
        $frontendUrl = rtrim((string) config('app.frontend_url', config('app.url')), '/');

        return $frontendUrl.'/admin/orders/'.$orderId;
    }
}
