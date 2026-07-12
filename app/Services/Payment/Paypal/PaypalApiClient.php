<?php

namespace App\Services\Payment\Paypal;

use App\Exceptions\Payment\PaymentVerificationException;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Symfony\Component\HttpFoundation\Response;

class PaypalApiClient
{
    public function apiBaseUrl(): string
    {
        return config('services.paypal.mode', 'sandbox') === 'live'
            ? 'https://api-m.paypal.com'
            : 'https://api-m.sandbox.paypal.com';
    }

    public function accessToken(): string
    {
        $baseUrl = $this->apiBaseUrl();

        return Cache::remember('paypal_access_token', 3000, function () use ($baseUrl): string {
            $clientId = (string) config('services.paypal.client_id');
            $clientSecret = (string) config('services.paypal.client_secret');

            if ($clientId === '' || $clientSecret === '') {
                throw new PaymentVerificationException(__('subscription.paypal_not_configured'));
            }

            $response = Http::asForm()
                ->withBasicAuth($clientId, $clientSecret)
                ->post("{$baseUrl}/v1/oauth2/token", [
                    'grant_type' => 'client_credentials',
                ]);

            if (! $response->successful()) {
                throw new PaymentVerificationException(
                    __('subscription.paypal_auth_failed'),
                    Response::HTTP_BAD_GATEWAY,
                );
            }

            return (string) $response->json('access_token');
        });
    }

    /**
     * @return array<string, mixed>
     */
    public function getOrder(string $orderId): array
    {
        $baseUrl = $this->apiBaseUrl();
        $response = Http::withToken($this->accessToken())
            ->acceptJson()
            ->get("{$baseUrl}/v2/checkout/orders/{$orderId}");

        if (! $response->successful()) {
            throw new PaymentVerificationException(
                __('subscription.paypal_verification_failed'),
                Response::HTTP_BAD_GATEWAY,
            );
        }

        return $response->json();
    }

    /**
     * @return array<string, mixed>|null
     */
    public function findOrder(string $orderId): ?array
    {
        $baseUrl = $this->apiBaseUrl();
        $response = Http::withToken($this->accessToken())
            ->acceptJson()
            ->get("{$baseUrl}/v2/checkout/orders/{$orderId}");

        if ($response->successful()) {
            return $response->json();
        }

        if ($response->status() === Response::HTTP_NOT_FOUND) {
            return null;
        }

        throw new PaymentVerificationException(
            __('subscription.paypal_verification_failed'),
            Response::HTTP_BAD_GATEWAY,
        );
    }

    /**
     * @return array<string, mixed>
     */
    public function getCapture(string $captureId): array
    {
        $baseUrl = $this->apiBaseUrl();
        $response = Http::withToken($this->accessToken())
            ->acceptJson()
            ->get("{$baseUrl}/v2/payments/captures/{$captureId}");

        if (! $response->successful()) {
            throw new PaymentVerificationException(
                __('subscription.paypal_verification_failed'),
                Response::HTTP_BAD_GATEWAY,
            );
        }

        return $response->json();
    }

    /**
     * Create and capture an order using a vaulted payment token.
     *
     * @return array<string, mixed>
     */
    public function createAndCaptureVaultedOrder(
        string $vaultId,
        float $amount,
        string $currency,
        string $customId,
    ): array {
        $baseUrl = $this->apiBaseUrl();
        $payload = [
            'intent' => 'CAPTURE',
            'purchase_units' => [
                [
                    'amount' => [
                        'currency_code' => strtoupper($currency),
                        'value' => number_format($amount, 2, '.', ''),
                    ],
                    'custom_id' => $customId,
                ],
            ],
            'payment_source' => [
                'token' => [
                    'id' => $vaultId,
                    'type' => 'PAYMENT_METHOD_TOKEN',
                ],
            ],
        ];

        $response = Http::withToken($this->accessToken())
            ->acceptJson()
            ->withHeaders([
                'PayPal-Request-Id' => $customId,
            ])
            ->post("{$baseUrl}/v2/checkout/orders", $payload);

        if (! $response->successful()) {
            throw new PaymentVerificationException(
                __('subscription.paypal_renew_charge_failed'),
                Response::HTTP_BAD_GATEWAY,
            );
        }

        return $response->json();
    }

    /**
     * @param  array<string, mixed>  $order
     */
    public function extractVaultId(array $order): ?string
    {
        $candidates = [
            data_get($order, 'payment_source.paypal.attributes.vault.id'),
            data_get($order, 'payment_source.card.attributes.vault.id'),
            data_get($order, 'payment_source.paypal.vault_id'),
            data_get($order, 'payment_source.token.id'),
        ];

        foreach ($candidates as $candidate) {
            if (is_string($candidate) && $candidate !== '') {
                return $candidate;
            }
        }

        return null;
    }

    /**
     * @param  array<string, mixed>  $order
     */
    public function extractPayerId(array $order): ?string
    {
        $payerId = data_get($order, 'payer.payer_id')
            ?? data_get($order, 'payment_source.paypal.account_id');

        return is_string($payerId) && $payerId !== '' ? $payerId : null;
    }
}
