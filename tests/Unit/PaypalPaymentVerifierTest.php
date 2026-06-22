<?php

use App\Services\Payment\PaypalPaymentVerifier;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

uses(TestCase::class);

beforeEach(function (): void {
    Cache::forget('paypal_access_token');

    config([
        'services.paypal.client_id' => 'test-client',
        'services.paypal.client_secret' => 'test-secret',
        'services.paypal.mode' => 'sandbox',
    ]);
});

test('paypal verifier accepts checkout order ids', function (): void {
    Http::fake([
        'https://api-m.sandbox.paypal.com/v1/oauth2/token' => Http::response([
            'access_token' => 'test-access-token',
            'token_type' => 'Bearer',
        ]),
        'https://api-m.sandbox.paypal.com/v2/checkout/orders/ORDER-TEST-123' => Http::response([
            'id' => 'ORDER-TEST-123',
            'status' => 'COMPLETED',
            'purchase_units' => [
                [
                    'amount' => [
                        'value' => '99.00',
                        'currency_code' => 'USD',
                    ],
                ],
            ],
        ]),
    ]);

    $verified = app(PaypalPaymentVerifier::class)->verify([
        'payment_id' => 'ORDER-TEST-123',
        'paid_amount' => 99.00,
    ]);

    expect($verified->externalId)->toBe('ORDER-TEST-123')
        ->and($verified->amount)->toBe(99.0)
        ->and($verified->status)->toBe('completed');
});

test('paypal verifier resolves capture ids to their parent order', function (): void {
    Http::fake([
        'https://api-m.sandbox.paypal.com/v1/oauth2/token' => Http::response([
            'access_token' => 'test-access-token',
            'token_type' => 'Bearer',
        ]),
        'https://api-m.sandbox.paypal.com/v2/checkout/orders/CAPTURE-TEST-123' => Http::response([], 404),
        'https://api-m.sandbox.paypal.com/v2/payments/captures/CAPTURE-TEST-123' => Http::response([
            'id' => 'CAPTURE-TEST-123',
            'status' => 'COMPLETED',
            'supplementary_data' => [
                'related_ids' => [
                    'order_id' => 'CAPTURE-ONLY-ORDER',
                ],
            ],
        ]),
        'https://api-m.sandbox.paypal.com/v2/checkout/orders/CAPTURE-ONLY-ORDER' => Http::response([
            'id' => 'CAPTURE-ONLY-ORDER',
            'status' => 'COMPLETED',
            'purchase_units' => [
                [
                    'amount' => [
                        'value' => '2990.00',
                        'currency_code' => 'USD',
                    ],
                ],
            ],
        ]),
    ]);

    $verified = app(PaypalPaymentVerifier::class)->verify([
        'payment_id' => 'CAPTURE-TEST-123',
        'paid_amount' => 2990.00,
    ]);

    expect($verified->externalId)->toBe('CAPTURE-ONLY-ORDER')
        ->and($verified->amount)->toBe(2990.0)
        ->and($verified->status)->toBe('completed');
});
