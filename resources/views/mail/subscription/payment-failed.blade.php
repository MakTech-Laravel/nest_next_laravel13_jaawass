@php
    $logoUrl = public_url('images/mail/sourcenest-logo.png');
    $frontendUrl = rtrim((string) config('app.frontend_url', config('app.url')), '/');
    $ctaUrl = $ctaUrl ?? \App\Support\Mail\MailNotificationHelper::frontendUrl('dashboard/manufacturer/subscription');
    $failedAt = $failedAt ?? now()->format('F j, Y');
    $name = trim($name ?? ($manufacturerName ?? '')) !== '' ? trim($name ?? ($manufacturerName ?? '')) : 'there';
    $planName = trim($planName ?? '') !== '' ? trim($planName) : __('subscription.plan');
    $reasons = [
        [
            'title' => __('mail.payment_failed.reason_1_title'),
            'body' => __('mail.payment_failed.reason_1_body'),
        ],
        [
            'title' => __('mail.payment_failed.reason_2_title'),
            'body' => __('mail.payment_failed.reason_2_body'),
        ],
        [
            'title' => __('mail.payment_failed.reason_3_title'),
            'body' => __('mail.payment_failed.reason_3_body'),
        ],
    ];
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
        html, body {
            margin: 0 !important;
            padding: 0 !important;
            width: 100% !important;
            -webkit-text-size-adjust: 100%;
            -ms-text-size-adjust: 100%;
            background-color: #F0F0F0 !important;
            color: #1C1C1C !important;
        }
        img { border: 0; outline: none; text-decoration: none; -ms-interpolation-mode: bicubic; display: block; max-width: 100%; height: auto; }
        table { border-collapse: collapse; mso-table-lspace: 0pt; mso-table-rspace: 0pt; }
        a { text-decoration: none; }
        /* Force light theme in clients that try dark mode */
        u + .body, .body { background-color: #F0F0F0 !important; }
        [data-ogsc] body,
        [data-ogsb] body {
            background-color: #F0F0F0 !important;
            color: #1C1C1C !important;
        }
        @media (prefers-color-scheme: dark) {
            body, .email-body, .email-bg {
                background-color: #F0F0F0 !important;
                color: #1C1C1C !important;
            }
            .email-card,
            .email-cell-white { background-color: #FFFFFF !important; color: #1C1C1C !important; }
            .email-cell-gray { background-color: #F8F8F8 !important; color: #1C1C1C !important; }
            .email-text { color: #464646 !important; }
            .email-text-strong { color: #1C1C1C !important; }
            .email-heading { color: #3B2800 !important; }
            .email-muted { color: #8A8A8A !important; }
        }
        @media only screen and (max-width: 600px) {
            .email-pad { padding-left: 18px !important; padding-right: 18px !important; }
            .email-ht { font-size: 25px !important; line-height: 1.2 !important; }
            .email-shell { width: 100% !important; }
            .email-status-dt { display: block !important; width: 100% !important; padding-top: 8px !important; text-align: left !important; }
        }
    </style>
</head>
<body class="body email-body" bgcolor="#F0F0F0" style="margin:0;padding:0;background-color:#F0F0F0 !important;color:#1C1C1C;font-family:Arial,Helvetica,sans-serif;-webkit-text-size-adjust:100%;-ms-text-size-adjust:100%;">
    <span style="display:none !important;visibility:hidden;mso-hide:all;font-size:1px;color:#F0F0F0;line-height:1px;max-height:0;max-width:0;opacity:0;overflow:hidden;">
        {{ __('mail.payment_failed.preheader') }}
    </span>

    <table role="presentation" class="email-bg" width="100%" cellspacing="0" cellpadding="0" border="0" bgcolor="#F0F0F0" style="background-color:#F0F0F0 !important;">
        <tr>
            <td align="center" style="padding:24px 12px;">

                {{-- Outer card: border-collapse:separate required for border-radius in email --}}
                <table role="presentation" class="email-shell email-card" width="600" cellspacing="0" cellpadding="0" border="0" bgcolor="#FFFFFF" style="max-width:600px;width:100%;background-color:#FFFFFF !important;border:1px solid #E6E6E6;border-radius:14px;border-collapse:separate !important;overflow:hidden;">

                    {{-- Header (hd-a) — top corners --}}
                    <tr>
                        <td class="email-pad email-cell-white" bgcolor="#FFFFFF" style="background-color:#FFFFFF !important;padding:20px 30px;border-bottom:1px solid #F0F0F0;border-radius:14px 14px 0 0;">
                            <table role="presentation" width="100%" cellspacing="0" cellpadding="0" border="0">
                                <tr>
                                    <td style="vertical-align:middle;">
                                        @if (!empty($logoUrl))
                                            <img src="{{ $logoUrl }}" alt="SourceNest" width="140" style="display:block;height:auto;max-height:36px;width:auto;border:0;outline:none;text-decoration:none;">
                                        @else
                                            <div style="font:900 21px/1 Arial,Helvetica,sans-serif;color:#3B2800;letter-spacing:-0.6px;">sourcenest</div>
                                        @endif
                                    </td>
                                    <td align="right" style="vertical-align:middle;">
                                        <span style="display:inline-block;font:700 9px/1 Arial,Helvetica,sans-serif;letter-spacing:0.4px;text-transform:uppercase;padding:4px 12px;border-radius:20px;color:#C42828;background-color:#FEF2F2;border:1px solid #EEAAAA;">{{ __('mail.payment_failed.badge') }}</span>
                                    </td>
                                </tr>
                            </table>
                        </td>
                    </tr>

                    {{-- Hero (h3) --}}
                    <tr>
                        <td class="email-pad email-cell-gray" bgcolor="#F8F8F8" style="background-color:#F8F8F8 !important;padding:26px 30px 24px;border-bottom:1px solid #E6E6E6;">
                            <table role="presentation" cellspacing="0" cellpadding="0" border="0" style="margin:0 0 14px 0;border-collapse:separate !important;">
                                <tr>
                                    <td bgcolor="#FEF2F2" style="background-color:#FEF2F2;border:1px solid #EEAAAA;border-radius:20px;padding:4px 11px;">
                                        <span style="display:inline-block;width:5px;height:5px;border-radius:50%;background-color:#C42828;vertical-align:middle;margin-right:5px;"></span>
                                        <span style="font:800 8.5px/1 Arial,Helvetica,sans-serif;letter-spacing:1.2px;text-transform:uppercase;color:#7A1818;vertical-align:middle;">{{ __('mail.payment_failed.pill') }}</span>
                                    </td>
                                </tr>
                            </table>
                            <div class="email-ht email-text-strong" style="font:500 25px/1.17 Georgia,'Times New Roman',serif;color:#1C1C1C !important;letter-spacing:-0.2px;">
                                {!! __('mail.payment_failed.hero_headline') !!}
                            </div>
                            <div class="email-muted" style="margin-top:12px;font:400 13px/1.78 Arial,Helvetica,sans-serif;color:#666666 !important;">
                                {{ __('mail.payment_failed.hero_subheadline') }}
                            </div>
                        </td>
                    </tr>

                    {{-- Body section --}}
                    <tr>
                        <td class="email-pad email-cell-white" bgcolor="#FFFFFF" style="background-color:#FFFFFF !important;padding:28px 30px;border-bottom:1px solid #F0F0F0;">
                            <div class="email-heading" style="font:500 17px/1 Georgia,'Times New Roman',serif;color:#3B2800 !important;margin:0 0 13px 0;">{{ __('mail.payment_failed.greeting', ['name' => $name]) }}</div>
                            <p class="email-text" style="margin:0 0 13px 0;font:400 13.5px/1.88 Arial,Helvetica,sans-serif;color:#464646 !important;">
                                {!! __('mail.payment_failed.intro_body', ['plan' => e($planName)]) !!}
                            </p>

                            {{-- Status — separate collapse for radius --}}
                            <table role="presentation" width="100%" cellspacing="0" cellpadding="0" border="0" bgcolor="#FEF2F2" style="margin-top:16px;background-color:#FEF2F2;border:1px solid #EEAAAA;border-radius:8px;border-collapse:separate !important;">
                                <tr>
                                    <td style="padding:12px 15px;border-radius:8px;">
                                        <table role="presentation" width="100%" cellspacing="0" cellpadding="0" border="0">
                                            <tr>
                                                <td style="vertical-align:middle;">
                                                    <span style="display:inline-block;width:7px;height:7px;border-radius:50%;background-color:#C42828;margin-right:8px;vertical-align:middle;"></span>
                                                    <span class="email-text-strong" style="font:700 12.5px/1 Arial,Helvetica,sans-serif;color:#1C1C1C !important;vertical-align:middle;">{{ __('mail.payment_failed.status_chip') }}</span>
                                                </td>
                                                <td class="email-status-dt email-muted" align="right" style="vertical-align:middle;font:500 11px/1 Arial,Helvetica,sans-serif;color:#8A8A8A !important;white-space:nowrap;">{{ $failedAt }}</td>
                                            </tr>
                                        </table>
                                    </td>
                                </tr>
                            </table>

                            {{-- Alert — separate collapse for radius --}}
                            <table role="presentation" width="100%" cellspacing="0" cellpadding="0" border="0" bgcolor="#FEF2F2" style="margin-top:14px;background-color:#FEF2F2;border:1px solid #EEAAAA;border-left:4px solid #7A1818;border-radius:8px;border-collapse:separate !important;">
                                <tr>
                                    <td style="padding:14px 16px;border-radius:8px;">
                                        <div style="font:900 8.5px/1 Arial,Helvetica,sans-serif;letter-spacing:1.6px;text-transform:uppercase;color:#7A1818;margin:0 0 5px 0;">{{ __('mail.payment_failed.alert_heading') }}</div>
                                        <div class="email-text" style="font:400 13px/1.65 Arial,Helvetica,sans-serif;color:#464646 !important;">
                                            {!! __('mail.payment_failed.alert_body') !!}
                                        </div>
                                    </td>
                                </tr>
                            </table>
                        </td>
                    </tr>

                    {{-- Reasons --}}
                    <tr>
                        <td class="email-pad email-cell-gray" bgcolor="#F8F8F8" style="background-color:#F8F8F8 !important;padding:28px 30px;border-bottom:1px solid #F0F0F0;">
                            <table role="presentation" cellspacing="0" cellpadding="0" border="0" style="margin:0 0 18px 0;">
                                <tr>
                                    <td style="width:3px;height:18px;background-color:#E8D5A8;border-radius:2px;font-size:0;line-height:0;">&nbsp;</td>
                                    <td class="email-heading" style="padding-left:9px;font:500 17px/1 Georgia,'Times New Roman',serif;color:#3B2800 !important;">
                                        {!! __('mail.payment_failed.reasons_section_title') !!}
                                    </td>
                                </tr>
                            </table>

                            @foreach ($reasons as $index => $reason)
                                <table role="presentation" width="100%" cellspacing="0" cellpadding="0" border="0" @if (! $loop->last) style="border-bottom:1px solid #F0F0F0;" @endif>
                                    <tr>
                                        <td width="40" valign="top" style="padding:15px 0;vertical-align:top;">
                                            <div style="width:26px;height:26px;line-height:26px;background-color:#FBF7EE;border:1px solid #E8D5A8;border-radius:13px;font:900 11px/26px Arial,Helvetica,sans-serif;color:#9A7A3A;text-align:center;">–</div>
                                        </td>
                                        <td valign="top" style="padding:15px 0;vertical-align:top;">
                                            <div class="email-text-strong" style="font:700 13.5px/1 Arial,Helvetica,sans-serif;color:#1C1C1C !important;margin:0 0 3px 0;">{{ $reason['title'] }}</div>
                                            <div class="email-muted" style="font:400 12.5px/1.55 Arial,Helvetica,sans-serif;color:#8A8A8A !important;">{{ $reason['body'] }}</div>
                                        </td>
                                    </tr>
                                </table>
                            @endforeach
                        </td>
                    </tr>

                    {{-- CTA --}}
                    <tr>
                        <td class="email-pad email-cell-white" bgcolor="#FFFFFF" style="background-color:#FFFFFF !important;padding:26px 30px 30px;border-top:1px solid #F0F0F0;">
                            <table role="presentation" width="100%" cellspacing="0" cellpadding="0" border="0" style="border-collapse:separate !important;">
                                <tr>
                                    <td>
                                        <!--[if mso]>
                                        <v:roundrect xmlns:v="urn:schemas-microsoft-com:vml" href="{{ $ctaUrl }}" style="height:44px;v-text-anchor:middle;width:240px;" arcsize="18%" strokecolor="#3B2800" fillcolor="#3B2800">
                                            <w:anchorlock/>
                                            <center style="color:#FFFFFF;font-family:Arial,Helvetica,sans-serif;font-size:12px;font-weight:bold;letter-spacing:0.6px;">{{ strtoupper(__('mail.payment_failed.cta')) }}</center>
                                        </v:roundrect>
                                        <![endif]-->
                                        <!--[if !mso]><!-->
                                        <a href="{{ $ctaUrl }}" style="display:inline-block;padding:14px 30px;background-color:#3B2800;color:#FFFFFF !important;font:900 12px/1 Arial,Helvetica,sans-serif;letter-spacing:0.6px;text-transform:uppercase;text-decoration:none;border-radius:8px;mso-hide:all;">{{ __('mail.payment_failed.cta') }}</a>
                                        <!--<![endif]-->
                                    </td>
                                </tr>
                            </table>
                        </td>
                    </tr>

                    {{-- Footer — bottom corners --}}
                    <tr>
                        <td class="email-pad email-cell-gray" bgcolor="#F8F8F8" style="background-color:#F8F8F8 !important;border-top:1px solid #E6E6E6;padding:18px 30px;border-radius:0 0 14px 14px;">
                            <table role="presentation" width="100%" cellspacing="0" cellpadding="0" border="0" style="margin:0 0 10px 0;">
                                <tr>
                                    <td class="email-heading" style="font:900 13px/1 Arial,Helvetica,sans-serif;color:#3B2800 !important;letter-spacing:-0.4px;">sourcenest</td>
                                    <td align="right" class="email-muted" style="font:700 8px/1 Arial,Helvetica,sans-serif;letter-spacing:0.8px;text-transform:uppercase;color:#B4B4B4 !important;">{{ __('mail.payment_failed.footer_tag') }}</td>
                                </tr>
                            </table>
                            <div style="height:1px;background-color:#E6E6E6;margin:0 0 10px 0;font-size:0;line-height:0;">&nbsp;</div>
                            <table role="presentation" cellspacing="0" cellpadding="0" border="0">
                                <tr>
                                    <td class="email-muted" style="font:600 10.5px/1 Arial,Helvetica,sans-serif;color:#B4B4B4 !important;">
                                        <a href="{{ $frontendUrl }}/privacy" style="color:#B4B4B4 !important;text-decoration:none;">Privacy</a>
                                        <span style="margin:0 5px;font-size:9px;color:#D6D6D6;">·</span>
                                        <a href="{{ $frontendUrl }}/terms" style="color:#B4B4B4 !important;text-decoration:none;">Terms</a>
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
