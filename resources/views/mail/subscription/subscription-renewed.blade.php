@php
    $platformName = config('app.name', 'SourceNest');
    $logoUrl = public_url('images/mail/sourcenest-logo-white.png');
    $frontendUrl = rtrim((string) config('app.frontend_url', config('app.url')), '/');
    $checkSuccessIconUrl = public_url('images/mail/svg/check-success.svg');
    $globeWatermarkUrl = public_url('images/mail/svg/globe-watermark-activated.svg');
    $mailIconStyle = 'display:block;border:0;outline:none;text-decoration:none;margin:0 auto;';
    $recipientName = trim($manufacturerName ?? ($name ?? ($recipientName ?? ''))) !== ''
        ? trim($manufacturerName ?? ($name ?? ($recipientName ?? '')))
        : 'there';
    $planName = trim($planName ?? '') !== '' ? trim($planName) : 'plan';
    $startsAt = $startsAt ?? now()->format('F j, Y');
    $endsAt = $endsAt ?? '';
    $billingInterval = trim($billingInterval ?? '') !== '' ? ucfirst((string) $billingInterval) : '';
    $statusLabel = ucfirst((string) ($status ?? 'active'));
    $paidDisplay = $paidAmountDisplay
        ?? (isset($paidAmount) && $paidAmount !== null && $paidAmount !== ''
            ? (str_starts_with((string) $paidAmount, '$') ? (string) $paidAmount : '$'.ltrim((string) $paidAmount, '$').' USD')
            : null);
    $ctaUrl = $ctaUrl ?? ($plansUrl ?? \App\Support\Mail\MailNotificationHelper::frontendUrl('dashboard/manufacturer/subscription'));
    $billingUrl = $billingUrl ?? \App\Support\Mail\MailNotificationHelper::frontendUrl('dashboard/manufacturer/subscription');
    $detailRows = array_filter([
        [
            'label' => 'Plan',
            'value' => $planName,
            'bold' => true,
            'color' => null,
        ],
        $billingInterval !== '' ? [
            'label' => 'Billing interval',
            'value' => $billingInterval,
            'bold' => false,
            'color' => null,
        ] : null,
        $paidDisplay ? [
            'label' => 'Amount paid',
            'value' => $paidDisplay,
            'bold' => false,
            'color' => null,
        ] : null,
        $startsAt !== '' ? [
            'label' => 'Starts',
            'value' => $startsAt,
            'bold' => false,
            'color' => null,
        ] : null,
        $endsAt !== '' ? [
            'label' => 'Renews / ends',
            'value' => $endsAt,
            'bold' => false,
            'color' => null,
        ] : null,
        [
            'label' => 'Status',
            'value' => $statusLabel,
            'bold' => true,
            'color' => '#0A5C32',
        ],
    ]);
@endphp
<!DOCTYPE html>
<html lang="en" xmlns="http://www.w3.org/1999/xhtml" xmlns:v="urn:schemas-microsoft-com:vml"
    xmlns:o="urn:schemas-microsoft-com:office:office">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <meta name="x-apple-disable-message-reformatting">
    <meta name="format-detection" content="telephone=no,address=no,email=no,date=no,url=no">
    <meta name="color-scheme" content="light only">
    <meta name="supported-color-schemes" content="light">
    <title>Payment received — subscription continued</title>
    <!--[if mso]>
    <noscript>
        <xml>
            <o:OfficeDocumentSettings>
                <o:PixelsPerInch>96</o:PixelsPerInch>
            </o:OfficeDocumentSettings>
        </xml>
    </noscript>
    <![endif]-->
    <style type="text/css">
        :root { color-scheme: light only; supported-color-schemes: light; }
        html, body { margin: 0 !important; padding: 0 !important; width: 100% !important; -webkit-text-size-adjust: 100%; -ms-text-size-adjust: 100%; }
        img { border: 0; outline: none; text-decoration: none; -ms-interpolation-mode: bicubic; display: block; max-width: 100%; height: auto; }
        table { border-collapse: collapse; mso-table-lspace: 0pt; mso-table-rspace: 0pt; }
    </style>
</head>

