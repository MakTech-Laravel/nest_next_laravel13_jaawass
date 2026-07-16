@php
    $platformName = config('app.name', 'SourceNest');
    $logoUrl = public_url('images/mail/sourcenest-logo.png');
    $frontendUrl = rtrim((string) config('app.frontend_url', config('app.url')), '/');
    $supportEmail = config('mail.from.address', 'support@sourcenest.com');
    $recipientName = trim($recipientName ?? '') !== '' ? trim($recipientName) : 'there';
    $ttlMinutes = (int) ($ttlMinutes ?? config('account.password_reset_otp_ttl_minutes', 15));
    $expiresIn = $expiresIn ?? ($ttlMinutes.' '.($ttlMinutes === 1 ? 'minute' : 'minutes'));
    $formattedOtp = $formattedOtp ?? preg_replace('/(\d{3})(?=\d)/', '$1 ', trim((string) ($otp ?? '')));
    $ctaUrl = $ctaUrl ?? \App\Support\Mail\MailNotificationHelper::frontendUrl('auth/restore-account');
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
    <title>{{ __('mail.password_reset_otp.subject') }}</title>
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
    style="margin:0;padding:0;background-color:#C8C2B6;font-family:Arial,Helvetica,sans-serif;-webkit-text-size-adjust:100%;-ms-text-size-adjust:100%;">
    <span
        style="display:none !important;visibility:hidden;mso-hide:all;font-size:1px;color:#C8C2B6;line-height:1px;max-height:0;max-width:0;opacity:0;overflow:hidden;">{{ __('mail.password_reset_otp.preheader') }}</span>

    <table role="presentation" width="100%" cellspacing="0" cellpadding="0" border="0"
        style="background-color:#C8C2B6;">
        <tr>
            <td align="center" style="padding:32px 16px;">
                <table role="presentation" width="600" cellspacing="0" cellpadding="0" border="0"
                    style="max-width:600px;width:100%;background-color:#FFFFFF;border-radius:14px;overflow:hidden;">

                    {{-- Header C --}}
                    <tr>
                        <td align="center" bgcolor="#FBF7EE"
                            style="padding:22px 30px 18px;background-color:#FBF7EE;border-bottom:1.5px solid #E8D5A8;">
                            @if (!empty($logoUrl))
                                <img src="{{ $logoUrl }}" alt="{{ $platformName }}" width="180"
                                    style="display:block;height:auto;max-height:80px;width:auto;margin:0 auto;border:0;outline:none;text-decoration:none;">
                            @else
                                <div
                                    style="font-weight:900;font-size:21px;line-height:1;font-family:Arial,Helvetica,sans-serif;color:#3B2800;letter-spacing:-0.6px;">
                                    sourcenest</div>
                            @endif
                            <div
                                style="font-weight:800;font-size:8.5px;line-height:1;font-family:Arial,Helvetica,sans-serif;letter-spacing:1.8px;text-transform:uppercase;color:#9A7A3A;margin-top:10px;">
                                {{ __('mail.password_reset_otp.context_label') }}</div>
                        </td>
                    </tr>

                    {{-- Hero H4 --}}
                    <tr>
                        <td align="center" bgcolor="#3B2800"
                            style="padding:40px 30px 42px;background-color:#3B2800;border-bottom:1.5px solid #5C3D10;">
                            <table role="presentation" cellspacing="0" cellpadding="0" border="0" align="center"
                                style="margin:0 auto 20px;">
                                <tr>
                                    <td align="center" width="54" height="54" bgcolor="#4A3210"
                                        style="width:54px;height:54px;background-color:rgba(200,169,106,0.1);border:1.5px solid rgba(200,169,106,0.22);border-radius:12px;text-align:center;vertical-align:middle;">
                                        <img src="{{ public_url('images/mail/svg/lock-gold.svg') }}" width="24"
                                            height="24" alt=""
                                            style="display:block;border:0;outline:none;text-decoration:none;margin:0 auto;">
                                    </td>
                                </tr>
                            </table>

                            <table role="presentation" cellspacing="0" cellpadding="0" border="0" align="center"
                                style="margin:0 auto 13px;">
                                <tr>
                                    <td width="20" valign="middle" style="line-height:0;font-size:0;">
                                        <span
                                            style="display:block;width:20px;height:2px;border-radius:1px;background-color:rgba(200,169,106,0.4);">&nbsp;</span>
                                    </td>
                                    <td valign="middle"
                                        style="padding:0 8px;font-weight:800;font-size:8.5px;line-height:1;font-family:Arial,Helvetica,sans-serif;letter-spacing:2px;text-transform:uppercase;color:rgba(200,169,106,0.7);">
                                        {{ __('mail.password_reset_otp.hero_eyebrow') }}</td>
                                    <td width="20" valign="middle" style="line-height:0;font-size:0;">
                                        <span
                                            style="display:block;width:20px;height:2px;border-radius:1px;background-color:rgba(200,169,106,0.4);">&nbsp;</span>
                                    </td>
                                </tr>
                            </table>

                            <div
                                style="font-weight:500;font-size:30px;line-height:1.17;font-family:Georgia,'Times New Roman',serif;color:#FFFFFF;letter-spacing:-0.2px;text-align:center;">
                                {{ __('mail.password_reset_otp.hero_headline_line1') }}<br>
                                <em style="font-style:italic;color:#C8A96A;">{{ __('mail.password_reset_otp.hero_headline_line2') }}</em>
                            </div>
                            <div
                                style="max-width:320px;margin:10px auto 0;font-weight:400;font-size:13px;line-height:1.78;font-family:Arial,Helvetica,sans-serif;color:rgba(255,255,255,0.4);text-align:center;">
                                {{ __('mail.password_reset_otp.hero_subheadline', ['minutes' => $ttlMinutes]) }}</div>
                        </td>
                    </tr>

                    {{-- Main content --}}
                    <tr>
                        <td bgcolor="#FFFFFF"
                            style="padding:28px 30px;background-color:#FFFFFF;border-bottom:1px solid #F0F0F0;">
                            <div
                                style="font-weight:500;font-size:17px;line-height:1;font-family:Georgia,'Times New Roman',serif;color:#3B2800;margin-bottom:13px;">
                                {{ __('mail.password_reset_otp.greeting', ['name' => $recipientName]) }}</div>
                            <p
                                style="margin:0 0 4px 0;font-weight:400;font-size:13.5px;line-height:1.88;font-family:Arial,Helvetica,sans-serif;color:#464646;">
                                {!! __('mail.password_reset_otp.intro', ['minutes' => $ttlMinutes]) !!}</p>

                            <table role="presentation" width="100%" cellspacing="0" cellpadding="0" border="0"
                                style="margin:16px 0 4px;background-color:#F8F8F8;border:2px dashed #E8D5A8;border-radius:12px;border-collapse:separate;">
                                <tr>
                                    <td align="center" style="padding:26px 20px;">
                                        <div
                                            style="font-weight:900;font-size:10px;line-height:1;font-family:Arial,Helvetica,sans-serif;letter-spacing:2px;text-transform:uppercase;color:#9A7A3A;margin-bottom:14px;">
                                            {{ __('mail.password_reset_otp.otp_label') }}</div>
                                        <div
                                            style="font-weight:900;font-size:54px;line-height:1;font-family:Arial,Helvetica,sans-serif;color:#3B2800;letter-spacing:14px;">
                                            {{ $formattedOtp }}</div>
                                        <div
                                            style="font-weight:600;font-size:12px;line-height:1;font-family:Arial,Helvetica,sans-serif;color:#B4B4B4;margin-top:12px;letter-spacing:0.2px;">
                                            {{ __('mail.password_reset_otp.otp_note', ['minutes' => $ttlMinutes]) }}
                                        </div>
                                    </td>
                                </tr>
                            </table>

                            <table role="presentation" cellspacing="0" cellpadding="0" border="0" align="center"
                                style="margin:14px auto 0;">
                                <tr>
                                    <td
                                        style="padding:7px 14px;background-color:#FFFFFF;border:1.5px solid #E6E6E6;border-radius:8px;">
                                        <table role="presentation" cellspacing="0" cellpadding="0" border="0">
                                            <tr>
                                                <td width="22" valign="middle" style="padding-right:8px;">
                                                    <table role="presentation" cellspacing="0" cellpadding="0"
                                                        border="0">
                                                        <tr>
                                                            <td align="center" width="22" height="22"
                                                                bgcolor="#FBF7EE"
                                                                style="width:22px;height:22px;background-color:#FBF7EE;border:1px solid #E8D5A8;border-radius:6px;text-align:center;vertical-align:middle;">
                                                                <img src="{{ public_url('images/mail/svg/clock.svg') }}"
                                                                    width="12" height="12" alt="Clock">
                                                            </td>
                                                        </tr>
                                                    </table>
                                                </td>
                                                <td valign="middle"
                                                    style="font-weight:500;font-size:11px;line-height:1;font-family:Arial,Helvetica,sans-serif;color:#8A8A8A;">
                                                    {{ __('mail.password_reset_otp.expires_in_label') }}</td>
                                                <td valign="middle"
                                                    style="padding-left:8px;font-weight:900;font-size:13px;line-height:1;font-family:Arial,Helvetica,sans-serif;color:#3B2800;">
                                                    {{ $expiresIn }}</td>
                                            </tr>
                                        </table>
                                    </td>
                                </tr>
                            </table>
                        </td>
                    </tr>

                    {{-- Security card --}}
                    <tr>
                        <td bgcolor="#F8F8F8"
                            style="padding:22px 30px;background-color:#F8F8F8;border-bottom:1px solid #F0F0F0;">
                            <table role="presentation" width="100%" cellspacing="0" cellpadding="0" border="0"
                                style="background-color:#FFFFFF;border:1.5px solid #E6E6E6;border-radius:10px;border-collapse:separate;">
                                <tr>
                                    <td style="padding:14px 16px;border-bottom:1px solid #F0F0F0;">
                                        <table role="presentation" cellspacing="0" cellpadding="0" border="0">
                                            <tr>
                                                <td width="22" valign="middle" style="padding-right:8px;">
                                                    <table role="presentation" cellspacing="0" cellpadding="0"
                                                        border="0">
                                                        <tr>
                                                            <td align="center" width="22" height="22"
                                                                bgcolor="#FFFFFF"
                                                                style="width:22px;height:22px;background-color:#FFFFFF;border:1.5px solid #E6E6E6;border-radius:6px;text-align:center;vertical-align:middle;">
                                                                <img src="{{ public_url('images/mail/svg/shield.svg') }}"
                                                                    width="10" height="10" alt="">
                                                            </td>
                                                        </tr>
                                                    </table>
                                                </td>
                                                <td valign="middle"
                                                    style="font-weight:800;font-size:11px;line-height:1;font-family:Arial,Helvetica,sans-serif;letter-spacing:0.3px;color:#666666;">
                                                    {{ __('mail.password_reset_otp.security_heading') }}</td>
                                            </tr>
                                        </table>
                                    </td>
                                </tr>
                                @foreach ([
                                    __('mail.password_reset_otp.security_row_1'),
                                    __('mail.password_reset_otp.security_row_2', ['minutes' => $ttlMinutes]),
                                    __('mail.password_reset_otp.security_row_3', ['email' => $supportEmail]),
                                ] as $securityRow)
                                    <tr>
                                        <td style="padding:12px 16px;border-top:1px solid #F5F5F5;">
                                            <table role="presentation" cellspacing="0" cellpadding="0" border="0">
                                                <tr>
                                                    <td width="14" valign="top" style="padding-top:5px;">
                                                        <span
                                                            style="display:block;width:5px;height:5px;border-radius:50%;background-color:#C8A96A;">&nbsp;</span>
                                                    </td>
                                                    <td valign="top"
                                                        style="font-weight:400;font-size:12.5px;line-height:1.6;font-family:Arial,Helvetica,sans-serif;color:#666666;">
                                                        {!! $securityRow !!}</td>
                                                </tr>
                                            </table>
                                        </td>
                                    </tr>
                                @endforeach
                            </table>

                            <table role="presentation" width="100%" cellspacing="0" cellpadding="0" border="0"
                                style="margin-top:16px;background-color:#F8F8F8;border:1.5px solid #E6E6E6;border-radius:8px;border-collapse:separate;">
                                <tr>
                                    <td style="padding:13px 15px;">
                                        <table role="presentation" cellspacing="0" cellpadding="0" border="0">
                                            <tr>
                                                <td width="26" valign="top" style="padding-right:11px;">
                                                    <table role="presentation" cellspacing="0" cellpadding="0"
                                                        border="0">
                                                        <tr>
                                                            <td align="center" width="26" height="26"
                                                                bgcolor="#FFFFFF"
                                                                style="width:26px;height:26px;background-color:#FFFFFF;border:1.5px solid #D6D6D6;border-radius:7px;text-align:center;vertical-align:middle;">
                                                                <img src="{{ public_url('images/mail/svg/info.svg') }}"
                                                                    width="12" height="12" alt="">
                                                            </td>
                                                        </tr>
                                                    </table>
                                                </td>
                                                <td valign="top"
                                                    style="font-weight:400;font-size:12px;line-height:1.65;font-family:Arial,Helvetica,sans-serif;color:#8A8A8A;">
                                                    {!! __('mail.password_reset_otp.alert_body', ['email' => $supportEmail]) !!}
                                                </td>
                                            </tr>
                                        </table>
                                    </td>
                                </tr>
                            </table>
                        </td>
                    </tr>

                    {{-- CTA --}}
                    <tr>
                        <td align="center" bgcolor="#FFFFFF"
                            style="padding:26px 30px 30px;background-color:#FFFFFF;border-top:1px solid #F0F0F0;">
                            <table role="presentation" cellspacing="0" cellpadding="0" border="0">
                                <tr>
                                    <td>
                                        <a href="{{ $ctaUrl }}"
                                            style="display:inline-block;padding:14px 30px;background-color:#3B2800;color:#FFFFFF;font-weight:900;font-size:12px;line-height:1;font-family:Arial,Helvetica,sans-serif;letter-spacing:0.6px;text-transform:uppercase;text-decoration:none;border-radius:8px;">{{ __('mail.password_reset_otp.cta') }}</a>
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
                                        {{ __('mail.password_reset_otp.footer_tag') }}</td>
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
                                <span style="margin:0 5px;font-size:9px;color:#E6E6E6;">·</span>
                                <a href="mailto:{{ $supportEmail }}"
                                    style="color:#B4B4B4;text-decoration:none;">Support</a>
                            </span>
                        </td>
                    </tr>

                </table>
            </td>
        </tr>
    </table>
</body>

</html>
