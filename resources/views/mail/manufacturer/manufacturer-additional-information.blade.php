@php
    $platformName = config('app.name', 'SourceNest');
    $logoUrl = public_url('images/mail/sourcenest-logo.png');
    $frontendUrl = rtrim((string) config('app.frontend_url', config('app.url')), '/');
    $supportEmail = config('mail.from.address', 'support@sourcenest.com');
    $manufacturerName =
        trim($manufacturerName ?? ($name ?? '')) !== '' ? trim($manufacturerName ?? ($name ?? '')) : 'there';
    $companyName =
        trim($companyName ?? ($company ?? '')) !== '' ? trim($companyName ?? ($company ?? '')) : $platformName;
    $adminMessage = trim($adminMessage ?? '');
    $allowedTypes = is_array($allowedTypes ?? null) ? array_values($allowedTypes) : [];
    $submissionUrl = $submissionUrl ?? ($ctaUrl ?? \App\Support\Mail\MailNotificationHelper::frontendUrl('review'));
    $requestedAt = $requestedAt ?? ($submittedAt ?? now()->format('F j, Y'));
    $expiresAt = $expiresAt ?? now()->addDays(7)->format('F j, Y');
    $referenceId = $referenceId ?? 'SN-MFR-000000';
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
    <title>{{ __('mail.manufacturer_additional_information.subject') }}</title>
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
        style="display:none !important;visibility:hidden;mso-hide:all;font-size:1px;color:#F0F0F0;line-height:1px;max-height:0;max-width:0;opacity:0;overflow:hidden;">{{ __('mail.manufacturer_additional_information.preheader') }}</span>

    <table role="presentation" width="100%" cellspacing="0" cellpadding="0" border="0"
        style="background-color:#F0F0F0;">
        <tr>
            <td align="center" style="padding:24px 12px;">
                <table role="presentation" width="600" cellspacing="0" cellpadding="0" border="0"
                    style="max-width:600px;width:100%;background-color:#FFFFFF;border-radius:14px;overflow:hidden;">

                    {{-- Header D: accent bar + logo + badge --}}
                    <tr>
                        <td style="padding:0;border-bottom:1.5px solid #F0F0F0;">
                            <table role="presentation" width="100%" cellspacing="0" cellpadding="0" border="0">
                                <tr>
                                    <td width="4" bgcolor="#9A7A3A"
                                        style="width:4px;background-color:#9A7A3A;font-size:0;line-height:0;">&nbsp;
                                    </td>
                                    <td bgcolor="#FFFFFF" style="padding:20px 26px;background-color:#FFFFFF;">
                                        <table role="presentation" width="100%" cellspacing="0" cellpadding="0"
                                            border="0">
                                            <tr>
                                                <td align="left" valign="middle">
                                                    @if (!empty($logoUrl))
                                                        <img src="{{ $logoUrl }}" alt="{{ $platformName }}"
                                                            width="140"
                                                            style="display:block;height:auto;max-height:68px;width:auto;border:0;outline:none;text-decoration:none;">
                                                    @else
                                                        <div
                                                            style="font-weight:900;font-size:21px;line-height:1;font-family:Arial,Helvetica,sans-serif;color:#3B2800;letter-spacing:-0.6px;">
                                                            sourcenest</div>
                                                    @endif
                                                </td>
                                                <td align="right" valign="middle">
                                                    <span
                                                        style="display:inline-block;padding:4px 12px;border-radius:20px;border:1.5px solid #E6E6E6;background-color:#F8F8F8;font-weight:700;font-size:9px;line-height:1;font-family:Arial,Helvetica,sans-serif;letter-spacing:0.4px;text-transform:uppercase;color:#8A8A8A;">{{ __('mail.manufacturer_additional_information.badge') }}</span>
                                                </td>
                                            </tr>
                                        </table>
                                    </td>
                                </tr>
                            </table>
                        </td>
                    </tr>

                    {{-- Hero H3 --}}
                    <tr>
                        <td bgcolor="#F8F8F8"
                            style="padding:26px 30px 24px;background-color:#F8F8F8;border-bottom:1.5px solid #E6E6E6;">
                            <table role="presentation" cellspacing="0" cellpadding="0" border="0"
                                style="margin-bottom:14px;border-collapse:separate;border-spacing:0;">
                                <tr>
                                    <td
                                        style="padding:4px 11px;border-radius:20px;border:1.5px solid #E8D5A8;background-color:#FBF7EE;">
                                        <table role="presentation" cellspacing="0" cellpadding="0" border="0">
                                            <tr>
                                                <td width="5" valign="middle" style="line-height:0;font-size:0;">
                                                    <span
                                                        style="display:block;width:5px;height:5px;border-radius:50%;background-color:#9A7A3A;">&nbsp;</span>
                                                </td>
                                                <td valign="middle"
                                                    style="padding-left:5px;font-weight:800;font-size:8.5px;line-height:1;font-family:Arial,Helvetica,sans-serif;letter-spacing:1.2px;text-transform:uppercase;color:#666666;">
                                                    {{ __('mail.manufacturer_additional_information.pill') }}</td>
                                            </tr>
                                        </table>
                                    </td>
                                </tr>
                            </table>
                            <div
                                style="font-weight:500;font-size:25px;line-height:1.17;font-family:Georgia,'Times New Roman',serif;color:#1C1C1C;letter-spacing:-0.2px;">
                                {!! __('mail.manufacturer_additional_information.hero_headline') !!}
                            </div>
                            <div
                                style="padding-top:12px;font-weight:400;font-size:13px;line-height:1.78;font-family:Arial,Helvetica,sans-serif;color:#8A8A8A;">
                                {{ __('mail.manufacturer_additional_information.hero_subheadline') }}
                            </div>
                        </td>
                    </tr>

                    {{-- Greeting + status --}}
                    <tr>
                        <td bgcolor="#FFFFFF"
                            style="padding:28px 30px;background-color:#FFFFFF;border-bottom:1px solid #F0F0F0;">
                            <div
                                style="font-weight:500;font-size:17px;line-height:1;font-family:Georgia,'Times New Roman',serif;color:#3B2800;margin-bottom:13px;">
                                {{ __('mail.manufacturer_additional_information.greeting', ['name' => $manufacturerName]) }}
                            </div>
                            <p
                                style="margin:0;font-weight:400;font-size:13.5px;line-height:1.88;font-family:Arial,Helvetica,sans-serif;color:#464646;">
                                {!! __('mail.manufacturer_additional_information.intro', ['company' => e($companyName)]) !!}
                            </p>

                            <table role="presentation" width="100%" cellspacing="0" cellpadding="0" border="0"
                                style="margin-top:16px;background-color:#EEF3FF;border:1.5px solid #90B4F0;border-radius:8px;border-collapse:separate;">
                                <tr>
                                    <td style="padding:12px 15px;">
                                        <table role="presentation" width="100%" cellspacing="0" cellpadding="0"
                                            border="0">
                                            <tr>
                                                <td valign="middle">
                                                    <table role="presentation" cellspacing="0" cellpadding="0"
                                                        border="0">
                                                        <tr>
                                                            <td width="7" valign="middle"
                                                                style="line-height:0;font-size:0;padding-right:8px;">
                                                                <span
                                                                    style="display:block;width:7px;height:7px;border-radius:50%;background-color:#1464C8;">&nbsp;</span>
                                                            </td>
                                                            <td valign="middle"
                                                                style="font-weight:700;font-size:12.5px;line-height:1;font-family:Arial,Helvetica,sans-serif;color:#1C1C1C;">
                                                                {{ __('mail.manufacturer_additional_information.status_label') }}
                                                            </td>
                                                        </tr>
                                                    </table>
                                                </td>
                                                <td align="right" valign="middle"
                                                    style="font-weight:500;font-size:11px;line-height:1;font-family:Arial,Helvetica,sans-serif;color:#8A8A8A;">
                                                    {{ __('mail.manufacturer_additional_information.status_since', ['date' => $requestedAt]) }}
                                                </td>
                                            </tr>
                                        </table>
                                    </td>
                                </tr>
                            </table>

                            <table role="presentation" width="100%" cellspacing="0" cellpadding="0"
                                border="0"
                                style="margin-top:16px;background-color:#EEF3FF;border:1.5px solid #90B4F0;border-left:4px solid #0C3C70;border-radius:8px;border-collapse:separate;">
                                <tr>
                                    <td style="padding:14px 16px;">
                                        <div
                                            style="font-weight:900;font-size:8.5px;line-height:1;font-family:Arial,Helvetica,sans-serif;letter-spacing:1.6px;text-transform:uppercase;color:#0C3C70;margin-bottom:5px;">
                                            {{ __('mail.manufacturer_additional_information.alert_tag') }}</div>
                                        <div
                                            style="font-weight:400;font-size:13px;line-height:1.65;font-family:Arial,Helvetica,sans-serif;color:#464646;">
                                            {!! __('mail.manufacturer_additional_information.alert_body') !!}</div>
                                    </td>
                                </tr>
                            </table>

                            <table role="presentation" width="100%" cellspacing="0" cellpadding="0"
                                border="0" style="margin-top:12px;">
                                <tr>
                                    <td
                                        style="font-weight:500;font-size:11px;line-height:1.5;font-family:Arial,Helvetica,sans-serif;color:#8A8A8A;">
                                        {{ __('mail.manufacturer_additional_information.alert_meta', ['date' => $requestedAt, 'reference' => $referenceId]) }}
                                    </td>
                                </tr>
                            </table>
                        </td>
                    </tr>

                    @if ($adminMessage !== '')
                        {{-- Admin message --}}
                        <tr>
                            <td bgcolor="#FBF7EE"
                                style="padding:28px 30px;background-color:#FBF7EE;border-bottom:1px solid #F0F0F0;">
                                <table role="presentation" width="100%" cellspacing="0" cellpadding="0"
                                    border="0" style="margin-bottom:14px;border-collapse:separate;">
                                    <tr>
                                        <td width="3" bgcolor="#E8D5A8"
                                            style="width:3px;background-color:#E8D5A8;border-radius:2px;font-size:0;line-height:0;">
                                            &nbsp;</td>
                                        <td
                                            style="padding-left:9px;font-weight:500;font-size:17px;line-height:1;font-family:Georgia,'Times New Roman',serif;color:#3B2800;">
                                            {{ __('mail.manufacturer_additional_information.admin_message_heading') }}
                                        </td>
                                    </tr>
                                </table>
                                <table role="presentation" width="100%" cellspacing="0" cellpadding="0"
                                    border="0"
                                    style="background-color:#FFFFFF;border:1.5px solid #E8D5A8;border-radius:10px;border-collapse:separate;">
                                    <tr>
                                        <td
                                            style="padding:17px 19px;font-weight:400;font-size:13.5px;line-height:1.8;font-family:Arial,Helvetica,sans-serif;color:#464646;">
                                            {{ $adminMessage }}
                                        </td>
                                    </tr>
                                </table>
                            </td>
                        </tr>
                    @endif

                    @if (count($allowedTypes) > 0)
                        {{-- Requested information types --}}
                        <tr>
                            <td bgcolor="#F8F8F8"
                                style="padding:28px 30px;background-color:#F8F8F8;border-bottom:1px solid #F0F0F0;">
                                <table role="presentation" width="100%" cellspacing="0" cellpadding="0"
                                    border="0" style="margin-bottom:18px;border-collapse:separate;">
                                    <tr>
                                        <td width="3" bgcolor="#E8D5A8"
                                            style="width:3px;background-color:#E8D5A8;border-radius:2px;font-size:0;line-height:0;">
                                            &nbsp;</td>
                                        <td
                                            style="padding-left:9px;font-weight:500;font-size:17px;line-height:1;font-family:Georgia,'Times New Roman',serif;color:#3B2800;">
                                            {!! __('mail.manufacturer_additional_information.allowed_types_heading') !!}
                                        </td>
                                    </tr>
                                </table>

                                <table role="presentation" width="100%" cellspacing="0" cellpadding="0"
                                    border="0"
                                    style="border:1.5px solid #E6E6E6;border-radius:10px;border-collapse:separate;overflow:hidden;">
                                    @foreach ($allowedTypes as $type)
                                        <tr>
                                            <td bgcolor="#FFFFFF"
                                                style="padding:13px 15px;background-color:#FFFFFF;{{ !$loop->last ? 'border-bottom:1px solid #F0F0F0;' : '' }}">
                                                <table role="presentation" cellspacing="0" cellpadding="0"
                                                    border="0">
                                                    <tr>
                                                        <td width="32" valign="top"
                                                            style="width:32px;padding-top:1px;">
                                                            <table role="presentation" cellspacing="0"
                                                                cellpadding="0" border="0"
                                                                style="border-collapse:separate;border-spacing:0;">
                                                                <tr>
                                                                    <td width="22" height="22" align="center"
                                                                        valign="middle" bgcolor="#FFF8E4"
                                                                        style="width:22px;height:22px;min-width:22px;background-color:#FFF8E4;border:1.5px dashed #F0C040;border-radius:11px;font-size:0;line-height:0;">
                                                                        &nbsp;</td>
                                                                </tr>
                                                            </table>
                                                        </td>
                                                        <td valign="top" style="padding-left:10px;">
                                                            <div
                                                                style="font-weight:700;font-size:13px;line-height:1.3;font-family:Arial,Helvetica,sans-serif;color:#1C1C1C;margin-bottom:3px;">
                                                                {{ $type }}</div>
                                                            <div
                                                                style="font-weight:400;font-size:12px;line-height:1.55;font-family:Arial,Helvetica,sans-serif;color:#8A8A8A;">
                                                                {{ __('mail.manufacturer_additional_information.allowed_type_hint') }}
                                                            </div>
                                                        </td>
                                                    </tr>
                                                </table>
                                            </td>
                                        </tr>
                                    @endforeach
                                </table>
                            </td>
                        </tr>
                    @endif

                    {{-- CTA --}}
                    <tr>
                        <td bgcolor="#FFFFFF"
                            style="padding:26px 30px 30px;background-color:#FFFFFF;border-top:1px solid #F0F0F0;">
                            <table role="presentation" cellspacing="0" cellpadding="0" border="0">
                                <tr>
                                    <td>
                                        <a href="{{ $submissionUrl }}"
                                            style="display:inline-block;padding:14px 30px;background-color:#9A7A3A;color:#FFFFFF;font-weight:900;font-size:12px;line-height:1;font-family:Arial,Helvetica,sans-serif;letter-spacing:0.6px;text-transform:uppercase;text-decoration:none;border-radius:8px;">{{ __('mail.manufacturer_additional_information.cta') }}</a>
                                    </td>
                                </tr>
                                <tr>
                                    <td style="padding-top:10px;">
                                        <a href="mailto:{{ $supportEmail }}"
                                            style="font-weight:600;font-size:12.5px;line-height:1;font-family:Arial,Helvetica,sans-serif;color:#B4B4B4;text-decoration:none;">{{ __('mail.manufacturer_additional_information.cta_ghost', ['email' => $supportEmail]) }}</a>
                                    </td>
                                </tr>
                                <tr>
                                    <td style="padding-top:12px;">
                                        <div
                                            style="font-weight:500;font-size:11px;line-height:1.5;font-family:Arial,Helvetica,sans-serif;color:#8A8A8A;">
                                            {{ __('mail.manufacturer_additional_information.expires', ['date' => $expiresAt]) }}
                                        </div>
                                    </td>
                                </tr>
                                <tr>
                                    <td style="padding-top:8px;">
                                        <div
                                            style="font-weight:500;font-size:11px;line-height:1.5;font-family:Arial,Helvetica,sans-serif;color:#B4B4B4;">
                                            {{ __('mail.manufacturer_additional_information.cta_link_note') }}
                                            <a href="{{ $submissionUrl }}"
                                                style="color:#9A7A3A;text-decoration:none;word-break:break-all;">{{ $submissionUrl }}</a>
                                        </div>
                                    </td>
                                </tr>
                            </table>
                        </td>
                    </tr>

                    {{-- Footer --}}
                    <tr>
                        <td bgcolor="#F8F8F8"
                            style="padding:18px 30px;background-color:#F8F8F8;border-top:1px solid #E6E6E6;">
                            <table role="presentation" width="100%" cellspacing="0" cellpadding="0"
                                border="0">
                                <tr>
                                    <td
                                        style="font-weight:900;font-size:13px;line-height:1;font-family:Arial,Helvetica,sans-serif;color:#3B2800;letter-spacing:-0.4px;">
                                        sourcenest</td>
                                    <td align="right"
                                        style="font-weight:700;font-size:8px;line-height:1;font-family:Arial,Helvetica,sans-serif;letter-spacing:0.8px;text-transform:uppercase;color:#B4B4B4;">
                                        {{ __('mail.manufacturer_additional_information.footer_tag') }}</td>
                                </tr>
                            </table>
                            <table role="presentation" width="100%" cellspacing="0" cellpadding="0"
                                border="0" style="margin:10px 0;">
                                <tr>
                                    <td height="1" bgcolor="#E6E6E6"
                                        style="height:1px;background-color:#E6E6E6;font-size:0;line-height:0;">&nbsp;
                                    </td>
                                </tr>
                            </table>
                            <p
                                style="margin:0 0 10px 0;font-weight:400;font-size:11px;line-height:1.6;font-family:Arial,Helvetica,sans-serif;color:#8A8A8A;">
                                {{ __('mail.manufacturer_additional_information.footer') }}
                            </p>
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
