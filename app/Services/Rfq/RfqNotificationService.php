<?php

namespace App\Services\Rfq;

use App\Enums\MailTemplate;
use App\Enums\RfqSubmissionStatus;
use App\Jobs\Support\SendSupportTicketInAppNotificationJob;
use App\Models\RfqSubmission;
use App\Models\User;
use App\Services\Mailing\MailingService;
use App\Support\Mail\MailNotificationHelper;
use Illuminate\Support\Facades\Lang;

class RfqNotificationService
{
    public function __construct(
        private readonly MailingService $mailingService,
    ) {}

    public function notifyCreated(RfqSubmission $rfq): void
    {
        $rfq->loadMissing(['buyer.company', 'manufacturer.company', 'product']);
        $manufacturer = $rfq->manufacturer;
        $buyer = $rfq->buyer;

        if ($manufacturer === null) {
            return;
        }

        $rfqNumber = $this->rfqNumber($rfq);
        $buyerName = MailNotificationHelper::displayName($rfq->buyer);
        $buyerCompany = $rfq->buyer?->company?->company_name;
        $productName = $rfq->product?->name ?? __('order.product');
        $manufacturerUrl = $this->manufacturerRfqUrl($rfq);
        $buyerUrl = $this->buyerRfqUrl($rfq);
        $messagePreview = trim((string) ($rfq->additional_requirements ?? ''));

        MailNotificationHelper::sendIfEmail($manufacturer, function (string $email) use ($rfq, $rfqNumber, $buyerName, $buyerCompany, $productName, $manufacturerUrl, $manufacturer, $messagePreview): void {
            $this->mailingService->send($email, MailTemplate::RfqCreatedManufacturer, [
                'recipientName' => MailNotificationHelper::displayName($manufacturer),
                'buyerName' => $buyerName,
                'buyerDisplayName' => $buyerCompany ? $buyerName.' — '.$buyerCompany : $buyerName,
                'buyerMeta' => __('mail.demo.badges.buyer').($rfq->buyer?->company?->country ? ' · '.$rfq->buyer->company->country : ''),
                'buyerInitials' => MailNotificationHelper::initials($buyerName),
                'rfqNumber' => $rfqNumber,
                'productName' => $productName,
                'messagePreview' => $messagePreview !== '' ? nl2br(e(mb_strlen($messagePreview) > 280 ? mb_substr($messagePreview, 0, 277).'...' : $messagePreview)) : null,
                'inquiryTimestamp' => $rfq->created_at?->format('M j, g:i A'),
                'inquiryTags' => array_filter([
                    ['label' => 'Product', 'value' => $productName],
                    $rfq->quantity !== null ? ['label' => 'Qty', 'value' => $rfq->quantity.' '.($rfq->quantity_unit ?? '')] : null,
                ]),
                'details' => $this->rfqDetails($rfq),
                'ctaUrl' => $manufacturerUrl,
            ]);
        }, 'rfq.created');

        if ($buyer !== null) {
            MailNotificationHelper::sendIfEmail($buyer, function (string $email) use ($buyerName, $rfqNumber, $productName, $buyerUrl): void {
                $this->mailingService->send($email, MailTemplate::RfqSubmittedBuyer, [
                    'buyerName' => $buyerName,
                    'rfqNumber' => $rfqNumber,
                    'productName' => $productName,
                    'ctaUrl' => $buyerUrl,
                ]);
            }, 'rfq.created');
        }

        $this->dispatchInApp(
            recipient: $manufacturer,
            sender: $rfq->buyer,
            type: 'rfq.created',
            title: __('mail.rfq_created_manufacturer.notification_title'),
            body: __('mail.rfq_created_manufacturer.notification_body', [
                'buyer' => $buyerName,
                'rfq' => $rfqNumber,
                'product' => $productName,
            ]),
            data: ['rfq_id' => $rfq->id, 'rfq_number' => $rfqNumber],
            actionUrl: $manufacturerUrl,
        );
    }

