<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>{{ __('mail.subscription_expired.subject') }}</title>
</head>

<body style="margin:0;padding:0;background-color:#f4f4f5;font-family:system-ui,-apple-system,BlinkMacSystemFont,'Segoe UI',sans-serif;">
    <span style="display:none !important;visibility:hidden;font-size:1px;color:#f4f4f5;line-height:1px;max-height:0;max-width:0;opacity:0;overflow:hidden;">
        {{ __('mail.subscription_expired.preheader') }}
    </span>
    <table role="presentation" width="100%" cellspacing="0" cellpadding="0" border="0" style="background-color:#f4f4f5;">
        <tr>
            <td align="center" style="padding:32px 16px;">
                <table role="presentation" width="600" cellspacing="0" cellpadding="0" border="0" style="max-width:600px;width:100%;background-color:#ffffff;border:1px solid #e5e7eb;border-radius:12px;overflow:hidden;">
                    <tr>
                        <td style="padding:28px 28px 12px 28px;background-color:#111827;">
                            <p style="margin:0 0 8px 0;font-size:11px;font-weight:700;letter-spacing:0.25em;color:#94a3b8;text-transform:uppercase;">SourceNest</p>
                            <h1 style="margin:0;font-size:24px;line-height:1.3;font-weight:600;color:#f8fafc;">
                                {{ __('mail.subscription_expired.subject') }}
                            </h1>
                        </td>
                    </tr>
                    <tr>
                        <td style="padding:28px;">
                            <p style="margin:0 0 16px 0;font-size:15px;line-height:1.6;color:#111827;">
                                {{ __('mail.subscription_expired.greeting', ['name' => $manufacturerName]) }}
                            </p>
                            <p style="margin:0 0 16px 0;font-size:15px;line-height:1.6;color:#374151;">
                                {{ __('mail.subscription_expired.intro', ['plan' => $planName, 'date' => $endedAt]) }}
                            </p>
                            <p style="margin:0 0 24px 0;font-size:15px;line-height:1.6;color:#374151;">
                                {{ __('mail.subscription_expired.body') }}
                            </p>
                            <table role="presentation" cellspacing="0" cellpadding="0" border="0" style="margin:0 0 24px 0;">
                                <tr>
                                    <td style="border-radius:8px;background-color:#1d4ed8;">
                                        <a href="{{ $plansUrl }}" style="display:inline-block;padding:14px 24px;font-size:15px;font-weight:600;color:#ffffff;text-decoration:none;">
                                            {{ __('mail.subscription_expired.cta') }}
                                        </a>
                                    </td>
                                </tr>
                            </table>
                        </td>
                    </tr>
                    <tr>
                        <td style="padding:20px 28px;background-color:#f9fafb;border-top:1px solid #e5e7eb;">
                            <p style="margin:0;font-size:12px;line-height:1.6;color:#6b7280;text-align:center;">
                                {{ __('mail.subscription_expired.footer') }}
                            </p>
                        </td>
                    </tr>
                </table>
            </td>
        </tr>
    </table>
</body>

</html>
