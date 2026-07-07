<?php

namespace App\Services\Registration;

use App\Enums\MailTemplate;
use App\Enums\UserManuFactureStatus;
use App\Enums\UserRole;
use App\Models\User;
use App\Services\Mailing\MailingService;
use App\Support\Mail\MailNotificationHelper;

class ManufacturerRegistrationReminderService
{
    public function __construct(
        private readonly MailingService $mailingService,
    ) {}

    public function sendReminder(User $user): void
    {
        if ($user->role !== UserRole::MANUFACTURER || $user->manufacture_status !== UserManuFactureStatus::PENDING) {
            return;
        }

        MailNotificationHelper::sendIfEmail($user, function (string $email) use ($user): void {
            $this->mailingService->send($email, MailTemplate::ManufacturerRegistrationReminder, [
                'name' => MailNotificationHelper::displayName($user),
                'ctaUrl' => MailNotificationHelper::frontendUrl('register'),
            ]);
        });

        $user->forceFill(['manufacturer_registration_reminder_sent_at' => now()])->save();
    }
}