    public function notifyQuoted(RfqSubmission $rfq): void
    {
        $rfq->loadMissing(['buyer', 'manufacturer.company', 'product']);
        $buyer = $rfq->buyer;

        if ($buyer === null) {
            return;
        }

        $rfqNumber = $this->rfqNumber($rfq);
        $manufacturerName = MailNotificationHelper::companyOrName($rfq->manufacturer);
        $url = $this->buyerRfqUrl($rfq);

        MailNotificationHelper::sendIfEmail($buyer, function (string $email) use ($rfq, $rfqNumber, $manufacturerName, $url, $buyer): void {
            $this->mailingService->send($email, MailTemplate::RfqQuotedBuyer, $this->transactionalMail(
                prefix: 'mail.rfq_quoted_buyer',
                recipientName: MailNotificationHelper::displayName($buyer),
                replacements: [
                    'name' => MailNotificationHelper::displayName($buyer),
                    'manufacturer' => $manufacturerName,
                    'manufacturerName' => $manufacturerName,
                    'rfq' => $rfqNumber,
                    'rfqNumber' => $rfqNumber,
                ],
                details: array_filter([
                    'RFQ' => $rfqNumber,
                    'Quote' => $rfq->quoted_price !== null
                        ? strtoupper((string) $rfq->quote_currency_code).' '.number_format((float) $rfq->quoted_price, 2)
                        : null,
                    'Valid until' => $rfq->quote_valid_until?->format('F j, Y'),
                ]),
                ctaUrl: $url,
                ctaLabel: __('mail.rfq_quoted_buyer.cta'),
                referenceId: $rfqNumber,
                footerNote: __('mail.rfq_quoted_buyer.footer'),
            ));
        }, 'rfq.quoted');

        $this->dispatchInApp(
            recipient: $buyer,
            sender: $rfq->manufacturer,
            type: 'rfq.quoted',
            title: __('mail.rfq_quoted_buyer.notification_title'),
            body: __('mail.rfq_quoted_buyer.notification_body', [
                'manufacturer' => $manufacturerName,
                'rfq' => $rfqNumber,
            ]),
            data: ['rfq_id' => $rfq->id, 'rfq_number' => $rfqNumber],
            actionUrl: $url,
        );
    }

    public function notifyStatusUpdated(RfqSubmission $rfq, ?User $actor = null): void
    {
        $rfq->loadMissing(['buyer', 'manufacturer.company', 'product']);
        $status = $rfq->status instanceof RfqSubmissionStatus
            ? $rfq->status
            : RfqSubmissionStatus::from((string) $rfq->status);
        $statusLabel = ucfirst(str_replace('_', ' ', $status->value));
        $rfqNumber = $this->rfqNumber($rfq);

        $recipients = collect([$rfq->buyer, $rfq->manufacturer])
            ->filter()
            ->unique('id')
            ->reject(fn (User $user): bool => $actor !== null && (int) $user->id === (int) $actor->id);

        foreach ($recipients as $recipient) {
            $url = $recipient->role?->value === 'manufacturer'
                ? $this->manufacturerRfqUrl($rfq)
                : $this->buyerRfqUrl($rfq);

            MailNotificationHelper::sendIfEmail($recipient, function (string $email) use ($rfq, $rfqNumber, $statusLabel, $url, $recipient): void {
                $this->mailingService->send($email, MailTemplate::RfqStatusUpdated, $this->transactionalMail(
                    prefix: 'mail.rfq_status_updated',
                    recipientName: MailNotificationHelper::displayName($recipient),
                    replacements: [
                        'name' => MailNotificationHelper::displayName($recipient),
                        'rfq' => $rfqNumber,
                        'rfqNumber' => $rfqNumber,
                        'status' => $statusLabel,
                    ],
                    details: $this->rfqDetails($rfq),
                    ctaUrl: $url,
                    ctaLabel: __('mail.rfq_status_updated.cta'),
                    referenceId: $rfqNumber,
                    footerNote: __('mail.rfq_status_updated.footer'),
                ));
            }, 'rfq.status.'.$status->value);

            $this->dispatchInApp(
                recipient: $recipient,
                sender: $actor,
                type: 'rfq.status.'.$status->value,
                title: __('mail.rfq_status_updated.notification_title'),
                body: __('mail.rfq_status_updated.notification_body', [
                    'rfq' => $rfqNumber,
                    'status' => $statusLabel,
                ]),
                data: ['rfq_id' => $rfq->id, 'status' => $status->value],
                actionUrl: $url,
            );
        }
    }

