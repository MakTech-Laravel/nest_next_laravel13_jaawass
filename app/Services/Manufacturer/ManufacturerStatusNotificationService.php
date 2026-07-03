<?php

namespace App\Services\Manufacturer;

use App\Enums\MailTemplate;
use App\Enums\UserManuFactureStatus;
use App\Jobs\Support\SendSupportTicketInAppNotificationJob;
use App\Models\User;
use App\Services\Mailing\MailingService;
use App\Support\Mail\MailNotificationHelper;

class ManufacturerStatusNotificationService
{
    public function __construct(
        private readonly MailingService $mailingService,
    ) {}

    public function notifyStatusChanged(User $manufacturer, UserManuFactureStatus $status, ?string $reason = null): void
    {
        $manufacturer->loadMissing('company');
        $company = $manufacturer->company?->company_name ?? config('app.name');
        $name = MailNotificationHelper::displayName($manufacturer);
        $dashboardUrl = MailNotificationHelper::frontendUrl('dashboard/manufacturer');
        $supportUrl = MailNotificationHelper::frontendUrl('dashboard/manufacturer/support-tickets');

        if ($status === UserManuFactureStatus::APPROVED) {
            MailNotificationHelper::sendIfEmail($manufacturer, function (string $email) use ($name, $company, $dashboardUrl): void {
                $this->mailingService->send($email, MailTemplate::ManufacturerApproved, [
                    'preheader' => __('mail.manufacturer_approved.preheader'),
                    'headerEyebrow' => __('mail.layout.default_eyebrow'),
                    'headerTitle' => __('mail.manufacturer_approved.header_title'),
                    'headerSubtitle' => __('mail.manufacturer_approved.header_subtitle'),
                    'alertTag' => __('mail.manufacturer_approved.alert_tag'),
                    'alertHeading' => __('mail.manufacturer_approved.alert_heading'),
                    'intro' => __('mail.manufacturer_approved.intro', ['name' => $name, 'company' => $company]),
                    'ctaUrl' => $dashboardUrl,
                    'ctaLabel' => __('mail.manufacturer_approved.cta'),
                    'footerNote' => __('mail.manufacturer_approved.footer'),
                ]);
            });

            $this->dispatchInApp(
                $manufacturer,
                'manufacturer.approved',
                __('mail.manufacturer_approved.notification_title'),
                __('mail.manufacturer_approved.notification_body'),
                $dashboardUrl,
            );

            return;
        }

        if ($status === UserManuFactureStatus::REJECTED) {
            MailNotificationHelper::sendIfEmail($manufacturer, function (string $email) use ($name, $company, $reason, $supportUrl): void {
                $this->mailingService->send($email, MailTemplate::ManufacturerRejected, [
                    'preheader' => __('mail.manufacturer_rejected.preheader'),
                    'headerEyebrow' => __('mail.layout.default_eyebrow'),
                    'headerTitle' => __('mail.manufacturer_rejected.header_title'),
                    'headerSubtitle' => __('mail.manufacturer_rejected.header_subtitle'),
                    'intro' => __('mail.manufacturer_rejected.intro', ['name' => $name, 'company' => $company]),
                    'messageHeading' => __('mail.manufacturer_rejected.message_heading'),
                    'messageBody' => $reason ? nl2br(e($reason)) : null,
                    'ctaUrl' => $supportUrl,
                    'ctaLabel' => __('mail.manufacturer_rejected.cta'),
                    'footerNote' => __('mail.manufacturer_rejected.footer'),
                ]);
            });

            $this->dispatchInApp(
                $manufacturer,
                'manufacturer.rejected',
                __('mail.manufacturer_rejected.notification_title'),
                __('mail.manufacturer_rejected.notification_body'),
                $supportUrl,
            );
        }
    }

    private function dispatchInApp(
        User $manufacturer,
        string $type,
        string $title,
        string $body,
        string $actionUrl,
    ): void {
        SendSupportTicketInAppNotificationJob::dispatch(
            recipientId: $manufacturer->id,
            type: $type,
            title: $title,
            body: $body,
            data: ['manufacturer_id' => $manufacturer->id],
            actionUrl: $actionUrl,
            senderId: null,
        );
    }
}
