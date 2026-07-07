<?php

namespace App\Services\Manufacturer;

use App\Enums\MailTemplate;
use App\Jobs\Support\SendSupportTicketInAppNotificationJob;
use App\Models\User;
use App\Services\Mailing\MailingService;
use App\Support\Mail\MailNotificationHelper;

class ManufacturerRegistrationNotificationService
{
    public function __construct(
        private readonly MailingService $mailingService,
    ) {}

    public function notifyAdmins(User $manufacturer): void
    {
        $manufacturer->loadMissing(['company', 'factoryImages']);

        if ($manufacturer->role?->value !== 'manufacturer') {
            return;
        }

        $mailData = $this->mailData($manufacturer);
        $reviewUrl = $this->reviewUrl($manufacturer);

        foreach (MailNotificationHelper::adminRecipients() as $admin) {
            MailNotificationHelper::sendIfEmail($admin, function (string $email) use ($mailData): void {
                $this->mailingService->send($email, MailTemplate::ManufacturerRegisteredAdmin, $mailData);
            });

            $this->dispatchInAppNotification($admin, $manufacturer, $mailData, $reviewUrl);
        }
    }

    /**
     * @return array<string, mixed>
     */
    private function mailData(User $manufacturer): array
    {
        $companyName = MailNotificationHelper::companyOrName($manufacturer);
        $manufacturerName = MailNotificationHelper::displayName($manufacturer);
        $referenceId = sprintf('REG-%s-%04d', now()->format('Ymd'), $manufacturer->id);
        $reviewUrl = $this->reviewUrl($manufacturer);
        $notes = trim((string) ($manufacturer->company?->notes ?? ''));

        $replacements = [
            'name' => $manufacturerName,
            'company' => $companyName,
            'email' => (string) $manufacturer->email,
        ];

        $details = array_filter([
            __('mail.manufacturer_registered_admin.company') => $companyName,
            __('mail.manufacturer_registered_admin.contact') => $manufacturerName,
            __('mail.manufacturer_registered_admin.email') => $manufacturer->email,
            __('mail.manufacturer_registered_admin.country') => $manufacturer->company?->country,
            __('mail.manufacturer_registered_admin.city') => $manufacturer->company?->city,
            __('mail.manufacturer_registered_admin.registered_at') => $manufacturer->created_at?->format('F j, Y g:i A'),
            __('mail.manufacturer_registered_admin.registration_id') => $referenceId,
        ]);

        return [
            'company' => $companyName,
            'name' => $manufacturerName,
            'email' => (string) $manufacturer->email,
            'preheader' => __('mail.manufacturer_registered_admin.preheader', $replacements),
            'intro' => __('mail.manufacturer_registered_admin.intro', $replacements),
            'messageHeading' => $notes !== '' ? __('mail.manufacturer_registered_admin.message_heading') : null,
            'messageBody' => $notes !== '' ? nl2br(e($notes)) : null,
            'details' => $details,
            'ctaUrl' => $reviewUrl,
            'ctaLabel' => __('mail.manufacturer_registered_admin.cta'),
            'allRegistrationsUrl' => MailNotificationHelper::frontendUrl('admin/manufacturer-registrations'),
            'referenceId' => $referenceId,
        ];
    }

    /**
     * @param  array<string, mixed>  $mailData
     */
    private function dispatchInAppNotification(
        User $admin,
        User $manufacturer,
        array $mailData,
        string $reviewUrl,
    ): void {
        $companyName = MailNotificationHelper::companyOrName($manufacturer);

        SendSupportTicketInAppNotificationJob::dispatch(
            recipientId: $admin->id,
            type: 'manufacturer.registered',
            title: __('mail.manufacturer_registered_admin.notification_title'),
            body: __('mail.manufacturer_registered_admin.notification_body', [
                'company' => $companyName,
                'name' => MailNotificationHelper::displayName($manufacturer),
            ]),
            data: [
                'category' => 'manufacturer_review',
                'manufacturer_id' => $manufacturer->id,
                'reference_id' => $mailData['referenceId'] ?? null,
            ],
            actionUrl: $reviewUrl,
            senderId: $manufacturer->id,
        );
    }

    private function reviewUrl(User $manufacturer): string
    {
        return MailNotificationHelper::frontendUrl(
            'admin/manufacturer-registrations?manufacturer='.$manufacturer->id,
        );
    }
}
