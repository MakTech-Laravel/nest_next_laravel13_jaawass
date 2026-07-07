<?php

namespace App\Services\Registration;

use App\Enums\MailTemplate;
use App\Enums\UserRole;
use App\Models\User;
use App\Services\Mailing\MailingService;
use App\Support\Mail\MailNotificationHelper;

class BuyerRegistrationReminderService
{
    public function __construct(
        private readonly MailingService $mailingService,
    ) {}

    public function sendReminder(User $user): void
    {
        if ($user->role !== UserRole::BUYER) {
            return;
        }

        MailNotificationHelper::sendIfEmail($user, function (string $email) use ($user): void {
            $this->mailingService->send($email, MailTemplate::BuyerRegistrationReminder, [
                'name' => MailNotificationHelper::displayName($user),
                'ctaUrl' => MailNotificationHelper::frontendUrl($user->hasVerifiedEmail() ? 'dashboard/buyer' : 'verify'),
            ]);
        });

        $user->forceFill(['buyer_registration_reminder_sent_at' => now()])->save();
    }
}
