<?php

namespace App\Jobs;

use App\Services\Mailing\MailgunTransport;
use App\Services\Mailing\MailTemplateRenderer;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Support\Facades\Log;
use Throwable;

class SendMailJob implements ShouldQueue
{
    use Queueable;

    public int $tries = 3;

    /**
     * @var array<int, int>
     */
    public array $backoff = [10, 30, 60];

    /**
     * @param  array<string, mixed>  $data
     */
    public function __construct(
        public string $recipient,
        public string $template,
        public array $data = [],
    ) {}

    public function handle(MailTemplateRenderer $renderer, MailgunTransport $transport): void
    {
        try {
            $html = $renderer->render($this->template, $this->data);
            $subject = $renderer->subject($this->template, $this->data);

            Log::info('SendMailJob preparing to send.', [
                'recipient' => $this->recipient,
                'template' => $this->template,
                'subject' => $subject,
                'urls' => $this->urlsFromData(),
            ]);

            $transport->send($this->recipient, $subject, $html);
        } catch (Throwable $exception) {
            Log::error('SendMailJob attempt failed.', [
                'recipient' => $this->recipient,
                'template' => $this->template,
                'attempt' => $this->attempts(),
                'max_tries' => $this->tries,
                'error' => $exception->getMessage(),
            ]);

            throw $exception;
        }
    }

    public function failed(Throwable $exception): void
    {
        Log::error('SendMailJob permanently failed after all retries.', [
            'recipient' => $this->recipient,
            'template' => $this->template,
            'error' => $exception->getMessage(),
        ]);

        report($exception);
    }

    /**
     * @return array<string, string>
     */
    private function urlsFromData(): array
    {
        $urls = [];

        foreach ($this->data as $key => $value) {
            if (! is_string($value) || $value === '') {
                continue;
            }

            if ($key === 'submissionUrl' || str_ends_with($key, 'Url') || str_ends_with($key, 'url')) {
                $urls[$key] = $value;
            }
        }

        return $urls;
    }
}
