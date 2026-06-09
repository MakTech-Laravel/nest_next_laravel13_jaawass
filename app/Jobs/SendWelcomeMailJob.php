<?php

namespace App\Jobs;

use App\Mail\WelcomeMail;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;
use Throwable;

class SendWelcomeMailJob implements ShouldQueue
{
    use Queueable;

    public int $tries = 3;

    /**
     * @var array<int, int>
     */
    public array $backoff = [10, 30, 60];

    public function __construct(
        public string $email,
        public string $firstName,
    ) {}

    public function handle(): void
    {
        $greetingName = trim($this->firstName) !== '' ? trim($this->firstName) : 'there';
        Mail::mailer('log')->to($this->email)->send(new WelcomeMail($greetingName));
        Mail::to($this->email)->send(new WelcomeMail($greetingName));
    }

    public function failed(Throwable $exception): void
    {
        Log::error('Welcome email job permanently failed.', [
            'email' => $this->email,
            'exception' => $exception,
        ]);
    }
}
