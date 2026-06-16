<?php

namespace App\Http\Resources\Api\V1\Buyer;

use App\Http\Resources\Api\V1\Concerns\FormatsRfqQuoteFields;
use App\Enums\RfqSubmissionStatus;
use App\Models\RfqSubmission;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * @mixin RfqSubmission
 */
class RfqSubmissionResource extends JsonResource
{
    use FormatsRfqQuoteFields;

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
            ...$this->quoteFields($this->resource),
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
