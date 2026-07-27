<?php

use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    return view('welcome');
});



Route::get('/test-email', function () {
    return view('mail.subscription.subscription-activated', [
        'manufacturerName' => 'Acme Manufacturing',
        'planName' => 'Professional',
        'startsAt' => now()->format('F j, Y'),
        'endsAt' => now()->addYear()->format('F j, Y'),
        'status' => 'active',
        'paidAmountDisplay' => '$299 USD',
    ]);
});

Route::get('/test-emails/subscription', function () {
    return response()->make('
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Subscription Email Previews</title>
    <style>
        body { font-family: Arial, sans-serif; padding: 28px; max-width: 900px; margin: 0 auto; background: #f8f8f8; }
        h1 { margin-bottom: 8px; }
        p { color: #555; }
        ul { padding-left: 20px; }
        li { margin: 10px 0; }
        a { color: #9A7A3A; text-decoration: none; font-weight: 600; }
        a:hover { text-decoration: underline; }
        code { background: #eee; padding: 2px 6px; border-radius: 4px; }
    </style>
</head>
<body>
    <h1>Subscription Email Previews</h1>
    <p>Open any template preview route below:</p>
    <ul>
        <li><a href="/test-emails/subscription/subscription-activated">subscription-activated</a></li>
        <li><a href="/test-emails/subscription/subscription-renewed">subscription-renewed</a></li>
        <li><a href="/test-emails/subscription/subscription-expiry-reminder">subscription-expiry-reminder</a></li>
        <li><a href="/test-emails/subscription/subscription-expired">subscription-expired</a></li>
        <li><a href="/test-emails/subscription/payment-failed">payment-failed</a></li>
    </ul>
    <p>You can also use: <code>/test-emails/subscription/{template}</code></p>
</body>
</html>
    ', 200, ['Content-Type' => 'text/html']);
});

Route::get('/test-emails/subscription/{template}', function (string $template) {
    $viewMap = [
        'subscription-activated' => 'mail.subscription.subscription-activated',
        'subscription-renewed' => 'mail.subscription.subscription-renewed',
        'subscription-expiry-reminder' => 'mail.subscription.subscription-expiry-reminder',
        'subscription-expired' => 'mail.subscription.subscription-expired',
        'payment-failed' => 'mail.subscription.payment-failed',
    ];

    if (! array_key_exists($template, $viewMap)) {
        abort(404, 'Unknown subscription email template.');
    }

    return view($viewMap[$template], [
        // Shared demo values used by different subscription templates
        'name' => 'Alex Morgan',
        'manufacturerName' => 'Acme Manufacturing',
        'recipientName' => 'Alex Morgan',
        'planName' => 'Professional Plan',
        'billingInterval' => 'monthly',
        'startsAt' => now()->subMonth()->format('F j, Y'),
        'endsAt' => now()->addMonth()->format('F j, Y'),
        'status' => 'active',
        'daysRemaining' => 7,
        'paidAmount' => '299.00',
        'paidAmountDisplay' => '$299.00 USD',
        'activatedAt' => now()->format('F j, Y'),
        'failedAt' => now()->format('F j, Y'),
        'plansUrl' => 'http://localhost:3000/plans',
        'ctaUrl' => 'http://localhost:3000/dashboard/manufacturer',
        'billingUrl' => 'http://localhost:3000/dashboard/manufacturer/subscription',
        'productsUrl' => 'http://localhost:3000/dashboard/manufacturer/products',
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