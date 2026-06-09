<?php

namespace App\Http\Resources\Api\V1\Manufacturer;

use App\Enums\RfqSubmissionStatus;
use App\Models\RfqSubmission;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * @mixin RfqSubmission
 */
class RfqSubmissionResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        $status = $this->status instanceof RfqSubmissionStatus
            ? $this->status
            : RfqSubmissionStatus::tryFrom((string) $this->status);

        return [
            'id' => $this->id,
            'rfq_number' => $this->rfq_number,
            'status' => $status?->value ?? $this->status,
            'created_at' => $this->created_at?->toIso8601String(),
            'quantity' => $this->quantity,
            'quantity_unit' => $this->quantity_unit,
            'target_price' => $this->target_price,
            'target_currency_code' => $this->target_currency_code,
            'required_delivery_date' => $this->required_delivery_date,
            'shipping_terms' => $this->shipping_terms,
            'destination_country' => $this->destination_country,
            'destination_port_city' => $this->destination_port_city,
            'packaging_details' => $this->packaging_details,
            'additional_requirements' => $this->additional_requirements,
            'manufacturer_reply' => $this->manufacturer_reply,
            'quoted_price' => $this->quoted_price,
            'quote_currency_code' => $this->quote_currency_code,
            'minimum_order_quantity' => $this->minimum_order_quantity,
            'lead_time_days' => $this->lead_time_days,
            'quote_valid_until' => $this->quote_valid_until,
            'quoted_at' => $this->quoted_at?->toIso8601String(),
            'buyer_action_at' => $this->buyer_action_at?->toIso8601String(),
            'product' => $this->product === null ? null : [
                'id' => $this->product->id,
                'name' => $this->product->name,
                'slug' => $this->product->slug,
            ],
            'buyer' => $this->buyer === null ? null : [
                'id' => $this->buyer->id,
                'name' => trim(($this->buyer->first_name ?? '').' '.($this->buyer->last_name ?? '')),
                'email' => $this->buyer->email,
            ],
            'send_quote_endpoint' => route('api.v1.manufacturer.rfqs.send-quote', ['rfq' => $this->id]),
            'reply_endpoint' => route('api.v1.manufacturer.rfqs.reply', ['rfq' => $this->id]),
        ];
    }
}
