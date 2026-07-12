<?php

namespace App\Services\Subscription;

use App\Enums\Api\V1\BillingInterval;
use App\Enums\Api\V1\Payment\RegisterPaymentManager;
use App\Enums\Api\V1\SubscriptionEventType;
use App\Enums\Api\V1\SubscriptionSource;
use App\Enums\Api\V1\SubscriptionStatus;
use App\Models\Payment;
use App\Models\Plan;
use App\Models\Subscription;
use App\Services\Payment\PaymentFailedNotificationService;
use App\Services\Payment\Renewal\RenewalChargeRouter;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class SubscriptionAutoRenewService
{
    public function __construct(
        private readonly RenewalChargeRouter $renewalChargeRouter,
        private readonly SubscriptionAmountResolver $amountResolver,
        private readonly SubscriptionLogService $subscriptionLogService,
        private readonly SubscriptionNotificationService $notificationService,
        private readonly PlanEntitlementResolver $entitlementResolver,
        private readonly PaymentFailedNotificationService $paymentFailedNotificationService,
    ) {}

    public function process(Subscription $subscription): string
    {
        if (! $this->isEligibleCandidate($subscription)) {
            return 'ineligible';
        }

        return DB::transaction(function () use ($subscription): string {
            $locked = Subscription::query()
                ->whereKey($subscription->id)
                ->lockForUpdate()
                ->with(['plan.currency', 'promotion', 'manufacturer', 'payments'])
                ->first();

            if ($locked === null || ! $this->isEligibleCandidate($locked)) {
                return 'ineligible';
            }

            $method = strtolower((string) ($locked->payment_method ?? ''));

            if ($method === RegisterPaymentManager::STRIPE->value) {
                Log::info('subscription.auto_renew.skipped_stripe', [
                    'subscription_id' => $locked->id,
                ]);

                return 'skipped_stripe';
            }

            if ($method !== RegisterPaymentManager::PAYPAL->value) {
                return 'skipped_unsupported';
            }

            ['amount' => $amount, 'currency' => $currency] = $this->amountResolver->resolve($locked);

            if ($amount <= 0) {
                $this->markRenewFailure($locked, __('subscription.invalid_renew_amount'));

                return 'failed';
            }

            $result = $this->renewalChargeRouter->charge($locked, $amount, $currency);

            if ($result->isSkipped()) {
                Log::info('subscription.auto_renew.skipped', [
                    'subscription_id' => $locked->id,
                    'reason' => $result->message,
                ]);

                return 'skipped';
            }

            if ($result->isFailed()) {
                $this->markRenewFailure($locked, $result->message ?? __('subscription.paypal_renew_charge_failed'));

                return 'failed';
            }

            $this->markRenewSuccess(
                $locked,
                (string) $result->externalId,
                (float) $result->amount,
                (string) ($result->currency ?: $currency),
            );

            return 'renewed';
        });
    }

    public function isEligibleCandidate(Subscription $subscription): bool
    {
        if (! $subscription->auto_renew) {
            return false;
        }

        if ($subscription->ends_at === null || $subscription->ends_at->isFuture()) {
            return false;
        }

        $status = $subscription->status instanceof SubscriptionStatus
            ? $subscription->status->value
            : (string) $subscription->status;

        if (! in_array($status, [
            SubscriptionStatus::ACTIVE->value,
            SubscriptionStatus::TRIALING->value,
            SubscriptionStatus::PAST_DUE->value,
        ], true)) {
            return false;
        }

        $maxAttempts = (int) config('subscription.auto_renew.max_attempts', 3);

        if ((int) $subscription->renew_attempts >= $maxAttempts) {
            return false;
        }

        $retryHours = (int) config('subscription.auto_renew.retry_hours', 24);

        if (
            $subscription->last_renew_attempt_at !== null
            && (int) $subscription->renew_attempts > 0
            && $subscription->last_renew_attempt_at->gt(now()->subHours($retryHours))
        ) {
            return false;
        }

        $method = strtolower((string) ($subscription->payment_method ?? ''));

        if ($method === RegisterPaymentManager::PAYPAL->value) {
            return filled($subscription->paypal_vault_id);
        }

        // Stripe is discovered by the command for skip logging, but not charged.
        return $method === RegisterPaymentManager::STRIPE->value;
    }

    public function shouldDeferExpiry(Subscription $subscription): bool
    {
        if (! $subscription->auto_renew) {
            return false;
        }

        $method = strtolower((string) ($subscription->payment_method ?? ''));

        if ($method !== RegisterPaymentManager::PAYPAL->value) {
            return false;
        }

        if (! filled($subscription->paypal_vault_id)) {
            return false;
        }

        $maxAttempts = (int) config('subscription.auto_renew.max_attempts', 3);

        return (int) $subscription->renew_attempts < $maxAttempts;
    }

    private function markRenewSuccess(
        Subscription $subscription,
        string $externalId,
        float $amount,
        string $currency,
    ): void {
        $existingPayment = Payment::query()->where('payment_id', $externalId)->first();

        if ($existingPayment !== null) {
            return;
        }

        $plan = $subscription->plan ?? Plan::query()->findOrFail($subscription->plan_id);
        $fromPlanId = $subscription->plan_id;
        $billingInterval = $this->normalizeInterval($subscription->billing_interval);
        $startsAt = Carbon::now();
        $endsAt = $billingInterval === BillingInterval::YEAR
            ? $startsAt->copy()->addYear()
            : $startsAt->copy()->addMonth();

        $subscription->update([
            'status' => SubscriptionStatus::ACTIVE->value,
            'starts_at' => $startsAt,
            'ends_at' => $endsAt,
            'trial_ends_at' => null,
            'auto_renew' => true,
            'expiry_reminder_sent_at' => null,
            'source' => SubscriptionSource::PURCHASE->value,
            'promotion_id' => null,
            'payment_method' => RegisterPaymentManager::PAYPAL->value,
            'renew_attempts' => 0,
            'last_renew_attempt_at' => now(),
            'last_renewed_at' => now(),
        ]);

        Payment::query()->create([
            'payment_id' => $externalId,
            'payment_method' => RegisterPaymentManager::PAYPAL->value,
            'amount' => $amount,
            'status' => 'paid',
            'source_id' => $plan->id,
            'source_type' => Plan::class,
            'user_id' => $subscription->manufacturer_id,
            'subscription_id' => $subscription->id,
        ]);

        $this->subscriptionLogService->createSubscriptionLog([
            'manufacturer_id' => $subscription->manufacturer_id,
            'from_plan_id' => $fromPlanId,
            'to_plan_id' => $plan->id,
            'paid_amount' => $amount,
            'event_type' => SubscriptionEventType::SUBSCRIPTION_RENEWED->value,
        ]);

        $fresh = $subscription->fresh(['manufacturer', 'plan']);

        if ($fresh?->manufacturer !== null) {
            $this->entitlementResolver->forget($fresh->manufacturer);
        }

        if ($fresh !== null) {
            $this->notificationService->sendSubscriptionRenewed($fresh, $amount);
        }
    }

    private function markRenewFailure(Subscription $subscription, string $message): void
    {
        $attempts = (int) $subscription->renew_attempts + 1;
        $maxAttempts = (int) config('subscription.auto_renew.max_attempts', 3);

        $payload = [
            'renew_attempts' => $attempts,
            'last_renew_attempt_at' => now(),
        ];

        if ($attempts >= $maxAttempts) {
            $payload['auto_renew'] = false;
            $payload['status'] = SubscriptionStatus::PAST_DUE->value;
        }

        $subscription->update($payload);

        $fresh = $subscription->fresh(['manufacturer', 'plan']);

        if ($fresh?->manufacturer !== null) {
            $this->entitlementResolver->forget($fresh->manufacturer);
            $this->paymentFailedNotificationService->notify(
                $fresh->manufacturer,
                $fresh->plan?->name,
            );
        }

        Log::warning('subscription.auto_renew.failed', [
            'subscription_id' => $subscription->id,
            'attempts' => $attempts,
            'message' => $message,
        ]);
    }

    private function normalizeInterval(mixed $interval): BillingInterval
    {
        if ($interval instanceof BillingInterval) {
            return $interval;
        }

        $value = strtolower((string) $interval);

        return match ($value) {
            'year', 'yearly' => BillingInterval::YEAR,
            default => BillingInterval::MONTH,
        };
    }
}
