<?php

namespace App\Contracts\Payment;

use App\DTO\Payment\RenewalChargeResult;
use App\Models\Subscription;

interface RenewalDriverInterface
{
    public function supports(string $paymentMethod): bool;

    public function hasReusablePaymentMethod(Subscription $subscription): bool;

    public function charge(Subscription $subscription, float $amount, string $currency): RenewalChargeResult;
}
