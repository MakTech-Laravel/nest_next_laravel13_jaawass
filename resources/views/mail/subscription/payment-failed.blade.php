@php
    $logoUrl = public_url('images/mail/sourcenest-logo.png');
    $frontendUrl = rtrim((string) config('app.frontend_url', config('app.url')), '/');
    $footerTag = __('mail.demo.footer_tags.platform');
    $ctaUrl = $ctaUrl ?? \App\Support\Mail\MailNotificationHelper::frontendUrl('settings/billing');
    $failedAt = $failedAt ?? now()->format('F j, Y');
    $paymentSteps = collect(__('mail.payment_failed.reasons'))->map(fn ($body, $title) => ['title' => $title, 'body' => $body])->values()->all();
@endphp
<!DOCTYPE html>
<html lang="en" xmlns="http://www.w3.org/1999/xhtml" xmlns:v="urn:schemas-microsoft-com:vml" xmlns:o="urn:schemas-microsoft-com:office:office">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <meta name="x-apple-disable-message-reformatting">
    <meta name="format-detection" content="telephone=no,address=no,email=no,date=no,url=no">
    <meta name="color-scheme" content="light only">
    <meta name="supported-color-schemes" content="light">
    <title>{{ __('mail.payment_failed.subject') }}</title>
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
        body, table, td, a, span, div { font-family: Arial, Helvetica, sans-serif !important; }
    </style>
    <![endif]-->
    <style type="text/css">
        :root { color-scheme: light only; supported-color-schemes: light; }
        html, body { margin: 0 !important; padding: 0 !important; width: 100% !important; -webkit-text-size-adjust: 100%; -ms-text-size-adjust: 100%; }
        img { border: 0; outline: none; text-decoration: none; -ms-interpolation-mode: bicubic; display: block; max-width: 100%; height: auto; }
        table { border-collapse: collapse; mso-table-lspace: 0pt; mso-table-rspace: 0pt; }
    </style>
