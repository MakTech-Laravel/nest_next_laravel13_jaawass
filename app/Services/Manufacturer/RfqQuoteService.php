<?php

namespace App\Services\Manufacturer;

use App\Enums\RfqSubmissionStatus;
use App\Http\Requests\Api\V1\Manufacturer\SendRfqQuoteRequest;
use App\Models\RfqQuoteAttachment;
use App\Models\RfqSubmission;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;

class RfqQuoteService
{
    public function __construct(
        private readonly \App\Services\Rfq\RfqNotificationService $rfqNotificationService,
    ) {}

    public function sendQuote(RfqSubmission $rfqSubmission, SendRfqQuoteRequest $request): RfqSubmission
    {
        $validated = $request->validated();

        $rfqSubmission->forceFill([
            'quoted_price' => $validated['quoted_price'],
            'quote_currency_code' => strtoupper((string) $validated['quote_currency_code']),
            'minimum_order_quantity' => $validated['minimum_order_quantity'],
            'lead_time_days' => $validated['lead_time_days'],
            'lead_time' => $validated['lead_time'] ?? null,
            'quote_valid_until' => $validated['quote_valid_until'],
            'quote_shipping_terms' => $validated['quote_shipping_terms'] ?? null,
            'quote_payment_terms' => $validated['quote_payment_terms'] ?? null,
            'sample_cost' => $validated['sample_cost'] ?? null,
            'sample_lead_time' => $validated['sample_lead_time'] ?? null,
            'quote_packaging_details' => $validated['quote_packaging_details'] ?? null,
            'quote_certifications' => $validated['quote_certifications'] ?? null,
            'quote_notes' => $validated['quote_notes'] ?? null,
            'manufacturer_reply' => $validated['manufacturer_reply']
                ?? $validated['quote_notes']
                ?? $rfqSubmission->manufacturer_reply,
            'quoted_at' => now(),
            'status' => RfqSubmissionStatus::Quoted->value,
        ])->save();

        $rfqSubmission->quoteAttachments()->delete();
        $this->storeAttachments($rfqSubmission, $request->file('photos', []), 'photo');
        $this->storeAttachments($rfqSubmission, $request->file('attachments', []), 'file');

        $quoted = $rfqSubmission->fresh([
            'buyer',
            'product',
            'conversation',
            'quoteAttachments',
        ]);

        $this->rfqNotificationService->notifyQuoted($quoted);

        return $quoted;
    }

    /**
     * @param  array<int, UploadedFile>  $files
     */
    private function storeAttachments(RfqSubmission $rfqSubmission, array $files, string $type): void
    {
        if ($files === []) {
            return;
        }

        $disk = 'public';

        foreach ($files as $file) {
            if (! $file instanceof UploadedFile) {
                continue;
            }

            $path = $file->store(
                'rfq-quotes/'.$rfqSubmission->id.'/'.$type,
                ['disk' => $disk],
            );

            RfqQuoteAttachment::query()->create([
                'rfq_submission_id' => $rfqSubmission->id,
                'type' => $type,
                'disk' => $disk,
                'file_path' => $path,
                'file_mime' => (string) $file->getClientMimeType(),
                'original_name' => $file->getClientOriginalName(),
                'size_bytes' => $file->getSize() ?? Storage::disk($disk)->size($path),
            ]);
        }
    }
}
