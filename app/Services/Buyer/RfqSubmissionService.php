<?php

namespace App\Services\Buyer;

use App\Enums\RfqSubmissionStatus;
use App\Models\Conversation;
use App\Models\Product;
use App\Models\RfqSubmission;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class RfqSubmissionService
{
    /**
     * @param  array<string, mixed>  $validated
     */
    public function submit(User $buyer, Product $product, array $validated): RfqSubmission
    {
        $manufacturer = $product->user;

        if ($manufacturer === null) {
            throw ValidationException::withMessages([
                'product_id' => ['Selected product has no supplier account.'],
            ]);
        }

        return DB::transaction(function () use ($buyer, $product, $manufacturer, $validated): RfqSubmission {
            $conversation = Conversation::query()->create([
                'name' => 'RFQ - '.$product->name,
                'created_by' => $buyer->id,
            ]);

            $conversation->participants()->attach([$buyer->id, $manufacturer->id]);

            $rfq = RfqSubmission::query()->create([
                'rfq_number' => null,
                'buyer_id' => $buyer->id,
                'manufacturer_id' => $manufacturer->id,
                'product_id' => $product->id,
                'conversation_id' => $conversation->id,
                'quantity' => (int) $validated['quantity'],
                'quantity_unit' => (string) ($validated['quantity_unit'] ?? 'pieces'),
                'target_price' => $validated['target_price'] ?? null,
                'target_currency_code' => isset($validated['target_currency_code'])
                    ? strtoupper((string) $validated['target_currency_code'])
                    : null,
                'required_delivery_date' => $validated['required_delivery_date'] ?? null,
                'shipping_terms' => $validated['shipping_terms'] ?? null,
                'destination_country' => $validated['destination_country'] ?? null,
                'destination_port_city' => $validated['destination_port_city'] ?? null,
                'packaging_details' => $validated['packaging_details'] ?? null,
                'additional_requirements' => $validated['additional_requirements'] ?? null,
                'status' => RfqSubmissionStatus::Pending->value,
            ]);

            $rfq->forceFill([
                'rfq_number' => $this->buildRfqNumber((int) $rfq->id),
            ])->save();

            Product::query()
                ->whereKey($product->id)
                ->increment('inquiry_count');

            return $rfq->load([
                'product',
                'manufacturer.company',
                'conversation',
            ]);
        });
    }

    private function buildRfqNumber(int $rfqId): string
    {
        return sprintf('RFQ-%03d', $rfqId);
    }
}
