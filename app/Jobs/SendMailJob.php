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
        $html = $renderer->render($this->template, $this->data);
        $subject = $renderer->subject($this->template, $this->data);

        $transport->send($this->recipient, $subject, $html);
    }

    public function failed(Throwable $exception): void
    {
        Log::error('Mail job permanently failed.', [
            'recipient' => $this->recipient,
            'template' => $this->template,
            'exception' => $exception,
        ]);
    }
}