    /**
     * @param  array<string, string>  $replacements
     * @param  array<string, mixed>  $details
     * @return array<string, mixed>
     */
    private function transactionalMail(
        string $prefix,
        string $recipientName,
        array $replacements,
        array $details = [],
        ?string $ctaUrl = null,
        ?string $ctaLabel = null,
        ?string $referenceId = null,
        ?string $footerNote = null,
        ?string $messageBody = null,
    ): array {
        return [
            'preheader' => __($prefix.'.preheader', $replacements),
            'headerEyebrow' => __('mail.layout.default_eyebrow'),
            'headerTitle' => __($prefix.'.header_title', $replacements),
            'headerSubtitle' => __($prefix.'.header_subtitle', $replacements),
            'alertTag' => Lang::has($prefix.'.alert_tag') ? __($prefix.'.alert_tag', $replacements) : null,
            'alertHeading' => Lang::has($prefix.'.alert_heading') ? __($prefix.'.alert_heading', $replacements) : null,
            'greeting' => null,
            'intro' => __($prefix.'.intro', $replacements),
            'messageHeading' => $messageBody ? __($prefix.'.message_heading', $replacements) : null,
            'messageBody' => $messageBody ? nl2br(e($messageBody)) : null,
            'detailsHeading' => $details !== [] ? __($prefix.'.details_heading', $replacements) : null,
            'details' => collect($details)->filter(fn ($v) => $v !== null && $v !== '')->all(),
            'ctaUrl' => $ctaUrl,
            'ctaLabel' => $ctaLabel,
            'referenceId' => $referenceId,
            'footerNote' => $footerNote,
        ];
    }

    /**
     * @return array<string, string>
     */
    private function rfqDetails(RfqSubmission $rfq): array
    {
        return array_filter([
            'RFQ' => $this->rfqNumber($rfq),
            'Product' => $rfq->product?->name,
            'Quantity' => $rfq->quantity !== null ? $rfq->quantity.' '.($rfq->quantity_unit ?? '') : null,
            'Status' => $rfq->status instanceof RfqSubmissionStatus
                ? ucfirst(str_replace('_', ' ', $rfq->status->value))
                : (string) $rfq->status,
        ]);
    }

    private function rfqNumber(RfqSubmission $rfq): string
    {
        return $rfq->rfq_number ?? sprintf('RFQ-%03d', $rfq->id);
    }

    private function manufacturerRfqUrl(RfqSubmission $rfq): string
    {
        return MailNotificationHelper::frontendUrl('dashboard/manufacturer/inquiries/'.$rfq->id);
    }

    private function buyerRfqUrl(RfqSubmission $rfq): string
    {
        return MailNotificationHelper::frontendUrl('dashboard/buyer/rfqs/'.$rfq->id);
    }

    /**
     * @param  array<string, mixed>  $data
     */
    private function dispatchInApp(
        User $recipient,
        ?User $sender,
        string $type,
        string $title,
        string $body,
        array $data,
        string $actionUrl,
    ): void {
        SendSupportTicketInAppNotificationJob::dispatch(
            recipientId: $recipient->id,
            type: $type,
            title: $title,
            body: $body,
            data: $data,
            actionUrl: $actionUrl,
            senderId: $sender?->id,
        );
    }
}
