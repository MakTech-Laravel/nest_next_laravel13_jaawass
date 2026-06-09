<?php

namespace App\Jobs;

use App\Mail\AccountRestoreOtpMail;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Support\Facades\Mail;

class SendAccountRestoreOtpMailJob implements ShouldQueue
{
    use Queueable;

    public function __construct(
        public string $email,
        public string $otp
    ) {}

    public function handle(): void
    {
        Mail::to($this->email)->send(new AccountRestoreOtpMail($this->otp));
    }
}
