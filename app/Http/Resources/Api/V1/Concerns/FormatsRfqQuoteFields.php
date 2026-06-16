<?php

namespace App\Http\Resources\Api\V1\Concerns;

use App\Http\Resources\Api\V1\RfqQuoteAttachmentResource;
use App\Models\RfqSubmission;
use Illuminate\Support\Collection;

trait FormatsRfqQuoteFields
{
    /**
     * @return array<string, mixed>
     */
    protected function quoteFields(RfqSubmission $submission): array
    {
        $attachments = $submission->relationLoaded('quoteAttachments')
            ? $submission->quoteAttachments
            : collect();

        return [
            'quoted_price' => $submission->quoted_price,
            'quote_currency_code' => $submission->quote_currency_code,
            'minimum_order_quantity' => $submission->minimum_order_quantity,
            'lead_time_days' => $submission->lead_time_days,
            'lead_time' => $submission->lead_time,
            'quote_valid_until' => $submission->quote_valid_until,
            'quote_shipping_terms' => $submission->quote_shipping_terms,
            'quote_payment_terms' => $submission->quote_payment_terms,
            'sample_cost' => $submission->sample_cost,
            'sample_lead_time' => $submission->sample_lead_time,
            'quote_packaging_details' => $submission->quote_packaging_details,
            'quote_certifications' => $submission->quote_certifications ?? [],
            'quote_notes' => $submission->quote_notes,
            'manufacturer_reply' => $submission->manufacturer_reply,
            'quoted_at' => $submission->quoted_at?->toIso8601String(),
            'buyer_action_at' => $submission->buyer_action_at?->toIso8601String(),
            'quote_attachments' => $attachments->isEmpty()
                ? []
                : RfqQuoteAttachmentResource::collection($attachments)->resolve(),
            'quote_photos' => $this->quoteAttachmentUrls($attachments, 'photo'),
            'quote_documents' => RfqQuoteAttachmentResource::collection(
                $attachments->where('type', 'file')->values(),
            )->resolve(),
        ];
    }

    /**
     * @param  Collection<int, \App\Models\RfqQuoteAttachment>  $attachments
     * @return list<string>
     */
    private function quoteAttachmentUrls(Collection $attachments, string $type): array
    {
        return $attachments
            ->where('type', $type)
            ->map(fn ($attachment) => $attachment->url)
            ->filter()
            ->values()
            ->all();
    }
}
