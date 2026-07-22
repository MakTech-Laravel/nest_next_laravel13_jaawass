@php
    $platformName = config('app.name', 'SourceNest');
    $logoUrl = public_url('images/mail/sourcenest-logo.png');
    $frontendUrl = rtrim((string) config('app.frontend_url', config('app.url')), '/');
    $supportEmail = config('mail.from.address', 'no-reply@sourcenest.com');
    $recipientName = trim((string) ($recipientName ?? $name ?? '')) !== '' ? trim((string) ($recipientName ?? $name ?? '')) : 'Admin';
    $buyerName = $buyerName ?? '';
    $supplierName = $supplierName ?? '';
    $reportId = $reportId ?? '';
    $reasonLabel = $reasonLabel ?? '';
    $messageBody = $messageBody ?? null;
    $referenceId = $referenceId ?? ($reportId !== '' ? 'RPT-'.str_pad((string) $reportId, 5, '0', STR_PAD_LEFT) : '');
    $ctaUrl = $ctaUrl ?? \App\Support\Mail\MailNotificationHelper::frontendUrl('admin/reports');
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
    <title>{{ __('mail.supplier_report_received_admin.subject', ['reportId' => $reportId]) }}</title>
    <!--[if !mso]><!-->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Nunito:ital,wght@0,300;0,400;0,500;0,600;0,700;0,800;0,900;1,400&family=Lora:ital,wght@0,400;0,500;0,600;1,400;1,500;1,600&display=swap" rel="stylesheet">
    <!--<![endif]-->
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
        @media only screen and (max-width: 640px) {
            .email-outer { padding: 12px 8px !important; }
            .email-pad { padding-left: 18px !important; padding-right: 18px !important; }
            .email-hero-title { font-size: 20px !important; }
            .email-stack { display: block !important; width: 100% !important; }
            .email-stack-icon { padding-right: 0 !important; padding-bottom: 14px !important; }
            .email-footer-tag { text-align: left !important; display: block !important; padding-top: 6px !important; }
            .email-evl-lbl { display: block !important; width: 100% !important; border-right: none !important; border-bottom: 1px solid #F0F0F0 !important; }
            .email-evl-val { display: block !important; width: 100% !important; }
        }
    </style>
</head>

<body style="margin:0;padding:0;background-color:#F0F0F0;font-family:'Nunito',Arial,Helvetica,sans-serif;-webkit-text-size-adjust:100%;-ms-text-size-adjust:100%;">
    <span style="display:none !important;visibility:hidden;mso-hide:all;font-size:1px;color:#F0F0F0;line-height:1px;max-height:0;max-width:0;opacity:0;overflow:hidden;">{{ __('mail.supplier_report_received_admin.preheader') }}</span>

    <table role="presentation" width="100%" cellspacing="0" cellpadding="0" border="0" style="background-color:#F0F0F0;">
        <tr>
            <td align="center" class="email-outer" style="padding:24px 12px;">
                <table role="presentation" width="600" cellspacing="0" cellpadding="0" border="0"
                    style="max-width:600px;width:100%;background-color:#FFFFFF;border-radius:14px;overflow:hidden;box-shadow:0 0 0 1px rgba(0,0,0,.055),0 4px 16px rgba(0,0,0,.06);">

                    {{-- Header --}}
                    <tr>
                        <td class="email-pad" bgcolor="#FFFFFF" style="padding:20px 30px;background-color:#FFFFFF;border-bottom:1.5px solid #F0F0F0;">
                            <table role="presentation" width="100%" cellspacing="0" cellpadding="0" border="0">
                                <tr>
                                    <td align="left" valign="middle">
                                        @if (! empty($logoUrl))
                                            <img src="{{ $logoUrl }}" alt="{{ $platformName }}" width="140" style="display:block;height:auto;max-height:68px;width:auto;border:0;outline:none;text-decoration:none;">
                                        @else
                                            <div style="font-weight:900;font-size:21px;line-height:1;font-family:'Nunito',Arial,Helvetica,sans-serif;color:#3B2800;letter-spacing:-0.6px;">sourcenest</div>
                                        @endif
                                    </td>
                                    <td align="right" valign="middle">
                                        <span style="display:inline-block;padding:4px 12px;border-radius:20px;border:1.5px solid #E6E6E6;background-color:#F8F8F8;font-weight:700;font-size:9px;line-height:1;font-family:'Nunito',Arial,Helvetica,sans-serif;letter-spacing:0.4px;text-transform:uppercase;color:#8A8A8A;">{{ __('mail.supplier_report_received_admin.badge') }}</span>
                                    </td>
                                </tr>
                            </table>
                        </td>
                    </tr>

                    {{-- Hero --}}
                    <tr>
                        <td class="email-pad" bgcolor="#FBF7EE" style="padding:26px 30px;background-color:#FBF7EE;background-image:linear-gradient(135deg,#FBF7EE 0%,#FFFFFF 55%);border-bottom:1.5px solid #E8D5A8;">
                            <table role="presentation" width="100%" cellspacing="0" cellpadding="0" border="0">
                                <tr>
                                    <td class="email-stack email-stack-icon" width="76" valign="middle" style="width:76px;padding-right:18px;">
                                        <table role="presentation" cellspacing="0" cellpadding="0" border="0" style="border-collapse:separate;">
                                            <tr>
                                                <td width="58" height="58" align="center" valign="middle" bgcolor="#FFFFFF"
                                                    style="width:58px;height:58px;background-color:#FFFFFF;border:1.5px solid #E8D5A8;border-radius:14px;box-shadow:0 2px 10px rgba(59,40,0,.06),0 0 0 5px #FBF7EE;">
                                                    <span style="display:inline-block;font-family:Arial,Helvetica,sans-serif;font-size:26px;line-height:1;color:#9A7A3A;">&#9873;</span>
                                                </td>
                                            </tr>
                                        </table>
                                    </td>
                                    <td class="email-stack" valign="middle">
                                        <table role="presentation" cellspacing="0" cellpadding="0" border="0" style="margin-bottom:8px;">
                                            <tr>
                                                <td width="20" valign="middle" style="line-height:0;font-size:0;padding-right:8px;">
                                                    <span style="display:block;width:20px;height:2px;border-radius:1px;background-color:#E8D5A8;">&nbsp;</span>
                                                </td>
                                                <td valign="middle" style="font-weight:800;font-size:8.5px;line-height:1;font-family:'Nunito',Arial,Helvetica,sans-serif;letter-spacing:2px;text-transform:uppercase;color:#9A7A3A;">{{ __('mail.supplier_report_received_admin.pill') }}</td>
                                            </tr>
                                        </table>
                                        <div class="email-hero-title" style="font-weight:500;font-size:22px;line-height:1.17;font-family:'Lora',Georgia,'Times New Roman',serif;color:#3B2800;letter-spacing:-0.2px;">
                                            {!! __('mail.supplier_report_received_admin.hero_headline') !!}
                                        </div>
                                        <div style="padding-top:6px;font-weight:400;font-size:13px;line-height:1.78;font-family:'Nunito',Arial,Helvetica,sans-serif;color:#666666;">{{ __('mail.supplier_report_received_admin.hero_subheadline') }}</div>
                                    </td>
                                </tr>
                            </table>
                        </td>
                    </tr>

                    {{-- Intro + details --}}
                    <tr>
                        <td class="email-pad" bgcolor="#FFFFFF" style="padding:28px 30px;background-color:#FFFFFF;border-bottom:1px solid #F0F0F0;">
                            <div style="font-weight:500;font-size:17px;line-height:1;font-family:'Lora',Georgia,'Times New Roman',serif;color:#3B2800;margin-bottom:13px;">{{ __('mail.supplier_report_received_admin.greeting', ['name' => $recipientName]) }}</div>
                            <p style="margin:0 0 18px 0;font-weight:400;font-size:13.5px;line-height:1.88;font-family:'Nunito',Arial,Helvetica,sans-serif;color:#464646;">{{ __('mail.supplier_report_received_admin.intro', ['buyer' => $buyerName, 'supplier' => $supplierName, 'id' => $reportId]) }}</p>

                            <table role="presentation" cellspacing="0" cellpadding="0" border="0" style="margin-bottom:18px;border-collapse:separate;">
                                <tr>
                                    <td width="3" bgcolor="#E8D5A8" style="width:3px;height:18px;background-color:#E8D5A8;border-radius:2px;font-size:0;line-height:0;">&nbsp;</td>
                                    <td style="padding-left:9px;font-weight:500;font-size:17px;line-height:1;font-family:'Lora',Georgia,'Times New Roman',serif;color:#3B2800;">{{ __('mail.supplier_report_received_admin.details_title') }}</td>
                                </tr>
                            </table>

                            <table role="presentation" width="100%" cellspacing="0" cellpadding="0" border="0"
                                style="background-color:#FFFFFF;border:1.5px solid #E6E6E6;border-radius:10px;border-collapse:separate;overflow:hidden;">
                                <tr>
                                    <td bgcolor="#F8F8F8" style="padding:11px 16px;background-color:#F8F8F8;border-bottom:1px solid #E6E6E6;">
                                        <table role="presentation" width="100%" cellspacing="0" cellpadding="0" border="0">
                                            <tr>
                                                <td style="font-weight:900;font-size:9px;line-height:1;font-family:'Nunito',Arial,Helvetica,sans-serif;letter-spacing:1.2px;text-transform:uppercase;color:#8A8A8A;">{{ __('mail.supplier_report_received_admin.details_title') }}</td>
                                                <td align="right">
                                                    <span style="display:inline-block;padding:2px 10px;border-radius:20px;border:1.5px solid #F0C040;background-color:#FFF8E4;font-weight:800;font-size:9px;line-height:1;font-family:'Nunito',Arial,Helvetica,sans-serif;color:#7A4D00;">{{ __('mail.supplier_report_received_admin.status_badge') }}</span>
                                                </td>
                                            </tr>
                                        </table>
                                    </td>
                                </tr>
                                @foreach ([
                                    [__('mail.supplier_report_received_admin.label_report_id'), $referenceId !== '' ? $referenceId : $reportId],
                                    [__('mail.supplier_report_received_admin.label_buyer'), $buyerName],
                                    [__('mail.supplier_report_received_admin.label_supplier'), $supplierName],
                                    [__('mail.supplier_report_received_admin.label_reason'), $reasonLabel],
                                ] as [$label, $value])
                                    @if ($value !== null && $value !== '')
                                        <tr>
                                            <td style="padding:0;">
                                                <table role="presentation" width="100%" cellspacing="0" cellpadding="0" border="0">
                                                    <tr>
                                                        <td class="email-evl-lbl" width="110" bgcolor="#F8F8F8" valign="middle"
                                                            style="width:110px;padding:11px 16px;background-color:#F8F8F8;border-top:1px solid #F0F0F0;border-right:1px solid #F0F0F0;font-weight:700;font-size:11px;line-height:1;font-family:'Nunito',Arial,Helvetica,sans-serif;color:#8A8A8A;">{{ $label }}</td>
                                                        <td class="email-evl-val" valign="middle" style="padding:11px 16px;border-top:1px solid #F0F0F0;font-weight:500;font-size:12.5px;line-height:1.4;font-family:'Nunito',Arial,Helvetica,sans-serif;color:#1C1C1C;">{{ $value }}</td>
                                                    </tr>
                                                </table>
                                            </td>
                                        </tr>
                                    @endif
                                @endforeach
                            </table>
                        </td>
                    </tr>

                    @if (! empty($messageBody))
                        {{-- Report message body --}}
                        <tr>
                            <td class="email-pad" bgcolor="#F8F8F8" style="padding:28px 30px;background-color:#F8F8F8;border-bottom:1px solid #F0F0F0;">
                                <table role="presentation" cellspacing="0" cellpadding="0" border="0" style="margin-bottom:18px;border-collapse:separate;">
                                    <tr>
                                        <td width="3" bgcolor="#E8D5A8" style="width:3px;height:18px;background-color:#E8D5A8;border-radius:2px;font-size:0;line-height:0;">&nbsp;</td>
                                        <td style="padding-left:9px;font-weight:500;font-size:17px;line-height:1;font-family:'Lora',Georgia,'Times New Roman',serif;color:#3B2800;">{{ __('mail.supplier_report_received_admin.details_heading') }}</td>
                                    </tr>
                                </table>

                                <table role="presentation" width="100%" cellspacing="0" cellpadding="0" border="0"
                                    style="background-color:#FFFFFF;border:1.5px solid #E6E6E6;border-radius:10px;border-collapse:separate;overflow:hidden;">
                                    <tr>
                                        <td style="padding:17px 19px;">
                                            <div style="margin:0;font-weight:400;font-size:13.5px;line-height:1.88;font-family:'Nunito',Arial,Helvetica,sans-serif;color:#464646;">{!! $messageBody !!}</div>
                                        </td>
                                    </tr>
                                </table>
                            </td>
                        </tr>
                    @endif

                    {{-- CTA --}}
                    <tr>
                        <td class="email-pad" bgcolor="#FFFFFF" style="padding:26px 30px 30px;background-color:#FFFFFF;">
                            <table role="presentation" cellspacing="0" cellpadding="0" border="0" style="border-collapse:separate;">
                                <tr>
                                    <td bgcolor="#3B2800" style="border-radius:8px;background-color:#3B2800;">
                                        <a href="{{ $ctaUrl }}"
                                            style="display:inline-block;padding:14px 30px;background-color:#3B2800;color:#FFFFFF;font-weight:900;font-size:12px;line-height:1;font-family:'Nunito',Arial,Helvetica,sans-serif;letter-spacing:0.6px;text-transform:uppercase;text-decoration:none;border-radius:8px;">{{ __('mail.supplier_report_received_admin.cta') }}</a>
                                    </td>
                                </tr>
                            </table>
                            <p style="margin:18px 0 0 0;padding-top:18px;border-top:1px solid #F0F0F0;font-weight:400;font-size:12px;line-height:1.75;font-family:'Nunito',Arial,Helvetica,sans-serif;color:#8A8A8A;">
                                {{ __('mail.supplier_report_received_admin.cta_note') }}
                            </p>
                        </td>
                    </tr>

                    {{-- Footer --}}
                    <tr>
                        <td class="email-pad" bgcolor="#F8F8F8" style="padding:18px 30px;background-color:#F8F8F8;border-top:1px solid #E6E6E6;">
                            <table role="presentation" width="100%" cellspacing="0" cellpadding="0" border="0">
                                <tr>
                                    <td style="font-weight:900;font-size:13px;line-height:1;font-family:'Nunito',Arial,Helvetica,sans-serif;color:#3B2800;letter-spacing:-0.4px;">sourcenest</td>
                                    <td class="email-footer-tag" align="right" style="font-weight:700;font-size:8px;line-height:1;font-family:'Nunito',Arial,Helvetica,sans-serif;letter-spacing:0.8px;text-transform:uppercase;color:#B4B4B4;">{{ __('mail.supplier_report_received_admin.footer_tag') }}</td>
                                </tr>
                            </table>
                            <table role="presentation" width="100%" cellspacing="0" cellpadding="0" border="0" style="margin:10px 0;">
                                <tr>
                                    <td height="1" bgcolor="#E6E6E6" style="height:1px;background-color:#E6E6E6;font-size:0;line-height:0;">&nbsp;</td>
                                </tr>
                            </table>
                            <span style="font-weight:600;font-size:10.5px;line-height:1;font-family:'Nunito',Arial,Helvetica,sans-serif;color:#8A8A8A;">{{ __('mail.supplier_report_received_admin.footer') }}</span>
                            <span style="font-size:9px;color:#D6D6D6;margin:0 5px;">·</span>
                            <span style="font-weight:600;font-size:10.5px;line-height:1;font-family:'Nunito',Arial,Helvetica,sans-serif;color:#8A8A8A;">{{ $supportEmail }}</span>
                            <span style="margin:0 5px;font-size:9px;color:#E6E6E6;">·</span>
                            <a href="{{ $frontendUrl }}/privacy" style="font-weight:600;font-size:10.5px;line-height:1;font-family:'Nunito',Arial,Helvetica,sans-serif;color:#B4B4B4;text-decoration:none;">Privacy</a>
                        </td>
                    </tr>

                </table>
            </td>
        </tr>
    </table>
</body>

</html>
