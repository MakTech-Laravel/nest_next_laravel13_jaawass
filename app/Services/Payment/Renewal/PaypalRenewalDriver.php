<?php

namespace App\Services\Payment\Renewal;

use App\Contracts\Payment\RenewalDriverInterface;
use App\DTO\Payment\RenewalChargeResult;
use App\Enums\Api\V1\Payment\RegisterPaymentManager;
use App\Exceptions\Payment\PaymentVerificationException;
use App\Models\Subscription;
use App\Services\Payment\Paypal\PaypalApiClient;
use Illuminate\Support\Str;

class PaypalRenewalDriver implements RenewalDriverInterface
{
    public function __construct(
        private readonly PaypalApiClient $paypal,
    ) {}

    public function supports(string $paymentMethod): bool
    {
        return strtolower($paymentMethod) === RegisterPaymentManager::PAYPAL->value;
    }

    public function hasReusablePaymentMethod(Subscription $subscription): bool
    {
        return filled($subscription->paypal_vault_id);
    }

    public function charge(Subscription $subscription, float $amount, string $currency): RenewalChargeResult
    {
        $vaultId = (string) ($subscription->paypal_vault_id ?? '');

        if ($vaultId === '') {
            return RenewalChargeResult::failed(__('subscription.paypal_vault_missing'));
        }

        if ($amount <= 0) {
            return RenewalChargeResult::failed(__('subscription.invalid_renew_amount'));
        }

        $customId = sprintf(
            'sub-%d-renew-%s',
            $subscription->id,
            Str::lower((string) Str::ulid()),
        );

        try {
            $order = $this->paypal->createAndCaptureVaultedOrder(
                vaultId: $vaultId,
                amount: $amount,
                currency: $currency,
                customId: $customId,
            );
        } catch (PaymentVerificationException $exception) {
            return RenewalChargeResult::failed($exception->getMessage());
        }

        $status = strtoupper((string) ($order['status'] ?? ''));
        $orderId = (string) ($order['id'] ?? '');

        if ($orderId === '') {
            return RenewalChargeResult::failed(__('subscription.paypal_renew_charge_failed'));
        }

        if (! in_array($status, ['COMPLETED', 'APPROVED'], true)) {
            $captureStatus = strtoupper((string) data_get(
                $order,
                'purchase_units.0.payments.captures.0.status',
                '',
            ));

            if ($captureStatus !== 'COMPLETED' && $status !== 'COMPLETED') {
                return RenewalChargeResult::failed(__('subscription.paypal_renew_charge_failed'));
            }
        }

        $capturedAmount = (float) data_get(
            $order,
            'purchase_units.0.payments.captures.0.amount.value',
            data_get($order, 'purchase_units.0.amount.value', $amount),
        );

        $capturedCurrency = (string) data_get(
            $order,
            'purchase_units.0.payments.captures.0.amount.currency_code',
            data_get($order, 'purchase_units.0.amount.currency_code', $currency),
        );

        return RenewalChargeResult::success(
            externalId: $orderId,
            amount: $capturedAmount > 0 ? $capturedAmount : $amount,
            currency: strtoupper($capturedCurrency),
        );
    }
}
