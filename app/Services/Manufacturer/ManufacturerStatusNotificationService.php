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
        $subscriptionUrl = MailNotificationHelper::frontendUrl('subscription');
        $profileUrl = MailNotificationHelper::frontendUrl('dashboard/manufacturer/profile');

        if ($status === UserManuFactureStatus::APPROVED) {
            MailNotificationHelper::sendIfEmail($manufacturer, function (string $email) use ($name, $company, $subscriptionUrl): void {
                $this->mailingService->send($email, MailTemplate::ManufacturerApproved, [
                    'name' => $name,
                    'company' => $company,
                    'intro' => __('mail.manufacturer_approved.intro', ['name' => $name, 'company' => $company]),
                    'ctaUrl' => $subscriptionUrl,
                    'ctaLabel' => __('mail.manufacturer_approved.cta'),
                ]);
            });

            $this->dispatchInApp(
                $manufacturer,
                'manufacturer.approved',
                __('mail.manufacturer_approved.notification_title'),
                __('mail.manufacturer_approved.notification_body'),
                $subscriptionUrl,
            );

            return;
        }

        if ($status === UserManuFactureStatus::REJECTED) {
            MailNotificationHelper::sendIfEmail($manufacturer, function (string $email) use ($name, $company, $reason, $profileUrl): void {
                $this->mailingService->send($email, MailTemplate::ManufacturerRejected, [
                    'name' => $name,
                    'company' => $company,
                    'reason' => $reason,
                    'decisionDate' => now()->format('F j, Y'),
                    'intro' => __('mail.manufacturer_rejected.intro', ['name' => $name, 'company' => $company]),
                    'ctaUrl' => $profileUrl,
                    'ctaLabel' => __('mail.manufacturer_rejected.cta'),
                ]);
            });

            $this->dispatchInApp(
                $manufacturer,
                'manufacturer.rejected',
                __('mail.manufacturer_rejected.notification_title'),
                __('mail.manufacturer_rejected.notification_body'),
                $profileUrl,
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
