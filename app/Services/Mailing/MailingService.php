<?php

namespace App\Services\Mailing;

use App\Enums\MailTemplate;
use App\Jobs\SendMailJob;
use Illuminate\Support\Facades\Cache;

class MailingService
{
    public function send(string $recipient, MailTemplate|string $template, array $data = []): void
    {
        $templateName = $template instanceof MailTemplate ? $template->value : $template;

        $delaySeconds = $this->resolveJobDelaySeconds();

        $pendingDispatch = SendMailJob::dispatch($recipient, $templateName, $data)
            ->onQueue((string) config('mailing.queue', 'default'));

        if ($delaySeconds > 0) {
            $pendingDispatch->delay(now()->addSeconds($delaySeconds));
        }
    }

    private function resolveJobDelaySeconds(): int
    {
        $interval = (int) config('mailing.job_delay_seconds', 1);

        if ($interval <= 0) {
            return 0;
        }

        $sequence = (int) Cache::increment((string) config('mailing.dispatch_sequence_cache_key'));

        return ($sequence - 1) * $interval;
    }
}
