@php
    $platformName = config('app.name', 'SourceNest');
    $logoUrl = public_url('images/mail/sourcenest-logo.png');
    $frontendUrl = rtrim((string) config('app.frontend_url', config('app.url')), '/');
    $mailIconStyle = 'display:block;border:0;outline:none;text-decoration:none;margin:0 auto;';
    $recipientName = trim($recipientName ?? $name ?? '') !== '' ? trim($recipientName ?? $name ?? '') : 'there';
    $ctaUrl = $ctaUrl ?? \App\Support\Mail\MailNotificationHelper::frontendUrl('dashboard/buyer/rfqs');
    $inboxUrl = $inboxUrl ?? \App\Support\Mail\MailNotificationHelper::frontendUrl('dashboard/buyer/rfqs');
    $heroIconUrl = public_url('images/mail/svg/check-success-hero.svg');
    $inquiryTags = $inquiryTags ?? [];
    $steps = [
        ['title' => __('mail.rfq_quoted_buyer.step_1_title'), 'body' => __('mail.rfq_quoted_buyer.step_1_body', ['manufacturer' => $manufacturerDisplayName ?? $manufacturerName ?? __('order.manufacturer')])],
        ['title' => __('mail.rfq_quoted_buyer.step_2_title'), 'body' => __('mail.rfq_quoted_buyer.step_2_body')],
        ['title' => __('mail.rfq_quoted_buyer.step_3_title'), 'body' => __('mail.rfq_quoted_buyer.step_3_body')],
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
    <title>{{ __('mail.rfq_quoted_buyer.subject', ['rfqNumber' => $rfqNumber ?? '']) }}</title>
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
<body style="margin:0;padding:0;background-color:#F0F0F0;font-family:Arial,Helvetica,sans-serif;-webkit-text-size-adjust:100%;-ms-text-size-adjust:100%;">
    <span style="display:none !important;visibility:hidden;mso-hide:all;font-size:1px;color:#F0F0F0;line-height:1px;max-height:0;max-width:0;opacity:0;overflow:hidden;">{{ __('mail.rfq_quoted_buyer.preheader', ['manufacturer' => $manufacturerName ?? '']) }}</span>

    <table role="presentation" width="100%" cellspacing="0" cellpadding="0" border="0" style="background-color:#F0F0F0;">
        <tr>
            <td align="center" style="padding:24px 12px;">
                <table role="presentation" width="600" cellspacing="0" cellpadding="0" border="0" style="max-width:600px;width:100%;background-color:#FFFFFF;border-radius:14px;overflow:hidden;">

                    {{-- Header --}}
                    <tr>
                        <td bgcolor="#FFFFFF" style="padding:20px 30px;background-color:#FFFFFF;border-bottom:1.5px solid #F0F0F0;">
                            <table role="presentation" width="100%" cellspacing="0" cellpadding="0" border="0">
                                <tr>
                                    <td align="left" valign="middle">
                                        @if (!empty($logoUrl))
                                            <img src="{{ $logoUrl }}" alt="{{ $platformName }}" width="140" style="display:block;height:auto;max-height:68px;width:auto;border:0;outline:none;text-decoration:none;">
                                        @else
                                            <div style="font-weight:900;font-size:21px;line-height:1;font-family:Arial,Helvetica,sans-serif;color:#3B2800;letter-spacing:-0.6px;">sourcenest</div>
                                        @endif
                                    </td>
                                    <td align="right" valign="middle">
                                        <span style="display:inline-block;padding:4px 12px;border-radius:20px;border:1.5px solid #E6E6E6;background-color:#F8F8F8;font-weight:700;font-size:9px;line-height:1;font-family:Arial,Helvetica,sans-serif;letter-spacing:0.4px;text-transform:uppercase;color:#8A8A8A;">{{ __('mail.rfq_quoted_buyer.badge') }}</span>
                                    </td>
                                </tr>
                            </table>
                        </td>
                    </tr>

                    {{-- Hero --}}
                    <tr>
                        <td bgcolor="#FBF7EE" style="padding:26px 30px;background-color:#FBF7EE;border-bottom:1.5px solid #E8D5A8;">
                            <table role="presentation" width="100%" cellspacing="0" cellpadding="0" border="0">
                                <tr>
                                    <td width="58" valign="top" style="padding-right:18px;">
                                        <table role="presentation" cellspacing="0" cellpadding="0" border="0">
                                            <tr>
                                                <td width="58" height="58" align="center" valign="middle" bgcolor="#FFFFFF" style="width:58px;height:58px;background-color:#FFFFFF;border:1.5px solid #E8D5A8;border-radius:14px;">
                                                    <img src="{{ $heroIconUrl }}" width="24" height="24" alt="" style="{{ $mailIconStyle }}">
                                                </td>
                                            </tr>
                                        </table>
                                    </td>
                                    <td valign="top">
                                        <table role="presentation" cellspacing="0" cellpadding="0" border="0" style="margin-bottom:8px;">
                                            <tr>
                                                <td width="20" valign="middle" style="line-height:0;font-size:0;padding-right:8px;">
                                                    <span style="display:block;width:20px;height:2px;border-radius:1px;background-color:#E8D5A8;">&nbsp;</span>
                                                </td>
                                                <td valign="middle" style="font-weight:800;font-size:8.5px;line-height:1;font-family:Arial,Helvetica,sans-serif;letter-spacing:2px;text-transform:uppercase;color:#9A7A3A;">{{ __('mail.rfq_quoted_buyer.pill') }}</td>
                                            </tr>
                                        </table>
                                        <div style="font-weight:500;font-size:22px;line-height:1.17;font-family:Georgia,'Times New Roman',serif;color:#3B2800;letter-spacing:-0.2px;">
                                            {!! __('mail.rfq_quoted_buyer.hero_headline') !!}
                                        </div>
                                        <div style="padding-top:6px;font-weight:400;font-size:13px;line-height:1.78;font-family:Arial,Helvetica,sans-serif;color:#666666;">{{ __('mail.rfq_quoted_buyer.hero_subheadline') }}</div>
                                    </td>
                                </tr>
                            </table>
                        </td>
                    </tr>

                    {{-- Intro + quote card --}}
                    <tr>
                        <td bgcolor="#FFFFFF" style="padding:28px 30px;background-color:#FFFFFF;border-bottom:1px solid #F0F0F0;">
                            <div style="font-weight:500;font-size:17px;line-height:1;font-family:Georgia,'Times New Roman',serif;color:#3B2800;margin-bottom:13px;">{{ __('mail.rfq_quoted_buyer.greeting', ['name' => $recipientName]) }}</div>
                            <p style="margin:0;font-weight:400;font-size:13.5px;line-height:1.88;font-family:Arial,Helvetica,sans-serif;color:#464646;">{{ __('mail.rfq_quoted_buyer.intro', ['manufacturer' => $manufacturerName ?? '', 'rfq' => $rfqNumber ?? '']) }}</p>

                            <table role="presentation" width="100%" cellspacing="0" cellpadding="0" border="0" style="margin-top:20px;border:1.5px solid #E6E6E6;border-radius:12px;overflow:hidden;border-collapse:separate;">
                                <tr>
                                    <td bgcolor="#F8F8F8" style="padding:16px 18px;background-color:#F8F8F8;border-bottom:1px solid #E6E6E6;">
                                        <table role="presentation" width="100%" cellspacing="0" cellpadding="0" border="0">
                                            <tr>
                                                <td width="44" valign="top">
                                                    <div style="width:40px;height:40px;border-radius:10px;background-color:#3B2800;color:#FFFFFF;font-weight:800;font-size:13px;line-height:40px;font-family:Arial,Helvetica,sans-serif;text-align:center;">{{ $manufacturerInitials ?? 'SP' }}</div>
                                                </td>
                                                <td valign="top" style="padding-left:12px;">
                                                    <div style="font-weight:800;font-size:13px;line-height:1.3;font-family:Arial,Helvetica,sans-serif;color:#1C1C1C;">{{ $manufacturerDisplayName ?? $manufacturerName ?? 'Supplier' }}</div>
                                                    @if (!empty($manufacturerMeta))
                                                        <div style="font-weight:600;font-size:11px;line-height:1.4;font-family:Arial,Helvetica,sans-serif;color:#8A8A8A;margin-top:3px;">{{ $manufacturerMeta }}</div>
                                                    @endif
                                                </td>
                                                @if (!empty($inquiryTimestamp))
                                                    <td align="right" valign="top" style="font-weight:600;font-size:10px;line-height:1.3;font-family:Arial,Helvetica,sans-serif;color:#B4B4B4;white-space:nowrap;">{{ $inquiryTimestamp }}</td>
                                                @endif
                                            </tr>
                                        </table>
                                    </td>
                                </tr>
                                @if (!empty($messagePreview))
                                    <tr>
                                        <td style="padding:16px 18px;">
                                            <div style="font-weight:500;font-size:13px;line-height:1.65;font-family:Arial,Helvetica,sans-serif;color:#464646;">{!! $messagePreview !!}</div>
                                        </td>
                                    </tr>
                                @endif
                                @if (!empty($inquiryTags))
                                    <tr>
                                        <td style="padding:0 18px 16px 18px;">
                                            @foreach ($inquiryTags as $tag)
                                                <span style="display:inline-block;font-weight:600;font-size:10px;line-height:1;font-family:Arial,Helvetica,sans-serif;color:#666666;background-color:#F8F8F8;border:1px solid #E6E6E6;border-radius:6px;padding:4px 8px;margin:0 6px 6px 0;">{{ $tag['label'] ?? '' }} <strong style="color:#1C1C1C;">{{ $tag['value'] ?? '' }}</strong></span>
                                            @endforeach
                                        </td>
                                    </tr>
                                @endif
                            </table>

                            <table role="presentation" width="100%" cellspacing="0" cellpadding="0" border="0" style="margin-top:16px;background-color:#EAFAF2;border:1.5px solid #6ECFA0;border-radius:10px;border-collapse:separate;">
                                <tr>
                                    <td style="padding:15px 16px;">
                                        <div style="font-weight:900;font-size:8.5px;line-height:1;font-family:Arial,Helvetica,sans-serif;letter-spacing:1.6px;text-transform:uppercase;color:#0A5C32;margin-bottom:5px;">{{ __('mail.rfq_quoted_buyer.opportunity_label') }}</div>
                                        <div style="font-weight:400;font-size:13px;line-height:1.65;font-family:Arial,Helvetica,sans-serif;color:#464646;">{!! __('mail.rfq_quoted_buyer.opportunity_body', ['validUntil' => $validUntil ?? '']) !!}</div>
                                    </td>
                                </tr>
                            </table>
                        </td>
                    </tr>

                    {{-- Steps --}}
                    <tr>
                        <td bgcolor="#F8F8F8" style="padding:28px 30px;background-color:#F8F8F8;border-bottom:1px solid #F0F0F0;">
                            <table role="presentation" cellspacing="0" cellpadding="0" border="0" style="margin-bottom:18px;">
                                <tr>
                                    <td width="3" bgcolor="#E8D5A8" style="background-color:#E8D5A8;border-radius:2px;">&nbsp;</td>
                                    <td style="padding-left:9px;font-weight:500;font-size:17px;line-height:1;font-family:Georgia,'Times New Roman',serif;color:#3B2800;">{!! __('mail.rfq_quoted_buyer.steps_title') !!}</td>
                                </tr>
                            </table>

                            @foreach ($steps as $index => $step)
                                <table role="presentation" width="100%" cellspacing="0" cellpadding="0" border="0" @if (! $loop->last) style="margin-bottom:0;border-bottom:1px solid #F0F0F0;" @endif>
                                    <tr>
                                        <td width="40" valign="top" style="padding:15px 0;">
                                            <table role="presentation" cellspacing="0" cellpadding="0" border="0">
                                                <tr>
                                                    <td width="26" height="26" align="center" valign="middle" bgcolor="#FBF7EE" style="width:26px;height:26px;background-color:#FBF7EE;border:1.5px solid #E8D5A8;border-radius:50%;font-weight:900;font-size:11px;line-height:1;font-family:Arial,Helvetica,sans-serif;color:#9A7A3A;">{{ $index + 1 }}</td>
                                                </tr>
                                                @if (! $loop->last)
                                                    <tr>
                                                        <td align="center" style="padding-top:4px;line-height:0;font-size:0;">
                                                            <span style="display:block;width:1px;height:24px;background-color:#E6E6E6;">&nbsp;</span>
                                                        </td>
                                                    </tr>
                                                @endif
                                            </table>
                                        </td>
                                        <td valign="top" style="padding:15px 0;">
                                            <div style="font-weight:700;font-size:13.5px;line-height:1.2;font-family:Arial,Helvetica,sans-serif;color:#1C1C1C;margin-bottom:3px;">{{ $step['title'] }}</div>
                                            <div style="font-weight:400;font-size:12.5px;line-height:1.55;font-family:Arial,Helvetica,sans-serif;color:#8A8A8A;">{{ $step['body'] }}</div>
                                        </td>
                                    </tr>
                                </table>
                            @endforeach

                            <table role="presentation" width="100%" cellspacing="0" cellpadding="0" border="0" style="margin-top:16px;background-color:#FBF7EE;border:1.5px solid #E8D5A8;border-left:4px solid #9A7A3A;border-radius:8px;border-collapse:separate;">
                                <tr>
                                    <td style="padding:14px 16px;">
                                        <div style="font-weight:900;font-size:8.5px;line-height:1;font-family:Arial,Helvetica,sans-serif;letter-spacing:1.6px;text-transform:uppercase;color:#9A7A3A;margin-bottom:5px;">{{ __('mail.rfq_quoted_buyer.tip_label') }}</div>
                                        <div style="font-weight:400;font-size:13px;line-height:1.65;font-family:Arial,Helvetica,sans-serif;color:#464646;">{{ __('mail.rfq_quoted_buyer.tip_body') }}</div>
                                    </td>
                                </tr>
                            </table>
                        </td>
                    </tr>

                    {{-- CTA --}}
                    <tr>
                        <td bgcolor="#FFFFFF" style="padding:26px 30px 30px;background-color:#FFFFFF;border-top:1px solid #F0F0F0;">
                            <table role="presentation" cellspacing="0" cellpadding="0" border="0">
                                <tr>
                                    <td>
                                        <a href="{{ $ctaUrl }}" style="display:inline-block;padding:14px 30px;background-color:#3B2800;color:#FFFFFF;font-weight:900;font-size:12px;line-height:1;font-family:Arial,Helvetica,sans-serif;letter-spacing:0.6px;text-transform:uppercase;text-decoration:none;border-radius:8px;">{{ __('mail.rfq_quoted_buyer.cta') }}</a>
                                    </td>
                                </tr>
                                <tr>
                                    <td style="padding-top:10px;">
                                        <a href="{{ $inboxUrl }}" style="font-weight:600;font-size:12.5px;line-height:1;font-family:Arial,Helvetica,sans-serif;color:#B4B4B4;text-decoration:none;">{{ __('mail.rfq_quoted_buyer.cta_secondary') }}</a>
                                    </td>
                                </tr>
                            </table>
                        </td>
                    </tr>

                    {{-- Footer --}}
                    <tr>
                        <td bgcolor="#F8F8F8" style="padding:18px 30px;background-color:#F8F8F8;border-top:1px solid #E6E6E6;">
                            <table role="presentation" width="100%" cellspacing="0" cellpadding="0" border="0">
                                <tr>
                                    <td style="font-weight:900;font-size:13px;line-height:1;font-family:Arial,Helvetica,sans-serif;color:#3B2800;letter-spacing:-0.4px;">sourcenest</td>
                                    <td align="right" style="font-weight:700;font-size:8px;line-height:1;font-family:Arial,Helvetica,sans-serif;letter-spacing:0.8px;text-transform:uppercase;color:#B4B4B4;">{{ __('mail.rfq_quoted_buyer.footer_tag') }}</td>
                                </tr>
                            </table>
                            <table role="presentation" width="100%" cellspacing="0" cellpadding="0" border="0" style="margin:10px 0;">
                                <tr>
                                    <td height="1" bgcolor="#E6E6E6" style="height:1px;background-color:#E6E6E6;font-size:0;line-height:0;">&nbsp;</td>
                                </tr>
                            </table>
                            <span style="font-weight:600;font-size:10.5px;line-height:1;font-family:Arial,Helvetica,sans-serif;color:#B4B4B4;">
                                <a href="{{ $frontendUrl }}/unsubscribe" style="color:#B4B4B4;text-decoration:none;">Unsubscribe</a>
                                <span style="margin:0 5px;font-size:9px;color:#E6E6E6;">·</span>
                                <a href="{{ $frontendUrl }}/privacy" style="color:#B4B4B4;text-decoration:none;">Privacy</a>
                                <span style="margin:0 5px;font-size:9px;color:#E6E6E6;">·</span>
                                <a href="{{ $frontendUrl }}/terms" style="color:#B4B4B4;text-decoration:none;">Terms</a>
                            </span>
                        </td>
                    </tr>

                </table>
            </td>
        </tr>
    </table>
</body>
</html>
