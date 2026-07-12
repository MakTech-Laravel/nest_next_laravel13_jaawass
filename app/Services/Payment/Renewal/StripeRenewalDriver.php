<?php

namespace App\Services\Payment\Renewal;

use App\Contracts\Payment\RenewalDriverInterface;
use App\DTO\Payment\RenewalChargeResult;
use App\Enums\Api\V1\Payment\RegisterPaymentManager;
use App\Models\Subscription;

class StripeRenewalDriver implements RenewalDriverInterface
{
    public function supports(string $paymentMethod): bool
    {
        return strtolower($paymentMethod) === RegisterPaymentManager::STRIPE->value;
    }

    public function hasReusablePaymentMethod(Subscription $subscription): bool
    {
        return false;
    }

    public function charge(Subscription $subscription, float $amount, string $currency): RenewalChargeResult
    {
        return RenewalChargeResult::skipped(__('subscription.stripe_auto_renew_not_implemented'));
    }
}
