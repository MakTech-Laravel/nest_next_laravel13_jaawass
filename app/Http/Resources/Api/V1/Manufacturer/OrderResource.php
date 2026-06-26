<?php

namespace App\Http\Resources\Api\V1\Manufacturer;

use App\Http\Resources\Api\V1\OrderAttachmentResource;
use App\Http\Resources\Api\V1\OrderItemResource;
use App\Http\Resources\Api\V1\OrderStatusUpdateResource;
use App\Http\Resources\Api\V1\Product\ProductResource;
use App\Models\Order;
use App\Support\Time\TimezoneFormatter;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * @mixin Order
 */
class OrderResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        $locale = $request->query('locale') ?? app()->getLocale();
        $localized = $this->localizedData($locale);

        $statusUpdates = OrderStatusUpdateResource::collection(
            $this->whenLoaded('statusUpdates'),
        );

        return [
            'id' => $this->id,
            'order_number' => sprintf('ORD-%05d', $this->id),
            'buyer_id' => $this->buyer_id,
            'manufacturer_id' => $this->manufacturer_id,
            'product_id' => $this->product_id,
            'title' => $localized['title'],
            'quantity' => $this->quantity,
            'quantity_unit' => $this->quantity_unit,
            'total_amount' => $this->total_amount,
            'currency_code' => $this->currency_code,
            'estimated_delivery_at' => $this->estimated_delivery_at?->toDateString(),
            'production_lead' => $this->production_lead,
            'payment_terms' => $this->payment_terms,
            'shipping_terms' => $this->shipping_terms,
            'destination' => $this->destination,
            'notes' => $localized['notes'],
            'status' => $this->status->value,
            'status_label' => $this->status->label(),
            'created_at' => TimezoneFormatter::format($this->created_at),
            'updated_at' => TimezoneFormatter::format($this->updated_at),
            'buyer' => $this->buyer === null ? null : [
                'id' => $this->buyer->id,
                'first_name' => $this->buyer->first_name,
                'last_name' => $this->buyer->last_name,
                'email' => $this->buyer->email,
                'company_name' => $this->buyer->company?->company_name,
            ],
            'manufacturer' => $this->manufacturer === null ? null : [
                'id' => $this->manufacturer->id,
                'first_name' => $this->manufacturer->first_name,
                'last_name' => $this->manufacturer->last_name,
                'email' => $this->manufacturer->email,
                'company_name' => $this->manufacturer->company?->company_name,
                'name' => $this->manufacturer->company?->company_name,
            ],
            'product' => $this->product === null
                ? null
                : new ProductResource($this->product),
            'items' => OrderItemResource::collection(
                $this->whenLoaded('items'),
            ),
            'status_updates' => $statusUpdates,
            'progress_updates' => $statusUpdates,
            'attachments' => OrderAttachmentResource::collection(
                $this->whenLoaded('attachments'),
            ),
        ];
    }
}
