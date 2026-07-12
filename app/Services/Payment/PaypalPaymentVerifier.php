<?php

namespace App\Services\Payment;

use App\Contracts\Payment\PaymentVerifierInterface;
use App\DTO\Payment\VerifiedPaymentDTO;
use App\Enums\Api\V1\Payment\RegisterPaymentManager;
use App\Exceptions\Payment\PaymentVerificationException;
use App\Services\Payment\Paypal\PaypalApiClient;
use Symfony\Component\HttpFoundation\Response;

class PaypalPaymentVerifier implements PaymentVerifierInterface
{
    public function __construct(
        private readonly PaypalApiClient $paypal,
    ) {}

    /**
     * @param  array<string, mixed>  $paymentData
     */
    public function verify(array $paymentData): VerifiedPaymentDTO
    {
        $paymentId = (string) ($paymentData['payment_id'] ?? '');
        $expectedAmount = (float) ($paymentData['paid_amount'] ?? 0);

        if ($paymentId === '') {
            throw new PaymentVerificationException(__('subscription.payment_id_required'));
        }

        $order = $this->fetchOrder($paymentId);
        $orderId = (string) ($order['id'] ?? $paymentId);
        $status = strtoupper((string) ($order['status'] ?? ''));

        if ($status !== 'COMPLETED') {
            throw new PaymentVerificationException(__('subscription.paypal_not_completed'));
        }

        $amountValue = (float) ($order['purchase_units'][0]['amount']['value'] ?? 0);
        $currency = (string) ($order['purchase_units'][0]['amount']['currency_code'] ?? 'USD');

        if (! $this->amountsMatch($amountValue, $expectedAmount)) {
            throw new PaymentVerificationException(__('subscription.fraudulent_payment'));
        }

        $vaultId = $this->paypal->extractVaultId($order);
        $payloadVaultId = (string) ($paymentData['paypal_vault_id'] ?? '');

        if ($vaultId === null && $payloadVaultId !== '') {
            $vaultId = $payloadVaultId;
        }

        return new VerifiedPaymentDTO(
            externalId: $orderId,
            amount: $amountValue,
            currency: $currency,
            status: strtolower($status),
            paymentMethod: RegisterPaymentManager::PAYPAL->value,
            vaultId: $vaultId,
            payerId: $this->paypal->extractPayerId($order),
        );
    }

    /**
     * @return array<string, mixed>
     */
    private function fetchOrder(string $paymentId): array
    {
        $order = $this->paypal->findOrder($paymentId);

        if ($order !== null) {
            return $order;
        }

        $capture = $this->paypal->getCapture($paymentId);
        $captureStatus = strtoupper((string) ($capture['status'] ?? ''));

        if ($captureStatus !== 'COMPLETED') {
            throw new PaymentVerificationException(__('subscription.paypal_not_completed'));
        }

        $orderId = (string) ($capture['supplementary_data']['related_ids']['order_id'] ?? '');

        if ($orderId === '') {
            throw new PaymentVerificationException(
                __('subscription.paypal_verification_failed'),
                Response::HTTP_BAD_GATEWAY,
            );
        }

        return $this->paypal->getOrder($orderId);
    }

    private function amountsMatch(float $actual, float $expected): bool
    {
        return abs(round($actual, 2) - round($expected, 2)) < 0.01;
    }
}
