<?php

namespace App\Http\Resources\Api\V1\Buyer;

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
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        $manufacturerCompany = $this->manufacturer?->company;
        $product = $this->product;
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
            'conversation_id' => $this->conversation_id,
            'product' => $product === null ? null : [
                'id' => $product->id,
                'name' => $product->name,
                'slug' => $product->slug,
                'inquiry_count' => $product->inquiry_count,
            ],
            'supplier' => $this->manufacturer === null ? null : [
                'id' => $this->manufacturer->id,
                'company_name' => $manufacturerCompany?->company_name,
                'location' => collect([
                    $manufacturerCompany?->city,
                    $manufacturerCompany?->country,
                ])->filter()->implode(', '),
            ],
            // Frontend can call existing /api/v1/conversations/{id}/messages.
            'message_endpoint' => route(
                'api.v1.conversations.messages.store',
                ['conversation' => $this->conversation_id]
            ),
            'quote_action_endpoint' => route(
                'api.v1.buyer.rfqs.respond-quote',
                ['rfq' => $this->id]
            ),
        ];
    }
}
