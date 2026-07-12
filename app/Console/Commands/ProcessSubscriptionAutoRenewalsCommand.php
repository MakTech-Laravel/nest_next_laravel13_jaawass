<?php

namespace App\Console\Commands;

use App\Enums\Api\V1\Payment\RegisterPaymentManager;
use App\Enums\Api\V1\SubscriptionStatus;
use App\Jobs\Subscription\ProcessSubscriptionAutoRenewJob;
use App\Models\Subscription;
use Illuminate\Console\Command;

class ProcessSubscriptionAutoRenewalsCommand extends Command
{
    protected $signature = 'subscriptions:auto-renew';

    protected $description = 'Queue auto-renewal charges for due PayPal subscriptions (Stripe is skipped).';

    public function handle(): int
    {
        if (! config('subscription.auto_renew.enabled', true)) {
            $this->warn('Subscription auto-renew is disabled.');

            return self::SUCCESS;
        }

        $dispatched = 0;
        $maxAttempts = (int) config('subscription.auto_renew.max_attempts', 3);

        Subscription::query()
            ->where('auto_renew', true)
            ->whereNotNull('ends_at')
            ->where('ends_at', '<=', now())
            ->whereIn('status', [
                SubscriptionStatus::ACTIVE->value,
                SubscriptionStatus::TRIALING->value,
                SubscriptionStatus::PAST_DUE->value,
            ])
            ->where('renew_attempts', '<', $maxAttempts)
            ->where(function ($query): void {
                $query
                    ->where(function ($paypal): void {
                        $paypal
                            ->where('payment_method', RegisterPaymentManager::PAYPAL->value)
                            ->whereNotNull('paypal_vault_id')
                            ->where('paypal_vault_id', '!=', '');
                    })
                    ->orWhere('payment_method', RegisterPaymentManager::STRIPE->value);
            })
            ->orderBy('id')
            ->chunkById(100, function ($subscriptions) use (&$dispatched): void {
                foreach ($subscriptions as $subscription) {
                    ProcessSubscriptionAutoRenewJob::dispatch($subscription->id);
                    $dispatched++;
                }
            });

        $this->info("Queued {$dispatched} subscription auto-renew job(s).");

        return self::SUCCESS;
    }
}
