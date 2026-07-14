<?php

namespace App\Services\Order;

use App\Enums\MailTemplate;
use App\Enums\OrderStatus;
use App\Jobs\Order\SendOrderInAppNotificationJob;
use App\Models\Order;
use App\Models\User;
use App\Services\Mailing\MailingService;
use App\Support\Mail\MailNotificationHelper;

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
        $manufacturer = $order->manufacturer;

        if ($buyer !== null && $buyer->email !== null) {
            $this->mailingService->send(
                $buyer->email,
                MailTemplate::ManufacturerOrderCreated,
                $this->orderCreatedMailData($order, 'buyer', $buyer),
            );
        }

        MailNotificationHelper::sendIfEmail($manufacturer, function (string $email) use ($order, $manufacturer): void {
            $this->mailingService->send(
                $email,
                MailTemplate::OrderCreatedManufacturer,
                $this->orderCreatedMailData($order, 'manufacturer', $manufacturer),
            );
        });

        foreach (MailNotificationHelper::adminRecipients() as $admin) {
            MailNotificationHelper::sendIfEmail($admin, function (string $email) use ($order, $admin): void {
                $this->mailingService->send(
                    $email,
                    MailTemplate::OrderCreatedAdmin,
                    $this->orderCreatedMailData($order, 'admin', $admin),
                );
            });
        }

        $this->dispatchOrderCreatedInAppNotifications($order);
    }

    public function sendStatusUpdated(Order $order, OrderStatus $status, User $manufacturer): void
    {
        $order->loadMissing(['buyer.company', 'manufacturer.company']);

        $templates = $this->statusMailTemplates($status);

        if ($templates === null) {
            $this->dispatchStatusUpdatedInAppNotifications($order, $status, $manufacturer);

            return;
        }

        $buyer = $order->buyer;
        $orderManufacturer = $order->manufacturer;
        $mailData = $this->statusUpdatedMailData($order, $status);

        if ($buyer !== null && $buyer->email !== null && isset($templates['buyer'])) {
            $this->mailingService->send($buyer->email, $templates['buyer'], [
                ...$mailData,
                'recipientName' => $this->displayName($buyer),
                'ctaUrl' => $this->buyerOrdersUrl((int) $order->id),
            ]);
        }

        if (isset($templates['manufacturer'])) {
            MailNotificationHelper::sendIfEmail($orderManufacturer, function (string $email) use ($order, $orderManufacturer, $templates, $mailData): void {
                $this->mailingService->send($email, $templates['manufacturer'], [
                    ...$mailData,
                    'recipientName' => $this->displayName($orderManufacturer),
                    'ctaUrl' => $this->manufacturerOrdersUrl((int) $order->id),
                ]);
            });
        }

        if (isset($templates['admin'])) {
            foreach (MailNotificationHelper::adminRecipients() as $admin) {
                MailNotificationHelper::sendIfEmail($admin, function (string $email) use ($order, $admin, $templates, $mailData): void {
                    $this->mailingService->send($email, $templates['admin'], [
                        ...$mailData,
                        'recipientName' => $this->displayName($admin),
                        'ctaUrl' => $this->adminOrdersUrl((int) $order->id),
                    ]);
                });
            }
        }

        $this->dispatchStatusUpdatedInAppNotifications($order, $status, $manufacturer);
    }

    /**
     * @return array{buyer?: MailTemplate, manufacturer?: MailTemplate, admin?: MailTemplate}|null
     */
    private function statusMailTemplates(OrderStatus $status): ?array
    {
        return match ($status) {
            OrderStatus::InProduction => [
                'buyer' => MailTemplate::OrderInProductionBuyer,
                'manufacturer' => MailTemplate::OrderInProductionManufacturer,
            ],
            OrderStatus::ReadyForShipment => [
                'buyer' => MailTemplate::OrderReadyForShipmentBuyer,
                'manufacturer' => MailTemplate::OrderReadyForShipmentManufacturer,
            ],
            OrderStatus::Shipped => [
                'buyer' => MailTemplate::OrderShippedBuyer,
                'manufacturer' => MailTemplate::OrderShippedManufacturer,
            ],
            OrderStatus::Completed => [
                'buyer' => MailTemplate::OrderCompletedBuyer,
                'manufacturer' => MailTemplate::OrderCompletedManufacturer,
                'admin' => MailTemplate::OrderCompletedAdmin,
            ],
            OrderStatus::Cancelled => [
                'buyer' => MailTemplate::OrderCancelledBuyer,
                'manufacturer' => MailTemplate::OrderCancelledManufacturer,
                'admin' => MailTemplate::OrderCancelledAdmin,
            ],
            OrderStatus::OrderCreated => null,
        };
    }

    /**
     * @return array<string, mixed>
     */
    private function statusUpdatedMailData(Order $order, OrderStatus $status): array
    {
        $orderNumber = $this->orderNumber($order);

        return [
            'orderId' => (int) $order->id,
            'orderNumber' => $orderNumber,
            'status' => $status->label(),
            'buyerName' => $this->displayName($order->buyer),
            'manufacturerName' => $this->manufacturerDisplayName($order),
            'referenceId' => $orderNumber,
        ];
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

        $this->dispatchInAppNotification(
            recipient: $manufacturer,
            type: 'order.created',
            title: __('order.notifications.created.manufacturer_title'),
            body: __('order.notifications.created.manufacturer_body', [
                'buyerName' => $buyerName,
                'orderNumber' => $orderNumber,
            ]),
            data: $notificationData,
            actionUrl: $this->manufacturerOrdersUrl((int) $order->id),
            sender: $manufacturer,
        );

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

        $this->dispatchInAppNotification(
            recipient: $manufacturer,
            type: $type,
            title: __('order.notifications.status.manufacturer_title', [
                'orderNumber' => $orderNumber,
            ]),
            body: __('order.notifications.status.manufacturer_body', [
                'buyerName' => $this->displayName($buyer),
                'orderNumber' => $orderNumber,
                'status' => $status->label(),
            ]),
            data: $notificationData,
            actionUrl: $this->manufacturerOrdersUrl((int) $order->id),
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
        return MailNotificationHelper::adminRecipients();
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
    private function orderCreatedMailData(Order $order, string $recipientRole, ?User $recipient = null): array
    {
        $manufacturerName = $this->manufacturerDisplayName($order);
        $buyerName = $this->displayName($order->buyer);
        $orderNumber = $this->orderNumber($order);
        $localized = $order->localizedData();

        $items = $order->items->map(fn ($item): array => [
            'productName' => $item->product?->name ?? __('order.product'),
            'quantity' => $item->quantity,
            'quantityUnit' => $item->quantity_unit,
            'unitPrice' => number_format((float) $item->unit_price, 2),
            'lineTotal' => number_format((float) $item->line_total, 2),
            'notes' => $item->notes,
        ])->all();

        $ctaUrl = match ($recipientRole) {
            'manufacturer' => $this->manufacturerOrdersUrl((int) $order->id),
            'admin' => $this->adminOrdersUrl((int) $order->id),
            default => $this->buyerOrdersUrl((int) $order->id),
        };

        return [
            'recipientRole' => $recipientRole,
            'recipientName' => $this->displayName($recipient),
            'buyerName' => $buyerName,
            'manufacturerName' => $manufacturerName,
            'orderId' => (int) $order->id,
            'orderNumber' => $orderNumber,
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
            'ctaUrl' => $ctaUrl,
            'ordersUrl' => $ctaUrl,
            'referenceId' => $orderNumber,
            'footerNote' => __("mail.manufacturer_order_created.{$recipientRole}.footer"),
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

    private function manufacturerOrdersUrl(int $orderId): string
    {
        return MailNotificationHelper::manufacturerOrderUrl($orderId);
    }

    private function buyerOrdersUrl(int $orderId): string
    {
        return MailNotificationHelper::buyerOrderUrl($orderId);
    }

    private function adminOrdersUrl(int $orderId): string
    {
        return MailNotificationHelper::adminOrderUrl($orderId);
    }
}
