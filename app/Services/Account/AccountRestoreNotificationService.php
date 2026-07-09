<?php

namespace App\Services\Account;

use App\Enums\MailTemplate;
use App\Models\User;
use App\Services\Mailing\MailingService;
use App\Support\Mail\MailNotificationHelper;

class AccountRestoreNotificationService
{
    public function __construct(
        private readonly MailingService $mailingService,
    ) {}

    public function sendOtp(User $user, string $otp): void
    {
        $ttlMinutes = (int) config('account.restore_otp_ttl_minutes', 15);
        $expiresIn = $ttlMinutes.' '.($ttlMinutes === 1 ? 'minute' : 'minutes');

        $this->mailingService->send(
            $user->email,
            MailTemplate::AccountRestoreOtp,
            [
                'otp' => $otp,
                'formattedOtp' => preg_replace('/(\d{3})(?=\d)/', '$1 ', $otp),
                'recipientName' => MailNotificationHelper::displayName($user),
                'ttlMinutes' => $ttlMinutes,
                'expiresIn' => $expiresIn,
                'ctaUrl' => MailNotificationHelper::frontendUrl('auth/restore-account'),
            ],
        );
    }
}
