<?php

namespace App\Services\Mailing;

use Illuminate\Support\Facades\Log;
use Mailgun\Exception\HttpClientException;
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

        [$html, $inline] = $this->extractInlineImagesFromDataUris($html);

        $payload = [
            'from' => $from,
            'to' => $recipient,
            'subject' => $subject,
            'html' => $html,
        ];

        if ($text !== null && $text !== '') {
            $payload['text'] = $text;
        }

        if ($inline !== []) {
            $payload['inline'] = $inline;
        }

        try {
            $this->client->messages()->send($domain, $payload);
        } catch (HttpClientException $exception) {
            Log::error('Mailgun API request failed.', [
                'recipient' => $recipient,
                'domain' => $domain,
                'status' => $exception->getResponseCode(),
                'response' => $exception->getResponseBody(),
                'error' => $exception->getMessage(),
            ]);

            throw $exception;
        }
    }

    /**
     * Gmail/Outlook strip data:image URIs. Convert them to Mailgun CID inline attachments.
     *
     * @return array{0: string, 1: list<array{fileContent: string, filename: string}>}
     */
    private function extractInlineImagesFromDataUris(string $html): array
    {
        $inline = [];
        $index = 0;

        $converted = preg_replace_callback(
            '/src=(["\'])data:image\/([a-zA-Z0-9+.-]+);base64,([A-Za-z0-9+\/=]+)\1/i',
            function (array $matches) use (&$inline, &$index): string {
                $quote = $matches[1];
                $mimeSubtype = strtolower($matches[2]);
                $binary = base64_decode($matches[3], true);

                if ($binary === false || $binary === '') {
                    return $matches[0];
                }

                $extension = match ($mimeSubtype) {
                    'jpeg', 'jpg' => 'jpg',
                    'svg+xml' => 'svg',
                    'x-icon' => 'ico',
                    default => preg_replace('/[^a-z0-9]/', '', $mimeSubtype) ?: 'png',
                };

                $filename = 'inline-'.(++$index).'.'.$extension;
                $inline[] = [
                    'fileContent' => $binary,
                    'filename' => $filename,
                ];

                return 'src='.$quote.'cid:'.$filename.$quote;
            },
            $html,
        );

        return [(string) $converted, $inline];
    }
}
