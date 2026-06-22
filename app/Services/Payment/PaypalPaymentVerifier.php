<?php

namespace App\Services\Payment;

use App\Contracts\Payment\PaymentVerifierInterface;
use App\DTO\Payment\VerifiedPaymentDTO;
use App\Enums\Api\V1\Payment\RegisterPaymentManager;
use App\Exceptions\Payment\PaymentVerificationException;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Symfony\Component\HttpFoundation\Response;

class PaypalPaymentVerifier implements PaymentVerifierInterface
{
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

        $baseUrl = $this->apiBaseUrl();
        $accessToken = $this->accessToken($baseUrl);
        $order = $this->fetchOrder($baseUrl, $accessToken, $paymentId);
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

        return new VerifiedPaymentDTO(
            externalId: $orderId,
            amount: $amountValue,
            currency: $currency,
            status: strtolower($status),
            paymentMethod: RegisterPaymentManager::PAYPAL->value,
        );
    }

    /**
     * @return array<string, mixed>
     */
    private function fetchOrder(string $baseUrl, string $accessToken, string $paymentId): array
    {
        $orderResponse = Http::withToken($accessToken)
            ->acceptJson()
            ->get("{$baseUrl}/v2/checkout/orders/{$paymentId}");

        if ($orderResponse->successful()) {
            return $orderResponse->json();
        }

        if ($orderResponse->status() !== Response::HTTP_NOT_FOUND) {
            throw new PaymentVerificationException(
                __('subscription.paypal_verification_failed'),
                Response::HTTP_BAD_GATEWAY,
            );
        }

        $captureResponse = Http::withToken($accessToken)
            ->acceptJson()
            ->get("{$baseUrl}/v2/payments/captures/{$paymentId}");

        if (! $captureResponse->successful()) {
            throw new PaymentVerificationException(
                __('subscription.paypal_verification_failed'),
                Response::HTTP_BAD_GATEWAY,
            );
        }

        $capture = $captureResponse->json();
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

        $resolvedOrderResponse = Http::withToken($accessToken)
            ->acceptJson()
            ->get("{$baseUrl}/v2/checkout/orders/{$orderId}");

        if (! $resolvedOrderResponse->successful()) {
            throw new PaymentVerificationException(
                __('subscription.paypal_verification_failed'),
                Response::HTTP_BAD_GATEWAY,
            );
        }

        return $resolvedOrderResponse->json();
    }

    private function apiBaseUrl(): string
    {
        return config('services.paypal.mode', 'sandbox') === 'live'
            ? 'https://api-m.paypal.com'
            : 'https://api-m.sandbox.paypal.com';
    }

    private function accessToken(string $baseUrl): string
    {
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

    private function amountsMatch(float $actual, float $expected): bool
    {
        return abs(round($actual, 2) - round($expected, 2)) < 0.01;
    }
}
