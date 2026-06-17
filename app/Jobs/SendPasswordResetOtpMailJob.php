<?php

namespace App\Jobs;

use App\Enums\MailTemplate;
use App\Services\Mailing\MailingService;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;

/**
 * @deprecated Use {@see MailingService::send()} instead.
 */
class SendPasswordResetOtpMailJob implements ShouldQueue
{
    use Queueable;

    public function __construct(
        public string $email,
        public string $otp
    ) {}

    public function handle(MailingService $mailingService): void
    {
        $mailingService->send($this->email, MailTemplate::PasswordResetOtp, [
            'otp' => $this->otp,
        ]);
    }
}
