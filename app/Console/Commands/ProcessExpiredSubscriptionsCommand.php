<?php

namespace App\Console\Commands;

use App\Enums\Api\V1\SubscriptionStatus;
use App\Jobs\Subscription\ProcessExpiredSubscriptionJob;
use App\Models\Subscription;
use Illuminate\Console\Command;

class ProcessExpiredSubscriptionsCommand extends Command
{
    protected $signature = 'subscriptions:process-expired';

    protected $description = 'Queue jobs to mark expired subscriptions inactive and notify manufacturers.';

    public function handle(): int
    {
        $dispatched = 0;

        Subscription::query()
            ->whereIn('status', [
                SubscriptionStatus::ACTIVE->value,
                SubscriptionStatus::TRIALING->value,
            ])
            ->whereNotNull('ends_at')
            ->where('ends_at', '<=', now())
            ->orderBy('id')
            ->chunkById(100, function ($subscriptions) use (&$dispatched): void {
                foreach ($subscriptions as $subscription) {
                    ProcessExpiredSubscriptionJob::dispatch($subscription->id);
                    $dispatched++;
                }
            });

        $this->info("Queued {$dispatched} expired subscription processing job(s).");

        return self::SUCCESS;
    }
}
