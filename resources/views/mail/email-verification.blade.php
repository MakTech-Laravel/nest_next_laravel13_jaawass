<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>{{ __('mail.email_verification.subject') }}</title>
</head>
<body>
    <p>{{ __('mail.email_verification.intro') }}</p>
    <p><strong>{{ $otp }}</strong></p>
    <p>{{ __('mail.email_verification.expires', ['time' => $expires_at->format('Y-m-d H:i:s')]) }}</p>
</body>
</html>
