<?php

use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    return view('welcome');
});

Route::get('/account-restore-email', function () {
    $ttlMinutes = (int) config('account.restore_otp_ttl_minutes', 15);

    return view('mail.account-restore-otp', [
        'otp' => '736194',
        'formattedOtp' => '736 194',
        'recipientName' => 'Sarah',
        'ttlMinutes' => $ttlMinutes,
        'expiresIn' => $ttlMinutes.' minutes',
        'ctaUrl' => \App\Support\Mail\MailNotificationHelper::frontendUrl('auth/restore-account'),
    ]);
});

Route::get('/password-reset-email', function () {
    $ttlMinutes = (int) config('account.password_reset_otp_ttl_minutes', 15);

    return view('mail.otp-security', [
        'otp' => '582047',
        'formattedOtp' => '582 047',
        'recipientName' => 'Sarah',
        'ttlMinutes' => $ttlMinutes,
        'expiresIn' => $ttlMinutes.' minutes',
        'ctaUrl' => \App\Support\Mail\MailNotificationHelper::frontendUrl('auth/restore-account'),
    ]);
});

Route::get('/welcome-email', function () {
    $receivedAt = now();

    return view('mail.admin-new-inquiry', [
        'contactName' => 'James Chen — Global Parts Co.',
        'contactSubline' => 'general · james@globalpartsco.de',
        'message' => 'Looking for M6–M20 stainless steel fasteners. Monthly bulk order ~50,000 units. Samples required before commitment. Requesting capacity and lead time info…',
        'receivedAt' => $receivedAt->format('M j · g:i A'),
        'inquiryTags' => [
            ['label' => 'Type', 'value' => 'general'],
            ['label' => 'Status', 'value' => 'New'],
        ],
        'details' => [
            'Inquiry ID' => '#INQ-'.$receivedAt->format('Ymd').'-0847',
            'Name' => 'James Chen',
            'Email' => 'james@globalpartsco.de',
            'Company' => 'Global Parts Co.',
            'Type' => 'general',
            'Received' => $receivedAt->format('F j, Y · g:i A T'),
            'Status' => 'New',
        ],
        'ctaUrl' => \App\Support\Mail\MailNotificationHelper::frontendUrl('admin/contacts/1'),
        'contactsListUrl' => \App\Support\Mail\MailNotificationHelper::frontendUrl('admin/contacts'),
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