</head>
<body style="margin:0;padding:0;background-color:#C8C2B6;font-family:Arial,Helvetica,sans-serif;-webkit-text-size-adjust:100%;-ms-text-size-adjust:100%;">
    <span style="display:none !important;visibility:hidden;mso-hide:all;font-size:1px;color:#C8C2B6;line-height:1px;max-height:0;max-width:0;opacity:0;overflow:hidden;">
        {{ __('mail.payment_failed.preheader') }}
    </span>

    <table role="presentation" width="100%" cellspacing="0" cellpadding="0" border="0" style="background-color:#C8C2B6;">
        <tr>
            <td align="center" style="padding:32px 16px;">
                <table role="presentation" width="600" cellspacing="0" cellpadding="0" border="0" style="max-width:600px;width:100%;background-color:#FFFFFF;border-radius:14px;overflow:hidden;box-shadow:0 4px 16px rgba(0,0,0,0.06),0 20px 56px rgba(0,0,0,0.09);">
                    <tr>
                        <td bgcolor="#F8F8F8" style="background-color:#F8F8F8;border-bottom:1px solid #E6E6E6;padding:9px 30px;">
                            <span style="font:900 7.5px/1 Arial,Helvetica,sans-serif;letter-spacing:1.5px;text-transform:uppercase;color:#B4B4B4;">Subject</span>
                            <span style="font:500 12.5px/1 Arial,Helvetica,sans-serif;color:#2E2E2E;margin-left:10px;">{{ __('mail.payment_failed.subject') }}</span>
                        </td>
                    </tr>

                    <tr>
                        <td bgcolor="#3B2800" style="padding:20px 30px;background-color:#3B2800;">
                            <table role="presentation" width="100%" cellspacing="0" cellpadding="0" border="0">
                                <tr>
                                    <td>
                                        @if (!empty($logoUrl))
                                            <img src="{{ $logoUrl }}" alt="SourceNest" width="140" style="display:block;height:auto;max-height:36px;width:auto;border:0;outline:none;text-decoration:none;">
                                        @else
                                            <div style="font:900 21px/1 Arial,Helvetica,sans-serif;color:#FFFFFF;letter-spacing:-0.6px;">sourcenest</div>
                                        @endif
                                    </td>
                                    <td align="right">
                                        <span style="display:inline-block;font:700 9px/1 Arial,Helvetica,sans-serif;letter-spacing:0.4px;text-transform:uppercase;padding:4px 12px;border-radius:20px;color:rgba(200,169,106,0.7);background-color:transparent;border:1.5px solid rgba(200,169,106,0.25);">{{ __('mail.demo.badges.payment_failed') }}</span>
                                    </td>
                                </tr>
                            </table>
                        </td>
                    </tr>

                    <tr>
                        <td bgcolor="#F8F8F8" style="padding:26px 30px 24px;background-color:#F8F8F8;border-bottom:1.5px solid #E6E6E6;">
                            <h1 style="margin:0;font:600 24px/1.2 Georgia,'Times New Roman',serif;color:#3B2800;">{!! __('mail.payment_failed.hero_headline') !!}</h1>
                        </td>
                    </tr>

                    <tr>
                        <td bgcolor="#FFFFFF" style="padding:28px 30px 8px 30px;background-color:#FFFFFF;">
                            <p style="margin:0 0 16px 0;font:500 13px/1.65 Arial,Helvetica,sans-serif;color:#464646;">{{ __('mail.payment_failed.intro', ['name' => $name ?? 'there', 'plan' => $planName ?? '']) }}</p>

                            <table role="presentation" width="100%" cellspacing="0" cellpadding="0" border="0" style="margin:16px 0;background-color:#FEF2F2;border:1.5px solid #EEAAAA;border-radius:10px;">
                                <tr>
                                    <td style="padding:12px 16px;">
                                        <table role="presentation" width="100%" cellspacing="0" cellpadding="0" border="0">
                                            <tr>
                                                <td>
                                                    <span style="display:inline-block;width:8px;height:8px;border-radius:50%;background-color:#C42828;margin-right:8px;vertical-align:middle;"></span>
                                                    <span style="font:800 11px/1 Arial,Helvetica,sans-serif;color:#7A1818;vertical-align:middle;">{{ __('mail.payment_failed.status_label') }}</span>
                                                </td>
                                                <td align="right" style="font:600 10px/1 Arial,Helvetica,sans-serif;color:#8A8A8A;">{{ $failedAt }}</td>
                                            </tr>
                                        </table>
                                    </td>
                                </tr>
                            </table>

                            <table role="presentation" width="100%" cellspacing="0" cellpadding="0" border="0" style="margin:16px 0;background-color:#FEF2F2;border:1.5px solid #EEAAAA;border-radius:10px;">
                                <tr>
                                    <td style="padding:14px 16px;">
                                        <div style="font:500 12px/1.55 Arial,Helvetica,sans-serif;color:#464646;">{!! __('mail.payment_failed.risk_body') !!}</div>
                                    </td>
                                </tr>
                            </table>

                            <table role="presentation" width="100%" cellspacing="0" cellpadding="0" border="0" style="margin:20px 0;">
                                @foreach ($paymentSteps as $index => $step)
                                    <tr>
                                        <td width="36" style="vertical-align:top;padding-bottom:14px;">
                                            <div style="width:28px;height:28px;border-radius:50%;background-color:#3B2800;color:#FFFFFF;font:800 12px/28px Arial,Helvetica,sans-serif;text-align:center;">{{ $index + 1 }}</div>
                                        </td>
                                        <td style="vertical-align:top;padding-bottom:14px;">
                                            <div style="font:800 12px/1.3 Arial,Helvetica,sans-serif;color:#1C1C1C;">{{ $step['title'] ?? '' }}</div>
                                            @if (!empty($step['body']))
                                                <div style="font:500 12px/1.5 Arial,Helvetica,sans-serif;color:#666;margin-top:4px;">{{ $step['body'] }}</div>
                                            @endif
                                        </td>
                                    </tr>
                                @endforeach
                            </table>
                        </td>
                    </tr>

                    <tr>
                        <td bgcolor="#FFFFFF" style="padding:8px 30px 28px 30px;background-color:#FFFFFF;">
                            <table role="presentation" width="100%" cellspacing="0" cellpadding="0" border="0" style="margin-top:8px;">
                                <tr>
                                    <td align="center">
                                        <a href="{{ $ctaUrl }}" style="display:inline-block;background-color:#3B2800;color:#FFFFFF;font:800 12px/1 Arial,Helvetica,sans-serif;letter-spacing:0.3px;text-transform:uppercase;text-decoration:none;padding:14px 28px;border-radius:8px;">{{ __('mail.payment_failed.cta') }}</a>
                                    </td>
                                </tr>
                                <tr>
                                    <td align="center" style="padding-top:14px;">
                                        <a href="mailto:billing@sourcenest.com" style="font:700 11px/1 Arial,Helvetica,sans-serif;color:#9A7A3A;text-decoration:underline;">{{ __('mail.payment_failed.cta_secondary') }}</a>
                                    </td>
                                </tr>
                            </table>
                        </td>
                    </tr>

                    <tr>
                        <td bgcolor="#FFFFFF" style="padding:0 30px 24px 30px;background-color:#FFFFFF;">
                            <table role="presentation" width="100%" cellspacing="0" cellpadding="0" border="0" style="border-top:1.5px solid #F0F0F0;padding-top:18px;">
                                <tr>
                                    <td>
                                        <table role="presentation" width="100%" cellspacing="0" cellpadding="0" border="0">
                                            <tr>
                                                <td style="font:900 13px/1 Arial,Helvetica,sans-serif;color:#3B2800;letter-spacing:-0.3px;">sourcenest</td>
                                                <td align="right" style="font:700 8px/1 Arial,Helvetica,sans-serif;letter-spacing:0.8px;text-transform:uppercase;color:#9A7A3A;">{{ $footerTag }}</td>
                                            </tr>
                                        </table>
                                        <table role="presentation" width="100%" cellspacing="0" cellpadding="0" border="0" style="margin-top:12px;">
                                            <tr>
                                                <td style="font:500 11px/1.6 Arial,Helvetica,sans-serif;color:#8A8A8A;">
                                                    <a href="{{ $frontendUrl }}/privacy" style="color:#8A8A8A;text-decoration:none;">Privacy</a>
                                                    <span style="margin:0 6px;">·</span>
                                                    <a href="{{ $frontendUrl }}/terms" style="color:#8A8A8A;text-decoration:none;">Terms</a>
                                                    <span style="margin:0 6px;">·</span>
                                                    <a href="mailto:support@sourcenest.com" style="color:#8A8A8A;text-decoration:none;">Support</a>
                                                </td>
                                            </tr>
                                        </table>
                                    </td>
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
