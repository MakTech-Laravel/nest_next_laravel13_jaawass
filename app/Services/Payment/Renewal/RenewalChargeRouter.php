<?php

namespace App\Services\Payment\Renewal;

use App\Contracts\Payment\RenewalDriverInterface;
use App\DTO\Payment\RenewalChargeResult;
use App\Enums\Api\V1\Payment\RegisterPaymentManager;
use App\Models\Subscription;

class RenewalChargeRouter
{
    public function __construct(
        private readonly PaypalRenewalDriver $paypalRenewalDriver,
        private readonly StripeRenewalDriver $stripeRenewalDriver,
    ) {}

    public function charge(Subscription $subscription, float $amount, string $currency): RenewalChargeResult
    {
        $method = strtolower((string) ($subscription->payment_method ?? ''));

        if ($method === '') {
            return RenewalChargeResult::skipped(__('subscription.payment_method_missing'));
        }

        $driver = $this->resolveDriver($method);

        if ($driver === null) {
            return RenewalChargeResult::skipped(__('subscription.payment_method_not_supported'));
        }

        if ($method === RegisterPaymentManager::STRIPE->value) {
            return $driver->charge($subscription, $amount, $currency);
        }

        if (! $driver->hasReusablePaymentMethod($subscription)) {
            return RenewalChargeResult::failed(__('subscription.paypal_vault_missing'));
        }

        return $driver->charge($subscription, $amount, $currency);
    }

    public function resolveDriver(string $paymentMethod): ?RenewalDriverInterface
    {
        return match (strtolower($paymentMethod)) {
            RegisterPaymentManager::PAYPAL->value => $this->paypalRenewalDriver,
            RegisterPaymentManager::STRIPE->value => $this->stripeRenewalDriver,
            default => null,
        };
    }
}
