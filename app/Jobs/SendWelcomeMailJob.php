<?php

namespace App\Jobs;

use App\Enums\MailTemplate;
use App\Services\Mailing\MailingService;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;

/**
 * @deprecated Use {@see MailingService::send()} instead.
 */
class SendWelcomeMailJob implements ShouldQueue
{
    use Queueable;

    public function __construct(
        public string $email,
        public string $firstName,
    ) {}

    public function handle(MailingService $mailingService): void
    {
        $mailingService->send($this->email, MailTemplate::Welcome, [
            'firstName' => trim($this->firstName) !== '' ? trim($this->firstName) : 'there',
        ]);
    }
}
