<?php

namespace App\Jobs\Subscription;

use App\Models\Subscription;
use App\Services\Subscription\SubscriptionAutoRenewService;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;

class ProcessSubscriptionAutoRenewJob implements ShouldQueue
{
    use Queueable;

    public int $tries = 3;

    /** @var list<int> */
    public array $backoff = [30, 120, 300];

    public function __construct(
        public readonly int $subscriptionId,
    ) {
        $this->onQueue((string) config('subscription.queue', 'default'));
    }

    public function handle(SubscriptionAutoRenewService $autoRenewService): void
    {
        $subscription = Subscription::query()->find($this->subscriptionId);

        if ($subscription === null) {
            return;
        }

        $autoRenewService->process($subscription);
    }
}
