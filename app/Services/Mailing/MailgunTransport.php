<?php

namespace App\Services\Mailing;

use Illuminate\Support\Facades\Log;
use Mailgun\Mailgun;
use RuntimeException;

class MailgunTransport
{
    public function __construct(
        private readonly ?Mailgun $client,
    ) {}

    public function send(string $recipient, string $subject, string $html, ?string $text = null): void
    {
        if ($this->client === null) {
            Log::info('Mailgun is not configured; email logged instead of sent.', [
                'recipient' => $recipient,
                'subject' => $subject,
            ]);

            return;
        }

        $domain = (string) config('services.mailgun.domain');

        if ($domain === '') {
            throw new RuntimeException('Mailgun domain is not configured.');
        }

        $fromAddress = (string) config('mail.from.address');
        $fromName = (string) config('mail.from.name');
        $from = $fromName !== ''
            ? "{$fromName} <{$fromAddress}>"
            : $fromAddress;

        $payload = [
            'from' => $from,
            'to' => $recipient,
            'subject' => $subject,
            'html' => $html,
        ];

        if ($text !== null && $text !== '') {
            $payload['text'] = $text;
        }

        $this->client->messages()->send($domain, $payload);
    }
}