<body
    style="margin:0;padding:0;background-color:#F0F0F0;font-family:Arial,Helvetica,sans-serif;-webkit-text-size-adjust:100%;-ms-text-size-adjust:100%;">
    <span
        style="display:none !important;visibility:hidden;mso-hide:all;font-size:1px;color:#F0F0F0;line-height:1px;max-height:0;max-width:0;opacity:0;overflow:hidden;">Your subscription has been renewed successfully.</span>

    <table role="presentation" width="100%" cellspacing="0" cellpadding="0" border="0"
        style="background-color:#F0F0F0;">
        <tr>
            <td align="center" style="padding:24px 12px;">
                <table role="presentation" width="600" cellspacing="0" cellpadding="0" border="0"
                    style="max-width:600px;width:100%;background-color:#FFFFFF;border-radius:14px;overflow:hidden;">

                    {{-- Header: dark brand --}}
                    <tr>
                        <td bgcolor="#3B2800" style="padding:20px 30px;background-color:#3B2800;">
                            <table role="presentation" width="100%" cellspacing="0" cellpadding="0" border="0">
                                <tr>
                                    <td align="left" valign="middle">
                                        @if (!empty($logoUrl))
                                            <img src="{{ $logoUrl }}" alt="{{ $platformName }}" width="140"
                                                style="display:block;height:auto;max-height:68px;width:auto;border:0;outline:none;text-decoration:none;">
                                        @else
                                            <div
                                                style="font-weight:900;font-size:21px;line-height:1;font-family:Arial,Helvetica,sans-serif;color:#FFFFFF;letter-spacing:-0.6px;">
                                                sourcenest</div>
                                        @endif
                                    </td>
                                    <td align="right" valign="middle">
                                        <span
                                            style="display:inline-block;padding:4px 12px;border-radius:20px;border:1.5px solid rgba(200,169,106,0.18);background-color:transparent;font-weight:700;font-size:9px;line-height:1;font-family:Arial,Helvetica,sans-serif;letter-spacing:0.4px;text-transform:uppercase;color:rgba(200,169,106,0.5);">Manufacturer Account</span>
                                    </td>
                                </tr>
                            </table>
                        </td>
                    </tr>

                    {{-- Hero --}}
                    <tr>
                        <td bgcolor="#FBF7EE" background="{{ $globeWatermarkUrl }}"
                            style="padding:34px 30px 40px;background-color:#FBF7EE;background-image:url('{{ $globeWatermarkUrl }}');background-repeat:no-repeat;background-position:right -24px top -24px;background-size:210px 210px;border-bottom:1.5px solid #E8D5A8;">
                            <table role="presentation" width="100%" cellspacing="0" cellpadding="0" border="0">
                                <tr>
                                    <td valign="top">
                                        <table role="presentation" cellspacing="0" cellpadding="0" border="0"
                                            style="margin-bottom:14px;border-collapse:separate;">
                                            <tr>
                                                <td
                                                    style="padding:4px 11px;border-radius:20px;border:1.5px solid #6ECFA0;background-color:#EAFAF2;">
                                                    <table role="presentation" cellspacing="0" cellpadding="0"
                                                        border="0">
                                                        <tr>
                                                            <td width="5" valign="middle"
                                                                style="line-height:0;font-size:0;">
                                                                <span
                                                                    style="display:block;width:5px;height:5px;border-radius:50%;background-color:#0E8A4A;">&nbsp;</span>
                                                            </td>
                                                            <td valign="middle"
                                                                style="padding-left:5px;font-weight:800;font-size:8.5px;line-height:1;font-family:Arial,Helvetica,sans-serif;letter-spacing:1.2px;text-transform:uppercase;color:#0A5C32;">
                                                                Renewed</td>
                                                        </tr>
                                                    </table>
                                                </td>
                                            </tr>
                                        </table>
                                        <table role="presentation" cellspacing="0" cellpadding="0" border="0"
                                            style="margin-bottom:13px;">
                                            <tr>
                                                <td width="20" valign="middle"
                                                    style="line-height:0;font-size:0;padding-right:8px;">
                                                    <span
                                                        style="display:block;width:20px;height:2px;border-radius:1px;background-color:#E8D5A8;">&nbsp;</span>
                                                </td>
                                                <td valign="middle"
                                                    style="font-weight:800;font-size:8.5px;line-height:1;font-family:Arial,Helvetica,sans-serif;letter-spacing:2px;text-transform:uppercase;color:#9A7A3A;">
                                                    Payment Confirmed</td>
                                            </tr>
                                        </table>
                                        <div
                                            style="font-weight:500;font-size:31px;line-height:1.17;font-family:Georgia,'Times New Roman',serif;color:#3B2800;letter-spacing:-0.2px;max-width:400px;">
                                            Payment received.<br><em style="font-style:italic;">You're still live.</em>
                                        </div>
                                        <div
                                            style="padding-top:12px;font-weight:400;font-size:13.5px;line-height:1.78;font-family:Arial,Helvetica,sans-serif;color:#666666;max-width:400px;">
                                            Your subscription has been renewed successfully. Your profile stays fully active and visible to buyers.</div>
                                    </td>
                                </tr>
                            </table>
                        </td>
                    </tr>

                    {{-- Greeting + status --}}
                    <tr>
                        <td bgcolor="#FFFFFF"
                            style="padding:28px 30px;background-color:#FFFFFF;border-bottom:1px solid #F0F0F0;">
                            <div
                                style="font-weight:500;font-size:17px;line-height:1;font-family:Georgia,'Times New Roman',serif;color:#3B2800;margin-bottom:13px;">
                                Dear {{ $recipientName }},</div>
                            <p
                                style="margin:0;font-weight:400;font-size:13.5px;line-height:1.88;font-family:Arial,Helvetica,sans-serif;color:#464646;">
                                We received your payment and your <strong style="font-weight:700;color:#1C1C1C;">{{ $planName }}</strong> subscription on {{ $platformName }} is active again. There is no interruption to your visibility or buyer inquiries.</p>

                            <table role="presentation" width="100%" cellspacing="0" cellpadding="0" border="0"
                                style="margin-top:16px;background-color:#EAFAF2;border:1.5px solid #6ECFA0;border-radius:8px;border-collapse:separate;">
                                <tr>
                                    <td style="padding:12px 15px;">
                                        <table role="presentation" cellspacing="0" cellpadding="0" border="0">
                                            <tr>
                                                <td width="8" valign="middle"
                                                    style="line-height:0;font-size:0;padding-right:10px;">
                                                    <span
                                                        style="display:block;width:7px;height:7px;border-radius:50%;background-color:#0E8A4A;">&nbsp;</span>
                                                </td>
                                                <td valign="middle"
                                                    style="font-weight:700;font-size:12.5px;line-height:1;font-family:Arial,Helvetica,sans-serif;color:#0A5C32;">
                                                    Active — Renewed — Accepting Inquiries</td>
                                            </tr>
                                        </table>
                                    </td>
                                </tr>
                            </table>
                        </td>
                    </tr>

                    {{-- Access continues --}}
                    <tr>
                        <td bgcolor="#F8F8F8"
                            style="padding:28px 30px;background-color:#F8F8F8;border-bottom:1px solid #F0F0F0;">
                            <table role="presentation" width="100%" cellspacing="0" cellpadding="0" border="0"
                                style="margin-bottom:18px;border-collapse:separate;">
                                <tr>
                                    <td width="3" bgcolor="#E8D5A8"
                                        style="width:3px;background-color:#E8D5A8;border-radius:2px;font-size:0;line-height:0;">
                                        &nbsp;</td>
                                    <td
                                        style="padding-left:9px;font-weight:500;font-size:17px;line-height:1;font-family:Georgia,'Times New Roman',serif;color:#3B2800;">
                                        Your access <em style="font-style:italic;color:#9A7A3A;">continues uninterrupted</em></td>
                                </tr>
                            </table>

                            <table role="presentation" width="100%" cellspacing="0" cellpadding="0" border="0"
                                style="border:1.5px solid #6ECFA0;border-radius:10px;border-collapse:separate;">
                                <tr>
                                    <td bgcolor="#EAFAF2"
                                        style="padding:14px 16px;background-color:#EAFAF2;border-bottom:1px solid #6ECFA0;">
                                        <table role="presentation" width="100%" cellspacing="0" cellpadding="0"
                                            border="0">
                                            <tr>
                                                <td width="32" valign="top" style="width:32px;">
                                                    <table role="presentation" cellspacing="0" cellpadding="0"
                                                        border="0" style="border-collapse:separate;">
                                                        <tr>
                                                            <td width="32" height="32" align="center"
                                                                valign="middle" bgcolor="#EAFAF2"
                                                                style="width:32px;height:32px;background-color:#EAFAF2;border:1.5px solid #6ECFA0;border-radius:8px;">
                                                                <img src="{{ $checkSuccessIconUrl }}" width="14"
                                                                    height="14" alt=""
                                                                    style="{{ $mailIconStyle }}">
                                                            </td>
                                                        </tr>
                                                    </table>
                                                </td>
                                                <td valign="top" style="padding-left:12px;">
                                                    <div
                                                        style="font-weight:500;font-size:15px;line-height:1.3;font-family:Georgia,'Times New Roman',serif;color:#0A5C32;margin-bottom:3px;">
                                                        Full Access Maintained</div>
                                                    <div
                                                        style="font-weight:500;font-size:11.5px;line-height:1.4;font-family:Arial,Helvetica,sans-serif;color:#8A8A8A;">
                                                        All platform features remain active on your account</div>
                                                </td>
                                            </tr>
                                        </table>
                                    </td>
                                </tr>
                                <tr>
                                    <td style="padding:11px 16px;border-top:1px solid #F0F0F0;">
                                        <table role="presentation" width="100%" cellspacing="0" cellpadding="0" border="0">
                                            <tr>
                                                <td width="19" valign="top" style="width:19px;line-height:0;font-size:0;padding-top:4px;">
                                                    <span style="display:block;width:7px;height:7px;border-radius:50%;background-color:#0E8A4A;">&nbsp;</span>
                                                </td>
                                                <td valign="top" style="font-weight:500;font-size:12.5px;line-height:1.4;font-family:Arial,Helvetica,sans-serif;color:#0A5C32;">
                                                    Profile remains live and visible to buyers</td>
                                            </tr>
                                        </table>
                                    </td>
                                </tr>
                                <tr>
                                    <td style="padding:11px 16px;border-top:1px solid #F0F0F0;">
                                        <table role="presentation" width="100%" cellspacing="0" cellpadding="0" border="0">
                                            <tr>
                                                <td width="19" valign="top" style="width:19px;line-height:0;font-size:0;padding-top:4px;">
                                                    <span style="display:block;width:7px;height:7px;border-radius:50%;background-color:#0E8A4A;">&nbsp;</span>
                                                </td>
                                                <td valign="top" style="font-weight:500;font-size:12.5px;line-height:1.4;font-family:Arial,Helvetica,sans-serif;color:#0A5C32;">
                                                    Product listings and inquiry inbox stay open</td>
                                            </tr>
                                        </table>
                                    </td>
                                </tr>
                                <tr>
                                    <td style="padding:11px 16px;border-top:1px solid #F0F0F0;">
                                        <table role="presentation" width="100%" cellspacing="0" cellpadding="0" border="0">
                                            <tr>
                                                <td width="19" valign="top" style="width:19px;line-height:0;font-size:0;padding-top:4px;">
                                                    <span style="display:block;width:7px;height:7px;border-radius:50%;background-color:#0E8A4A;">&nbsp;</span>
                                                </td>
                                                <td valign="top" style="font-weight:500;font-size:12.5px;line-height:1.4;font-family:Arial,Helvetica,sans-serif;color:#0A5C32;">
                                                    Dashboard, messaging, and analytics unchanged</td>
                                            </tr>
                                        </table>
                                    </td>
                                </tr>
                            </table>
                        </td>
                    </tr>

                    {{-- Renewal details --}}
                    <tr>
                        <td bgcolor="#FFFFFF"
                            style="padding:28px 30px;background-color:#FFFFFF;border-bottom:1px solid #F0F0F0;">
                            <table role="presentation" width="100%" cellspacing="0" cellpadding="0" border="0"
                                style="margin-bottom:18px;border-collapse:separate;">
                                <tr>
                                    <td width="3" bgcolor="#E8D5A8"
                                        style="width:3px;background-color:#E8D5A8;border-radius:2px;font-size:0;line-height:0;">
                                        &nbsp;</td>
                                    <td
                                        style="padding-left:9px;font-weight:500;font-size:17px;line-height:1;font-family:Georgia,'Times New Roman',serif;color:#3B2800;">
                                        Renewal <em style="font-style:italic;color:#9A7A3A;">details</em></td>
                                </tr>
                            </table>

                            <table role="presentation" width="100%" cellspacing="0" cellpadding="0" border="0"
                                style="border:1.5px solid #E6E6E6;border-radius:10px;border-collapse:separate;overflow:hidden;">
                                <tr>
                                    <td bgcolor="#F8F8F8"
                                        style="padding:11px 16px;background-color:#F8F8F8;border-bottom:1px solid #E6E6E6;">
                                        <table role="presentation" width="100%" cellspacing="0" cellpadding="0"
                                            border="0">
                                            <tr>
                                                <td
                                                    style="font-weight:900;font-size:9px;line-height:1;font-family:Arial,Helvetica,sans-serif;letter-spacing:1.2px;text-transform:uppercase;color:#8A8A8A;">
                                                    Renewal Confirmation</td>
                                                <td align="right">
                                                    <span
                                                        style="display:inline-block;padding:2px 10px;border-radius:20px;border:1.5px solid #6ECFA0;background-color:#EAFAF2;font-weight:800;font-size:9px;line-height:1;font-family:Arial,Helvetica,sans-serif;color:#0A5C32;">Paid</span>
                                                </td>
                                            </tr>
                                        </table>
                                    </td>
                                </tr>
                                @foreach ($detailRows as $row)
                                    <tr>
                                        <td style="border-top:1px solid #F0F0F0;padding:0;">
                                            <table role="presentation" width="100%" cellspacing="0" cellpadding="0"
                                                border="0">
                                                <tr>
                                                    <td width="110" bgcolor="#F8F8F8"
                                                        style="width:110px;padding:11px 16px;background-color:#F8F8F8;border-right:1px solid #F0F0F0;font-weight:700;font-size:11px;line-height:1;font-family:Arial,Helvetica,sans-serif;color:#8A8A8A;">
                                                        {{ $row['label'] }}</td>
                                                    <td
                                                        style="padding:11px 16px;font-weight:{{ !empty($row['bold']) ? '800' : '500' }};font-size:12.5px;line-height:1.3;font-family:Arial,Helvetica,sans-serif;color:{{ $row['color'] ?? '#1C1C1C' }};">
                                                        {{ $row['value'] }}</td>
                                                </tr>
                                            </table>
                                        </td>
                                    </tr>
                                @endforeach
                            </table>
                        </td>
                    </tr>

                    {{-- CTA --}}
                    <tr>
                        <td bgcolor="#FFFFFF"
                            style="padding:26px 30px 30px;background-color:#FFFFFF;border-top:1px solid #F0F0F0;">
                            <table role="presentation" cellspacing="0" cellpadding="0" border="0">
                                <tr>
                                    <td>
                                        <a href="{{ $ctaUrl }}"
                                            style="display:inline-block;padding:14px 30px;background-color:#9A7A3A;color:#FFFFFF;font-weight:900;font-size:12px;line-height:1;font-family:Arial,Helvetica,sans-serif;letter-spacing:0.6px;text-transform:uppercase;text-decoration:none;border-radius:8px;">{{ $ctaLabel ?? 'View Your Plan' }}</a>
                                    </td>
                                </tr>
                                <tr>
                                    <td style="padding-top:18px;">
                                        <table role="presentation" width="100%" cellspacing="0" cellpadding="0"
                                            border="0" style="border-top:1px solid #F0F0F0;">
                                            <tr>
                                                <td
                                                    style="padding-top:18px;font-weight:400;font-size:12px;line-height:1.75;font-family:Arial,Helvetica,sans-serif;color:#8A8A8A;">
                                                    Invoice and billing details available in your <a href="{{ $billingUrl }}" style="color:#9A7A3A;text-decoration:none;border-bottom:1px solid rgba(154,122,58,.28);">account settings</a>.
                                                </td>
                                            </tr>
                                        </table>
                                    </td>
                                </tr>
                            </table>
                        </td>
                    </tr>

                    {{-- Footer --}}
                    <tr>
                        <td bgcolor="#F8F8F8"
                            style="padding:18px 30px;background-color:#F8F8F8;border-top:1px solid #E6E6E6;">
                            <table role="presentation" width="100%" cellspacing="0" cellpadding="0" border="0">
                                <tr>
                                    <td
                                        style="font-weight:900;font-size:13px;line-height:1;font-family:Arial,Helvetica,sans-serif;color:#3B2800;letter-spacing:-0.4px;">
                                        sourcenest</td>
                                    <td align="right"
                                        style="font-weight:700;font-size:8px;line-height:1;font-family:Arial,Helvetica,sans-serif;letter-spacing:0.8px;text-transform:uppercase;color:#B4B4B4;">
                                        Global Sourcing Platform</td>
                                </tr>
                            </table>
                            <table role="presentation" width="100%" cellspacing="0" cellpadding="0" border="0"
                                style="margin:10px 0;">
                                <tr>
                                    <td height="1" bgcolor="#E6E6E6"
                                        style="height:1px;background-color:#E6E6E6;font-size:0;line-height:0;">&nbsp;
                                    </td>
                                </tr>
                            </table>
                            <span
                                style="font-weight:600;font-size:10.5px;line-height:1;font-family:Arial,Helvetica,sans-serif;color:#B4B4B4;">
                                <a href="{{ $frontendUrl }}/privacy"
                                    style="color:#B4B4B4;text-decoration:none;">Privacy</a>
                                <span style="margin:0 5px;font-size:9px;color:#E6E6E6;">·</span>
                                <a href="{{ $frontendUrl }}/terms"
                                    style="color:#B4B4B4;text-decoration:none;">Terms</a>
                            </span>
                        </td>
                    </tr>

                </table>
            </td>
        </tr>
    </table>
</body>

</html>
