@php
    $platformName = config('app.name', 'SourceNest');
    // Header sits on a light background — use the regular (non-white) logo.
    $logoUrl = public_url('images/mail/sourcenest-logo.png');
    $frontendUrl = rtrim((string) config('app.frontend_url', config('app.url')), '/');
    $siteUrl = $frontendUrl !== '' ? $frontendUrl : 'https://sourcenest.tech';
    $siteHost = parse_url($siteUrl, PHP_URL_HOST) ?: 'sourcenest.tech';
    $ticketNumber = $ticketNumber ?? $referenceId ?? '';
    $ticketSubject = $ticketSubject ?? $subject ?? '';
    $ctaUrl = $ctaUrl ?? \App\Support\Mail\MailNotificationHelper::frontendUrl('dashboard/buyer/support-tickets');
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
    <title>Thank you for reaching out{{ $ticketNumber !== '' ? ' — '.$ticketNumber : '' }}</title>
    <!--[if mso]>
    <noscript>
        <xml>
            <o:OfficeDocumentSettings>
                <o:AllowPNG/>
                <o:PixelsPerInch>96</o:PixelsPerInch>
            </o:OfficeDocumentSettings>
        </xml>
    </noscript>
    <style type="text/css">
        body, table, td, a, span, div, p { font-family: Arial, Helvetica, sans-serif !important; }
    </style>
    <![endif]-->
    <style type="text/css">
        :root {
            color-scheme: light only;
            supported-color-schemes: light;
        }
        html, body { margin: 0 !important; padding: 0 !important; width: 100% !important; -webkit-text-size-adjust: 100%; -ms-text-size-adjust: 100%; }
        img { border: 0; outline: none; text-decoration: none; -ms-interpolation-mode: bicubic; display: block; max-width: 100%; height: auto; }
        table { border-collapse: collapse; mso-table-lspace: 0pt; mso-table-rspace: 0pt; }
        /* Force light palette even when the device/OS is in dark mode */
        @media (prefers-color-scheme: dark) {
            .email-bg-main { background-color: #FFFFFF !important; }
            .email-card { background-color: #FFFFFF !important; }
            .email-title { color: #1A1A1A !important; }
            .email-accent { color: #9A7A3A !important; }
            .email-body-text { color: #555555 !important; }
            .email-strong { color: #1A1A1A !important; }
            .email-muted { color: #888888 !important; }
            .email-link { color: #3B2800 !important; }
            .email-bar { background-color: #C8A96A !important; }
            .email-rule { background-color: #EEEEEE !important; }
        }
        @media only screen and (max-width: 640px) {
            .email-outer { padding: 24px 16px !important; }
            .email-card { max-width: 100% !important; width: 100% !important; }
            .email-pad { padding-left: 0 !important; padding-right: 0 !important; }
            .email-logo { width: 180px !important; max-width: 180px !important; }
            .email-title { font-size: 22px !important; line-height: 1.25 !important; }
            .email-body-text { font-size: 13px !important; line-height: 1.75 !important; }
            .email-muted { font-size: 12px !important; }
        }
    </style>
</head>

<body class="email-bg-main" style="margin:0;padding:0;background-color:#FFFFFF;font-family:Arial,Helvetica,sans-serif;-webkit-text-size-adjust:100%;-ms-text-size-adjust:100%;">
    <div style="display:none;font-size:1px;line-height:1px;max-height:0;max-width:0;opacity:0;overflow:hidden;mso-hide:all;">
        Thank you for reaching out. We have received your message{{ $ticketNumber !== '' ? ' on ticket #'.$ticketNumber : '' }}.&nbsp;&zwnj;&nbsp;&zwnj;&nbsp;&zwnj;&nbsp;&zwnj;&nbsp;&zwnj;&nbsp;&zwnj;&nbsp;&zwnj;&nbsp;&zwnj;&nbsp;&zwnj;&nbsp;&zwnj;
    </div>

    <table role="presentation" width="100%" cellspacing="0" cellpadding="0" border="0" class="email-bg-main" style="background-color:#FFFFFF;">
        <tr>
            <td align="center" class="email-outer" style="padding:40px 20px;">
                <table role="presentation" width="560" cellspacing="0" cellpadding="0" border="0" class="email-card"
                    style="max-width:560px;width:100%;background-color:#FFFFFF;font-family:Arial,Helvetica,sans-serif;">

                    {{-- Top gold bar --}}
                    <tr>
                        <td style="padding-bottom:32px;line-height:0;font-size:0;">
                            <table role="presentation" width="100%" cellspacing="0" cellpadding="0" border="0">
                                <tr>
                                    <td class="email-bar" style="height:2px;background-color:#C8A96A;font-size:0;line-height:0;">&nbsp;</td>
                                </tr>
                            </table>
                        </td>
                    </tr>

                    {{-- Logo (regular — light background) --}}
                    <tr>
                        <td style="padding-bottom:32px;">
                            @if (! empty($logoUrl))
                                <a href="{{ $siteUrl }}?source=email" target="_blank" style="text-decoration:none;display:inline-block;">
                                    <img class="email-logo" src="{{ $logoUrl }}" alt="{{ $platformName }}" width="240"
                                        style="display:block;width:240px;max-width:240px;height:auto;border:0;outline:none;">
                                </a>
                            @else
                                <span style="font-weight:900;font-size:22px;line-height:1;font-family:Arial,Helvetica,sans-serif;color:#3B2800;">sourcenest</span>
                            @endif
                        </td>
                    </tr>

                    {{-- Title --}}
                    <tr>
                        <td style="padding-bottom:10px;">
                            <p class="email-title" style="margin:0;font-family:Georgia,'Times New Roman',serif;font-size:26px;font-weight:normal;color:#1A1A1A;line-height:1.2;letter-spacing:-0.3px;">
                                Thank you for<br>
                                <span class="email-accent" style="color:#9A7A3A;font-style:italic;">reaching out.</span>
                            </p>
                        </td>
                    </tr>

                    <tr>
                        <td style="padding-bottom:24px;font-size:0;line-height:0;">&nbsp;</td>
                    </tr>

                    {{-- Body --}}
                    <tr>
                        <td style="padding-bottom:16px;">
                            <p class="email-body-text" style="margin:0;font-family:Arial,Helvetica,sans-serif;font-size:14px;color:#555555;line-height:1.8;">
                                We have received your message@if ($ticketNumber !== '')
                                    on ticket <strong class="email-strong" style="color:#1A1A1A;font-weight:700;">{{ $ticketNumber }}</strong>
                                @endif
                                and appreciate you getting in touch. Someone from our team will review your inquiry and respond within <span class="email-strong" style="color:#1A1A1A;font-weight:700;">1 business day.</span>
                            </p>
                        </td>
                    </tr>

                    @if ($ticketSubject !== '')
                        <tr>
                            <td style="padding-bottom:16px;">
                                <p class="email-body-text" style="margin:0;font-family:Arial,Helvetica,sans-serif;font-size:14px;color:#555555;line-height:1.8;">
                                    Subject: <span class="email-strong" style="color:#1A1A1A;font-weight:700;">{{ $ticketSubject }}</span>
                                </p>
                            </td>
                        </tr>
                    @endif

                    <tr>
                        <td style="padding-bottom:32px;">
                            <p class="email-body-text" style="margin:0;font-family:Arial,Helvetica,sans-serif;font-size:14px;color:#555555;line-height:1.8;">
                                In the meantime, feel free to explore SourceNest — a platform built for buyers and manufacturers to connect directly, without agents or commission fees.
                            </p>
                        </td>
                    </tr>

                    {{-- Divider --}}
                    <tr>
                        <td style="padding-bottom:28px;line-height:0;font-size:0;">
                            <table role="presentation" width="100%" cellspacing="0" cellpadding="0" border="0">
                                <tr>
                                    <td class="email-rule" style="height:1px;background-color:#EEEEEE;font-size:0;line-height:0;">&nbsp;</td>
                                </tr>
                            </table>
                        </td>
                    </tr>

                    {{-- Visit link --}}
                    <tr>
                        <td style="padding-bottom:32px;">
                            <p class="email-muted" style="margin:0;font-family:Arial,Helvetica,sans-serif;font-size:13px;color:#888888;">
                                Visit us at&nbsp;<a class="email-link" href="{{ $siteUrl }}?source=email" style="color:#3B2800;text-decoration:none;font-weight:700;border-bottom:1px solid #C8A96A;">{{ $siteHost }}</a>
                                @if (! empty($ctaUrl))
                                    &nbsp;·&nbsp;<a class="email-link" href="{{ $ctaUrl }}" style="color:#3B2800;text-decoration:none;font-weight:700;border-bottom:1px solid #C8A96A;">View ticket</a>
                                @endif
                            </p>
                        </td>
                    </tr>

                    {{-- Bottom gold bar --}}
                    <tr>
                        <td style="line-height:0;font-size:0;">
                            <table role="presentation" width="100%" cellspacing="0" cellpadding="0" border="0">
                                <tr>
                                    <td class="email-bar" style="height:2px;background-color:#C8A96A;font-size:0;line-height:0;">&nbsp;</td>
                                </tr>
                            </table>
                        </td>
                    </tr>

                </table>
            </td>
        </tr>
    </table>
</body>

</html>
