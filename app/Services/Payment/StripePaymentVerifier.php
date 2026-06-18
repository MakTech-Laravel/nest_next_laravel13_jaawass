<?php

namespace App\Services\Payment;

use App\Contracts\Payment\PaymentVerifierInterface;
use App\DTO\Payment\VerifiedPaymentDTO;
use App\Enums\Api\V1\Payment\RegisterPaymentManager;
use App\Exceptions\Payment\PaymentVerificationException;
use Stripe\PaymentIntent;
use Stripe\Stripe;

class StripePaymentVerifier implements PaymentVerifierInterface
{
    /**
     * @param  array<string, mixed>  $paymentData
     */
    public function verify(array $paymentData): VerifiedPaymentDTO
    {
        $paymentIntentId = (string) ($paymentData['payment_id'] ?? '');
        $expectedAmount = (float) ($paymentData['paid_amount'] ?? 0);

        if ($paymentIntentId === '') {
            throw new PaymentVerificationException(__('subscription.payment_id_required'));
        }

        $secret = (string) config('services.stripe.secret');

        if ($secret === '') {
            throw new PaymentVerificationException(__('subscription.stripe_not_configured'));
        }

        Stripe::setApiKey($secret);

        $paymentIntent = PaymentIntent::retrieve($paymentIntentId);
        $status = (string) $paymentIntent->status;

        if ($status !== 'succeeded') {
            throw new PaymentVerificationException(__('subscription.stripe_not_succeeded'));
        }

        $actualAmount = ((float) $paymentIntent->amount) / 100;

        if (abs(round($actualAmount, 2) - round($expectedAmount, 2)) >= 0.01) {
            throw new PaymentVerificationException(__('subscription.fraudulent_payment'));
        }

        return new VerifiedPaymentDTO(
            externalId: $paymentIntentId,
            amount: $actualAmount,
            currency: strtoupper((string) $paymentIntent->currency),
            status: $status,
            paymentMethod: RegisterPaymentManager::STRIPE->value,
        );
    }
}
