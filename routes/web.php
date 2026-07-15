<?php

use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    return view('welcome');
});




Route::get('/test-email', function () {
    $role = request()->query('role', 'buyer');
    $template = request()->query('template', 'manufacturer-order-created');
    $orderId = 42;

    $ctaUrl = match ($role) {
        'manufacturer' => \App\Support\Mail\MailNotificationHelper::manufacturerOrderUrl($orderId),
        'admin' => \App\Support\Mail\MailNotificationHelper::adminOrderUrl($orderId),
        default => \App\Support\Mail\MailNotificationHelper::buyerOrderUrl($orderId),
    };

    $shared = [
        'recipientRole' => $role,
        'recipientName' => 'Alex',
        'orderId' => $orderId,
        'orderNumber' => 'ORD-00042',
        'buyerName' => 'Alex Buyer',
        'manufacturerName' => 'Acme Ceramics',
        'ctaUrl' => $ctaUrl,
    ];

    if ($template === 'order-in-production') {
        return view(
            $role === 'manufacturer'
                ? 'mail.order-status.order-in-production-manufacturer'
                : 'mail.order-status.order-in-production-buyer',
            $shared,
        );
    }

    if ($template === 'order-ready-for-shipment') {
        return view(
            $role === 'manufacturer'
                ? 'mail.order-status.order-ready-for-shipment-manufacturer'
                : 'mail.order-status.order-ready-for-shipment-buyer',
            $shared,
        );
    }

    if ($template === 'order-shipped') {
        return view(
            $role === 'manufacturer'
                ? 'mail.order-status.order-shipped-manufacturer'
                : 'mail.order-status.order-shipped-buyer',
            $shared,
        );
    }

    if ($template === 'order-completed') {
        $view = match ($role) {
            'manufacturer' => 'mail.order-status.order-completed-manufacturer',
            'admin' => 'mail.order-status.order-completed-admin',
            default => 'mail.order-status.order-completed-buyer',
        };

        return view($view, $shared);
    }

    if ($template === 'order-cancelled') {
        $view = match ($role) {
            'manufacturer' => 'mail.order-status.order-cancelled-manufacturer',
            'admin' => 'mail.order-status.order-cancelled-admin',
            default => 'mail.order-status.order-cancelled-buyer',
        };

        return view($view, $shared);
    }

    if ($template === 'order-review-invite') {
        return view('mail.order-review-invite', [
            ...$shared,
            'manufacturerName' => 'Acme Ceramics',
            'productName' => 'Premium ceramic mugs - 350ml',
            'productId' => 12,
            'ctaUrl' => \App\Support\Mail\MailNotificationHelper::productUrl(12, [
                'review' => 'true',
                'order' => 'ORD-00042',
            ]),
        ]);
    }

    if ($template === 'review-approved') {
        return view('mail.review-approved', [
            ...$shared,
            'productName' => 'Premium ceramic mugs - 350ml',
            'productId' => 12,
            'manufacturerName' => 'Acme Ceramics',
            'rating' => 5,
            'reviewTitle' => 'Excellent quality and communication',
            'ctaUrl' => \App\Support\Mail\MailNotificationHelper::productReviewsUrl(12),
        ]);
    }

    if ($template === 'new-product-review') {
        return view('mail.new-product-review', [
            ...$shared,
            'productName' => 'Premium ceramic mugs - 350ml',
            'productId' => 12,
            'buyerName' => 'Alex Buyer',
            'buyerCompany' => 'Alex Trading Co',
            'buyerCountry' => 'USA',
            'buyerInitials' => 'AB',
            'rating' => 5,
            'reviewTitle' => 'Excellent quality and communication',
            'reviewBody' => 'The manufacturer delivered exactly what we ordered and kept us updated throughout production. Highly recommended.',
            'reviewDate' => 'Jul 14, 2026',
            'ctaUrl' => \App\Support\Mail\MailNotificationHelper::productReviewsUrl(12),
        ]);
    }

    return view('mail.manufacturer-order-created', [
        ...$shared,
        'orderTitle' => 'Premium ceramic mugs - 350ml',
        'totalAmount' => '12,500.50',
        'currencyCode' => 'USD',
        'estimatedDeliveryAt' => 'August 15, 2026',
        'productionLead' => '30 days',
        'paymentTerms' => '50% upfront, 50% on delivery',
        'shippingTerms' => 'FOB Shanghai',
        'destination' => 'Los Angeles, USA',
        'items' => [
            [
                'productName' => 'Ceramic Mug 350ml',
                'quantity' => 5000,
                'quantityUnit' => 'pieces',
                'unitPrice' => '2.50',
                'lineTotal' => '12,500.00',
            ],
        ],
    ]);
});


Route::get('/oauth/token-capture', function () {
    return response()->make('
<!DOCTYPE html>
<html>
<head>
    <title>Token Captured</title>
    <style>
        body { font-family: Arial, sans-serif; padding: 30px; max-width: 700px; margin: 0 auto; }
        h2 { color: #2e7d32; }
        textarea {
            width: 100%; height: 80px; font-family: monospace; font-size: 12px;
            padding: 10px; box-sizing: border-box; border: 2px solid #4285f4; border-radius: 4px;
        }
        button {
            background: #4285f4; color: white; border: none;
            padding: 10px 20px; border-radius: 4px; cursor: pointer; margin-top: 10px;
        }
        .note { background: #fff8e1; padding: 12px; border-radius: 4px; margin-top: 15px; font-size: 13px; }
    </style>
</head>
<body>
<h2>✅ Google Token Captured</h2>
<p>Copy the access token below and paste it into Postman.</p>
<textarea id="token" readonly placeholder="Reading token..."></textarea>
<br>
<button onclick="copy()">📋 Copy Token</button>
<div class="note">⚠️ Token expires in ~1 hour. This page is for local testing only.</div>
<script>
    // Token is in the URL hash — never sent to server
    const hash   = window.location.hash.substring(1);
    const params = new URLSearchParams(hash);
    const token  = params.get("access_token");
    if (token) {
        document.getElementById("token").value = token;
    } else {
        document.getElementById("token").value = "No token found. Hash: " + hash;
    }
    function copy() {
        const t = document.getElementById("token");
        t.select();
        document.execCommand("copy");
        alert("Copied!");
    }
</script>
</body>
</html>
    ', 200, ['Content-Type' => 'text/html']);
});