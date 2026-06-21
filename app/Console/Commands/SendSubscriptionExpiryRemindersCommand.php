<?php

namespace App\Console\Commands;

use App\Jobs\Subscription\SendSubscriptionExpiryReminderJob;
use App\Models\Subscription;
use Illuminate\Console\Command;

class SendSubscriptionExpiryRemindersCommand extends Command
{
    protected $signature = 'subscriptions:send-expiry-reminders';

    protected $description = 'Queue expiry reminder emails for subscriptions ending in the configured number of days.';

    public function handle(): int
    {
        $reminderDays = (int) config('subscription.expiry_reminder_days', 7);
        $targetDate = now()->addDays($reminderDays)->toDateString();
        $dispatched = 0;

        Subscription::query()
            ->entitlementActive()
            ->whereNotNull('ends_at')
            ->whereNull('expiry_reminder_sent_at')
            ->whereDate('ends_at', $targetDate)
            ->orderBy('id')
            ->chunkById(100, function ($subscriptions) use (&$dispatched): void {
                foreach ($subscriptions as $subscription) {
                    SendSubscriptionExpiryReminderJob::dispatch($subscription->id);
                    $dispatched++;
                }
            });

        $this->info("Queued {$dispatched} subscription expiry reminder job(s) for ends_at={$targetDate}.");

        return self::SUCCESS;
    }
}
