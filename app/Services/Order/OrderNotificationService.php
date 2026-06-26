<?php

namespace App\Services\Order;

use App\Enums\MailTemplate;
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

        if ($buyer === null || $buyer->email === null) {
            return;
        }

        $this->mailingService->send(
            $buyer->email,
            MailTemplate::ManufacturerOrderCreated,
            $this->orderCreatedMailData($order),
        );
    }

    /**
     * @return array<string, mixed>
     */
    private function orderCreatedMailData(Order $order): array
    {
        $manufacturerName = $order->manufacturer?->company?->company_name
            ?? trim(($order->manufacturer?->first_name ?? '').' '.($order->manufacturer?->last_name ?? ''))
            ?: __('order.manufacturer');

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
            'orderNumber' => sprintf('ORD-%05d', $order->id),
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
}
