<?php

namespace App\Services\Subscription;

use App\Enums\Api\V1\Payment\RegisterPaymentManager;
use App\Enums\Api\V1\SubscriptionStatus;
use App\Exceptions\Payment\PaymentVerificationException;
use App\Models\Subscription;
use App\Models\User;
use App\Services\Payment\Paypal\PaypalApiClient;
use Illuminate\Validation\ValidationException;

class SubscriptionAutoRenewToggleService
{
    public function __construct(
        private readonly PaypalApiClient $paypal,
    ) {}

    /**
     * @return array{id: string}
     */
    public function createVaultSetupToken(User $manufacturer, string $returnUrl, string $cancelUrl): array
    {
        $this->requireManageableSubscription($manufacturer);

        $token = $this->paypal->createSetupToken($returnUrl, $cancelUrl);
        $id = (string) ($token['id'] ?? '');

        if ($id === '') {
            throw new PaymentVerificationException(__('subscription.paypal_setup_token_failed'));
        }

        return ['id' => $id];
    }

    public function disable(User $manufacturer): Subscription
    {
        $subscription = $this->requireManageableSubscription($manufacturer);

        $subscription->forceFill([
            'auto_renew' => false,
        ])->save();

        return $subscription->fresh(['manufacturer', 'plan']);
    }

    /**
     * Enable auto-renew for the current plan.
     * Uses an existing vault, or exchanges a PayPal vault setup token for a new vault id.
     *
     * @param  array{vault_setup_token?: string|null, paypal_vault_id?: string|null}  $payload
     */
    public function enable(User $manufacturer, array $payload = []): Subscription
    {
        $subscription = $this->requireManageableSubscription($manufacturer);

        $vaultId = filled($subscription->paypal_vault_id)
            ? (string) $subscription->paypal_vault_id
            : null;
        $payerId = $subscription->paypal_payer_id;

        $setupToken = trim((string) ($payload['vault_setup_token'] ?? ''));
        $payloadVaultId = trim((string) ($payload['paypal_vault_id'] ?? ''));

        if ($vaultId === null && $setupToken !== '') {
            $paymentToken = $this->paypal->createPaymentTokenFromSetupToken($setupToken);
            $vaultId = $this->paypal->extractPaymentTokenId($paymentToken);
            $payerId = $this->paypal->extractPaymentTokenCustomerId($paymentToken) ?? $payerId;
        }

        if ($vaultId === null && $payloadVaultId !== '') {
            $vaultId = $payloadVaultId;
        }

        if ($vaultId === null || $vaultId === '') {
            throw ValidationException::withMessages([
                'auto_renew' => [__('subscription.auto_renew_vault_required')],
            ]);
        }

        $subscription->forceFill([
            'auto_renew' => true,
            'payment_method' => RegisterPaymentManager::PAYPAL->value,
            'paypal_vault_id' => $vaultId,
            'paypal_payer_id' => $payerId,
            'renew_attempts' => 0,
            'last_renew_attempt_at' => null,
        ])->save();

        return $subscription->fresh(['manufacturer', 'plan']);
    }

    private function requireManageableSubscription(User $manufacturer): Subscription
    {
        $subscription = $manufacturer->subscription;

        if ($subscription === null) {
            throw ValidationException::withMessages([
                'subscription' => [__('subscription.no_subscripiton_found')],
            ]);
        }

        $status = $subscription->status instanceof SubscriptionStatus
            ? $subscription->status
            : SubscriptionStatus::tryFrom((string) $subscription->status);

        if (! in_array($status, [SubscriptionStatus::ACTIVE, SubscriptionStatus::PAST_DUE, SubscriptionStatus::TRIALING], true)) {
            throw ValidationException::withMessages([
                'subscription' => [__('subscription.auto_renew_not_manageable')],
            ]);
        }

        return $subscription;
    }
}
