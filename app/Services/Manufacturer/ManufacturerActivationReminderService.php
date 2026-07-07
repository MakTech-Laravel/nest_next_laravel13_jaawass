<?php

namespace App\Services\Manufacturer;

use App\Enums\MailTemplate;
use App\Enums\UserManuFactureStatus;
use App\Enums\UserRole;
use App\Models\User;
use App\Services\Mailing\MailingService;
use App\Support\Mail\MailNotificationHelper;

class ManufacturerActivationReminderService
{
    public function __construct(
        private readonly MailingService $mailingService,
    ) {}

    public function sendReminder(User $user): void
    {
        if ($user->role !== UserRole::MANUFACTURER || $user->manufacture_status !== UserManuFactureStatus::APPROVED) {
            return;
        }

        $user->loadMissing(['company', 'subscription']);

        if ($user->subscription !== null && $user->subscription->ends_at !== null && $user->subscription->ends_at->isFuture()) {
            return;
        }

        MailNotificationHelper::sendIfEmail($user, function (string $email) use ($user): void {
            $this->mailingService->send($email, MailTemplate::ManufacturerActivationReminder, [
                'name' => MailNotificationHelper::displayName($user),
                'company' => MailNotificationHelper::companyOrName($user),
                'approvedAt' => $user->manufacture_status_at?->format('F j, Y'),
                'ctaUrl' => MailNotificationHelper::frontendUrl('subscription'),
            ]);
        });

        $user->forceFill(['manufacturer_activation_reminder_sent_at' => now()])->save();
    }
}
