<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>{{ $platform_name ?? config('app.name') }} — Test Email</title>
</head>
<body style="font-family: Arial, sans-serif; line-height: 1.6; color: #333;">
    <p>Hello {{ $recipient_name ?? 'Admin' }},</p>
    <p>This is a test email from <strong>{{ $platform_name ?? config('app.name') }}</strong>.</p>
    <p>Configured sender: {{ $from_name ?? config('mail.from.name') }} &lt;{{ $from_email ?? config('mail.from.address') }}&gt;</p>
    <p>If you received this message, your email delivery settings are working.</p>
</body>
</html>
