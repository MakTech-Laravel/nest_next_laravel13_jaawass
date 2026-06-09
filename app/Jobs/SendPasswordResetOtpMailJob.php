<?php

namespace App\Jobs;

use App\Mail\PasswordResetOtpMail;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;

class SendPasswordResetOtpMailJob implements ShouldQueue
{
    use Queueable;

    public function __construct(
        public string $email,
        public string $otp
    ) {}

    public function handle(): void
    {
        Log::info('Sending password reset OTP to email: ' . $this->email);
        Mail::to($this->email)->send(new PasswordResetOtpMail($this->otp));
    }
}
