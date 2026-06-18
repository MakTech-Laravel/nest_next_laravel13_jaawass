<?php

namespace App\Services\Payment;

use App\Contracts\Payment\PaymentVerifierInterface;
use App\DTO\Payment\VerifiedPaymentDTO;
use App\Enums\Api\V1\Payment\RegisterPaymentManager;
use App\Exceptions\Payment\PaymentVerificationException;

class PaymentCheckService
{
    /**
     * @param  array<string, mixed>  $paymentData
     */
    public function verify(string $paymentMethod, array $paymentData): VerifiedPaymentDTO
    {
        $method = strtolower($paymentMethod);
        $verifier = $this->resolveVerifier($method);

        return $verifier->verify($paymentData);
    }

    private function resolveVerifier(string $paymentMethod): PaymentVerifierInterface
    {
        return match ($paymentMethod) {
            RegisterPaymentManager::PAYPAL->value => app(PaypalPaymentVerifier::class),
            RegisterPaymentManager::STRIPE->value => app(StripePaymentVerifier::class),
            default => throw new PaymentVerificationException(__('subscription.payment_method_not_supported')),
        };
    }
}
