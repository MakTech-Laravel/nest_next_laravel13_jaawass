<?php

namespace App\Services\Subscription;

use App\Enums\Api\V1\BillingInterval;
use App\Enums\Api\V1\SubscriptionSource;
use App\Enums\Api\V1\SubscriptionStatus;
use App\Models\Plan;
use App\Models\Subscription;

class SubscriptionAmountResolver
{
    /**
     * @return array{amount: float, currency: string}
     */
    public function resolve(Subscription $subscription): array
    {
        $subscription->loadMissing(['plan.currency', 'promotion']);

        $plan = $subscription->plan;
        $currency = strtoupper((string) ($plan?->currency?->code ?: 'USD'));
        $interval = $this->normalizeInterval($subscription->billing_interval);

        if ($this->isPromotionConversion($subscription)) {
            $promoPrice = (float) ($subscription->promotion?->promotional_price ?? 0);
            $requiresPayment = (bool) ($subscription->promotion?->requires_payment ?? false);

            if ($requiresPayment && $promoPrice > 0 && ! $this->hasSuccessfulPayment($subscription)) {
                return [
                    'amount' => round($promoPrice, 2),
                    'currency' => $currency,
                ];
            }
        }

        $amount = $interval === BillingInterval::YEAR
            ? (float) ($plan?->yearly_price ?? 0)
            : (float) ($plan?->monthly_price ?? 0);

        return [
            'amount' => round($amount, 2),
            'currency' => $currency,
        ];
    }

    private function isPromotionConversion(Subscription $subscription): bool
    {
        $source = $subscription->source instanceof SubscriptionSource
            ? $subscription->source
            : SubscriptionSource::tryFrom((string) $subscription->source);

        $status = $subscription->status instanceof SubscriptionStatus
            ? $subscription->status
            : SubscriptionStatus::tryFrom((string) $subscription->status);

        return $source === SubscriptionSource::PROMOTION
            && $status === SubscriptionStatus::TRIALING
            && $subscription->promotion_id !== null;
    }

    private function hasSuccessfulPayment(Subscription $subscription): bool
    {
        return $subscription->payments()
            ->where('status', 'paid')
            ->exists();
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
