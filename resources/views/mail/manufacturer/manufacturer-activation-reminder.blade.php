@php
    $platformName = config('app.name', 'SourceNest');
    $logoUrl = public_url('images/mail/sourcenest-logo.png');
    $frontendUrl = rtrim((string) config('app.frontend_url', config('app.url')), '/');
    $supportEmail = config('mail.from.address', 'support@sourcenest.com');
    $mailIconStyle = 'display:block;border:0;outline:none;text-decoration:none;margin:0;';
    $recipientName = trim($recipientName ?? ($name ?? ($firstName ?? ''))) !== ''
        ? trim($recipientName ?? ($name ?? ($firstName ?? '')))
        : 'there';
    $companyName = trim($company ?? '') !== '' ? trim($company ?? '') : $platformName;
    $approvedAt = $approvedAt ?? ($approvedDate ?? null);
    $ctaUrl = $ctaUrl ?? \App\Support\Mail\MailNotificationHelper::frontendUrl('pricing');
    $detailsUrl = $detailsUrl ?? \App\Support\Mail\MailNotificationHelper::frontendUrl('pricing');
    $closeAccountUrl = $closeAccountUrl ?? \App\Support\Mail\MailNotificationHelper::frontendUrl('dashboard/manufacturer/settings');
    $whyCards = [
        [
            'icon' => public_url('images/mail/icons/why-user.png'),
            'title' => __('mail.manufacturer_activation_reminder.why_1_title'),
            'body' => __('mail.manufacturer_activation_reminder.why_1_body'),
        ],
        [
            'icon' => public_url('images/mail/icons/why-globe.png'),
            'title' => __('mail.manufacturer_activation_reminder.why_2_title'),
            'body' => __('mail.manufacturer_activation_reminder.why_2_body'),
        ],
        [
            'icon' => public_url('images/mail/icons/why-diamond.png'),
            'title' => __('mail.manufacturer_activation_reminder.why_3_title'),
            'body' => __('mail.manufacturer_activation_reminder.why_3_body'),
        ],
        [
            'icon' => public_url('images/mail/icons/why-calendar.png'),
            'title' => __('mail.manufacturer_activation_reminder.why_4_title'),
            'body' => __('mail.manufacturer_activation_reminder.why_4_body'),
        ],
    ];
    $whyPairs = array_chunk($whyCards, 2);
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
    <title>{{ __('mail.manufacturer_activation_reminder.subject') }}</title>
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
        style="display:none !important;visibility:hidden;mso-hide:all;font-size:1px;color:#F0F0F0;line-height:1px;max-height:0;max-width:0;opacity:0;overflow:hidden;">{{ __('mail.manufacturer_activation_reminder.preheader') }}</span>

    <table role="presentation" width="100%" cellspacing="0" cellpadding="0" border="0"
        style="background-color:#F0F0F0;">
        <tr>
            <td align="center" style="padding:24px 12px;">
                <table role="presentation" width="600" cellspacing="0" cellpadding="0" border="0"
                    style="max-width:600px;width:100%;background-color:#FFFFFF;border-radius:14px;overflow:hidden;">

                    {{-- Header A: white --}}
                    <tr>
                        <td bgcolor="#FFFFFF"
                            style="padding:20px 30px;background-color:#FFFFFF;border-bottom:1.5px solid #F0F0F0;">
                            <table role="presentation" width="100%" cellspacing="0" cellpadding="0" border="0">
                                <tr>
                                    <td align="left" valign="middle">
                                        @if (!empty($logoUrl))
                                            <img src="{{ $logoUrl }}" alt="{{ $platformName }}" width="140"
                                                style="display:block;height:auto;max-height:68px;width:auto;border:0;outline:none;text-decoration:none;">
                                        @else
                                            <div
                                                style="font-weight:900;font-size:21px;line-height:1;font-family:Arial,Helvetica,sans-serif;color:#3B2800;letter-spacing:-0.6px;">
                                                sourcenest</div>
                                        @endif
                                    </td>
                                    <td align="right" valign="middle">
                                        <span
                                            style="display:inline-block;padding:4px 12px;border-radius:20px;border:1.5px solid #E6E6E6;background-color:#F8F8F8;font-weight:700;font-size:9px;line-height:1;font-family:Arial,Helvetica,sans-serif;letter-spacing:0.4px;text-transform:uppercase;color:#8A8A8A;">{{ __('mail.manufacturer_activation_reminder.badge') }}</span>
                                    </td>
                                </tr>
                            </table>
                        </td>
                    </tr>

                    {{-- Hero H2: white with ghost deco --}}
                    <tr>
                        <td bgcolor="#FFFFFF"
                            style="padding:34px 30px 32px;background-color:#FFFFFF;border-bottom:2px solid #F0F0F0;">
                            <table role="presentation" width="100%" cellspacing="0" cellpadding="0" border="0">
                                <tr>
                                    <td valign="top" style="padding-right:12px;">
                                        <table role="presentation" cellspacing="0" cellpadding="0" border="0"
                                            style="margin-bottom:14px;border-collapse:separate;">
                                            <tr>
                                                <td
                                                    style="padding:4px 11px;border-radius:20px;border:1.5px solid #F0C040;background-color:#FFF8E4;">
                                                    <table role="presentation" cellspacing="0" cellpadding="0"
                                                        border="0">
                                                        <tr>
                                                            <td width="5" valign="middle"
                                                                style="line-height:0;font-size:0;">
                                                                <span
                                                                    style="display:block;width:5px;height:5px;border-radius:50%;background-color:#C07800;">&nbsp;</span>
                                                            </td>
                                                            <td valign="middle"
                                                                style="padding-left:5px;font-weight:800;font-size:8.5px;line-height:1;font-family:Arial,Helvetica,sans-serif;letter-spacing:1.2px;text-transform:uppercase;color:#666666;">
                                                                {{ __('mail.manufacturer_activation_reminder.pill') }}</td>
                                                        </tr>
                                                    </table>
                                                </td>
                                            </tr>
                                        </table>
                                        <div
                                            style="font-weight:500;font-size:27px;line-height:1.17;font-family:Georgia,'Times New Roman',serif;color:#3B2800;letter-spacing:-0.2px;">
                                            {!! __('mail.manufacturer_activation_reminder.hero_headline') !!}
                                        </div>
                                        <div
                                            style="padding-top:12px;font-weight:400;font-size:13.5px;line-height:1.78;font-family:Arial,Helvetica,sans-serif;color:#666666;max-width:480px;">
                                            {{ __('mail.manufacturer_activation_reminder.hero_subheadline') }}</div>
                                    </td>
                                    <td width="48" align="right" valign="top"
                                        style="width:48px;font-weight:600;font-size:88px;line-height:0.85;font-family:Georgia,'Times New Roman',serif;color:#E8D5A8;opacity:0.55;letter-spacing:-2px;">
                                        !</td>
                                </tr>
                            </table>
                        </td>
                    </tr>

                    {{-- Body --}}
                    <tr>
                        <td bgcolor="#FFFFFF"
                            style="padding:28px 30px;background-color:#FFFFFF;border-bottom:1px solid #F0F0F0;">
                            <div
                                style="font-weight:500;font-size:17px;line-height:1;font-family:Georgia,'Times New Roman',serif;color:#3B2800;margin-bottom:13px;">
                                {{ __('mail.manufacturer_activation_reminder.greeting', ['name' => $recipientName]) }}
                            </div>
                            <div
                                style="font-weight:400;font-size:13.5px;line-height:1.88;font-family:Arial,Helvetica,sans-serif;color:#464646;margin-bottom:16px;">
                                {!! __('mail.manufacturer_activation_reminder.intro') !!}
                            </div>

                            <table role="presentation" width="100%" cellspacing="0" cellpadding="0" border="0"
                                style="margin-top:0;background-color:#FFF8E4;border:1.5px solid #F0C040;border-radius:8px;border-collapse:separate;">
                                <tr>
                                    <td style="padding:12px 15px;">
                                        <table role="presentation" cellspacing="0" cellpadding="0" border="0">
                                            <tr>
                                                <td width="7" valign="middle" style="line-height:0;font-size:0;">
                                                    <span
                                                        style="display:block;width:7px;height:7px;border-radius:50%;background-color:#C07800;">&nbsp;</span>
                                                </td>
                                                <td valign="middle"
                                                    style="padding-left:8px;font-weight:700;font-size:12.5px;line-height:1;font-family:Arial,Helvetica,sans-serif;color:#1C1C1C;">
                                                    {{ __('mail.manufacturer_activation_reminder.status_label') }}</td>
                                            </tr>
                                        </table>
                                    </td>
                                </tr>
                            </table>

                            {{-- Why grid 2x2 --}}
                            <table role="presentation" width="100%" cellspacing="0" cellpadding="0" border="0"
                                style="margin-top:16px;border:1.5px solid #E6E6E6;border-radius:10px;border-collapse:separate;overflow:hidden;">
                                @foreach ($whyPairs as $pair)
                                    <tr>
                                        @foreach ($pair as $card)
                                            <td width="50%" valign="top"
                                                style="width:50%;padding:15px 14px;background-color:#FFFFFF;{{ $loop->first ? 'border-right:1.5px solid #E6E6E6;' : '' }}{{ !$loop->parent->last ? 'border-bottom:1.5px solid #E6E6E6;' : '' }}">
                                                <table role="presentation" cellspacing="0" cellpadding="0" border="0"
                                                    style="margin-bottom:8px;">
                                                    <tr>
                                                        <td>
                                                            <img src="{{ $card['icon'] }}" width="15" height="15"
                                                                alt="" style="{{ $mailIconStyle }}">
                                                        </td>
                                                    </tr>
                                                </table>
                                                <div
                                                    style="font-weight:800;font-size:12.5px;line-height:1;font-family:Arial,Helvetica,sans-serif;color:#3B2800;margin-bottom:3px;">
                                                    {{ $card['title'] }}</div>
                                                <div
                                                    style="font-weight:400;font-size:11.5px;line-height:1.5;font-family:Arial,Helvetica,sans-serif;color:#8A8A8A;">
                                                    {{ $card['body'] }}</div>
                                            </td>
                                        @endforeach
                                    </tr>
                                @endforeach
                            </table>
                        </td>
                    </tr>

                    {{-- CTA --}}
                    <tr>
                        <td bgcolor="#FFFFFF"
                            style="padding:26px 30px 30px;background-color:#FFFFFF;border-top:1px solid #F0F0F0;">
                            <table role="presentation" cellspacing="0" cellpadding="0" border="0" style="border-collapse:separate;">
                                <tr>
                                    <td align="center" bgcolor="#9A7A3A" style="border-radius:8px;background-color:#9A7A3A;">
                                        <a href="{{ $ctaUrl }}"
                                            style="display:inline-block;padding:14px 30px;background-color:#9A7A3A;color:#FFFFFF;font-weight:900;font-size:12px;line-height:1;font-family:Arial,Helvetica,sans-serif;letter-spacing:0.6px;text-transform:uppercase;text-decoration:none;border-radius:8px;">{{ __('mail.manufacturer_activation_reminder.cta') }}</a>
                                    </td>
                                </tr>
                            </table>
                            <a href="{{ $detailsUrl }}"
                                style="display:block;margin-top:10px;font-weight:600;font-size:12.5px;line-height:1;font-family:Arial,Helvetica,sans-serif;color:#B4B4B4;text-decoration:none;">{{ __('mail.manufacturer_activation_reminder.cta_secondary') }}</a>
                            <div
                                style="font-weight:400;font-size:12px;line-height:1.75;font-family:Arial,Helvetica,sans-serif;color:#8A8A8A;margin-top:18px;padding-top:18px;border-top:1px solid #F0F0F0;">
                                {!! __('mail.manufacturer_activation_reminder.cta_note', ['closeUrl' => e($closeAccountUrl)]) !!}
                            </div>
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
                                        {{ __('mail.manufacturer_activation_reminder.footer_tag') }}</td>
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
