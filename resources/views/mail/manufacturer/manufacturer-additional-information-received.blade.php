@php
    $platformName = config('app.name', 'SourceNest');
    $logoUrl = public_url('images/mail/sourcenest-logo.png');
    $checkIconUrl = public_url('images/mail/svg/check-success.svg');
    $frontendUrl = rtrim((string) config('app.frontend_url', config('app.url')), '/');
    $manufacturerName = trim($manufacturerName ?? ($name ?? '')) !== ''
        ? trim($manufacturerName ?? ($name ?? ''))
        : 'there';
    $companyName = trim($companyName ?? ($company ?? '')) !== ''
        ? trim($companyName ?? ($company ?? ''))
        : $platformName;
    $submittedAt = $submittedAt ?? now()->format('F j, Y g:i A');
    $responseCount = (int) ($responseCount ?? 0);
    $referenceId = trim($referenceId ?? '');
    $dashboardUrl = $dashboardUrl
        ?? \App\Support\Mail\MailNotificationHelper::frontendUrl('dashboard/manufacturer');
@endphp
<!DOCTYPE html>
<html lang="en" xmlns="http://www.w3.org/1999/xhtml"
    xmlns:v="urn:schemas-microsoft-com:vml"
    xmlns:o="urn:schemas-microsoft-com:office:office">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <meta name="x-apple-disable-message-reformatting">
    <meta name="format-detection" content="telephone=no,address=no,email=no,date=no,url=no">
    <meta name="color-scheme" content="light only">
    <meta name="supported-color-schemes" content="light">
    <title>{{ __('mail.manufacturer_additional_information_received.subject') }}</title>
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
        style="display:none !important;visibility:hidden;mso-hide:all;font-size:1px;color:#F0F0F0;line-height:1px;max-height:0;max-width:0;opacity:0;overflow:hidden;">{{ __('mail.manufacturer_additional_information_received.preheader') }}</span>

    <table role="presentation" width="100%" cellspacing="0" cellpadding="0" border="0"
        style="background-color:#F0F0F0;">
        <tr>
            <td align="center" style="padding:24px 12px;">
                <table role="presentation" width="600" cellspacing="0" cellpadding="0" border="0"
                    style="max-width:600px;width:100%;background-color:#FFFFFF;border-radius:14px;border-collapse:separate;overflow:hidden;">

                    {{-- Header --}}
                    <tr>
                        <td bgcolor="#FFFFFF"
                            style="padding:0;background-color:#FFFFFF;border-bottom:1.5px solid #F0F0F0;">
                            <table role="presentation" width="100%" cellspacing="0" cellpadding="0" border="0">
                                <tr>
                                    <td width="4" bgcolor="#9A7A3A"
                                        style="width:4px;background-color:#9A7A3A;font-size:0;line-height:0;">&nbsp;</td>
                                    <td style="padding:20px 26px;">
                                        <table role="presentation" width="100%" cellspacing="0" cellpadding="0" border="0">
                                            <tr>
                                                <td align="left" valign="middle">
                                                    @if (!empty($logoUrl))
                                                        <img src="{{ $logoUrl }}" alt="{{ $platformName }}" width="140"
                                                            style="display:block;height:auto;max-height:68px;width:auto;border:0;outline:none;text-decoration:none;">
                                                    @else
                                                        <div
                                                            style="font-weight:900;font-size:21px;line-height:1;font-family:Arial,Helvetica,sans-serif;color:#3B2800;letter-spacing:-0.6px;">
                                                            {{ strtolower($platformName) }}</div>
                                                    @endif
                                                </td>
                                                <td align="right" valign="middle">
                                                    <span
                                                        style="display:inline-block;padding:4px 12px;border-radius:20px;border:1.5px solid #E6E6E6;background-color:#F8F8F8;font-weight:700;font-size:9px;line-height:1;font-family:Arial,Helvetica,sans-serif;letter-spacing:0.4px;text-transform:uppercase;color:#8A8A8A;">{{ __('mail.manufacturer_additional_information_received.badge') }}</span>
                                                </td>
                                            </tr>
                                        </table>
                                    </td>
                                </tr>
                            </table>
                        </td>
                    </tr>

                    {{-- Hero --}}
                    <tr>
                        <td bgcolor="#F8F8F8"
                            style="padding:28px 30px;background-color:#F8F8F8;border-bottom:1.5px solid #E6E6E6;">
                            <table role="presentation" width="100%" cellspacing="0" cellpadding="0" border="0">
                                <tr>
                                    <td width="48" valign="middle" style="width:48px;padding-right:16px;">
                                        <table role="presentation" cellspacing="0" cellpadding="0" border="0"
                                            style="border-collapse:separate;border-spacing:0;">
                                            <tr>
                                                <td width="46" height="46" align="center" valign="middle"
                                                    bgcolor="#EAFAF2"
                                                    style="width:46px;height:46px;min-width:46px;background-color:#EAFAF2;border:1.5px solid #6ECFA0;border-radius:50%;text-align:center;vertical-align:middle;line-height:46px;mso-line-height-rule:exactly;">
                                                    @if (!empty($checkIconUrl))
                                                        <img src="{{ $checkIconUrl }}" width="20" height="20" alt=""
                                                            style="display:block;width:20px;height:20px;margin:0 auto;border:0;outline:none;text-decoration:none;">
                                                    @endif
                                                </td>
                                            </tr>
                                        </table>
                                    </td>
                                    <td valign="middle">
                                        <table role="presentation" cellspacing="0" cellpadding="0" border="0"
                                            style="margin-bottom:7px;border-collapse:separate;border-spacing:0;">
                                            <tr>
                                                <td
                                                    style="padding:4px 11px;border-radius:20px;border:1.5px solid #6ECFA0;background-color:#EAFAF2;">
                                                    <table role="presentation" cellspacing="0" cellpadding="0" border="0">
                                                        <tr>
                                                            <td width="5" valign="middle"
                                                                style="line-height:0;font-size:0;padding-right:5px;">
                                                                <span
                                                                    style="display:block;width:5px;height:5px;border-radius:50%;background-color:#0E8A4A;">&nbsp;</span>
                                                            </td>
                                                            <td valign="middle"
                                                                style="font-weight:800;font-size:8.5px;line-height:1;font-family:Arial,Helvetica,sans-serif;letter-spacing:1.2px;text-transform:uppercase;color:#0A5C32;">
                                                                {{ __('mail.manufacturer_additional_information_received.pill') }}
                                                            </td>
                                                        </tr>
                                                    </table>
                                                </td>
                                            </tr>
                                        </table>
                                        <div
                                            style="font-weight:500;font-size:24px;line-height:1.17;font-family:Georgia,'Times New Roman',serif;color:#1C1C1C;letter-spacing:-0.2px;">
                                            {!! __('mail.manufacturer_additional_information_received.hero_headline') !!}
                                        </div>
                                        <div
                                            style="padding-top:6px;font-weight:400;font-size:13px;line-height:1.7;font-family:Arial,Helvetica,sans-serif;color:#8A8A8A;">
                                            {{ __('mail.manufacturer_additional_information_received.hero_subheadline') }}
                                        </div>
                                    </td>
                                </tr>
                            </table>
                        </td>
                    </tr>

                    {{-- Confirmation --}}
                    <tr>
                        <td bgcolor="#FFFFFF"
                            style="padding:28px 30px;background-color:#FFFFFF;border-bottom:1px solid #F0F0F0;">
                            <div
                                style="font-weight:500;font-size:17px;line-height:1;font-family:Georgia,'Times New Roman',serif;color:#3B2800;margin-bottom:13px;">
                                {{ __('mail.manufacturer_additional_information_received.greeting', ['name' => $manufacturerName]) }}
                            </div>
                            <p
                                style="margin:0;font-weight:400;font-size:13.5px;line-height:1.88;font-family:Arial,Helvetica,sans-serif;color:#464646;">
                                {!! __('mail.manufacturer_additional_information_received.intro', ['company' => e($companyName)]) !!}
                            </p>

                            <table role="presentation" width="100%" cellspacing="0" cellpadding="0" border="0"
                                style="margin-top:20px;border:1.5px solid #E6E6E6;border-radius:10px;border-collapse:separate;overflow:hidden;">
                                <tr>
                                    <td bgcolor="#F8F8F8"
                                        style="padding:11px 16px;background-color:#F8F8F8;border-bottom:1px solid #E6E6E6;font-weight:900;font-size:9px;line-height:1;font-family:Arial,Helvetica,sans-serif;letter-spacing:1.2px;text-transform:uppercase;color:#8A8A8A;">
                                        {{ __('mail.manufacturer_additional_information_received.summary_heading') }}
                                    </td>
                                </tr>
                                <tr>
                                    <td style="padding:0;">
                                        <table role="presentation" width="100%" cellspacing="0" cellpadding="0" border="0">
                                            <tr>
                                                <td width="130" bgcolor="#F8F8F8"
                                                    style="width:130px;padding:11px 16px;background-color:#F8F8F8;border-right:1px solid #F0F0F0;font-weight:700;font-size:11px;line-height:1.4;font-family:Arial,Helvetica,sans-serif;color:#8A8A8A;">
                                                    {{ __('mail.manufacturer_additional_information_received.submitted') }}
                                                </td>
                                                <td
                                                    style="padding:11px 16px;font-weight:500;font-size:12.5px;line-height:1.4;font-family:Arial,Helvetica,sans-serif;color:#1C1C1C;">
                                                    {{ $submittedAt }}
                                                </td>
                                            </tr>
                                        </table>
                                    </td>
                                </tr>
                                <tr>
                                    <td style="padding:0;border-top:1px solid #F0F0F0;">
                                        <table role="presentation" width="100%" cellspacing="0" cellpadding="0" border="0">
                                            <tr>
                                                <td width="130" bgcolor="#F8F8F8"
                                                    style="width:130px;padding:11px 16px;background-color:#F8F8F8;border-right:1px solid #F0F0F0;font-weight:700;font-size:11px;line-height:1.4;font-family:Arial,Helvetica,sans-serif;color:#8A8A8A;">
                                                    {{ __('mail.manufacturer_additional_information_received.response_count') }}
                                                </td>
                                                <td
                                                    style="padding:11px 16px;font-weight:500;font-size:12.5px;line-height:1.4;font-family:Arial,Helvetica,sans-serif;color:#1C1C1C;">
                                                    {{ $responseCount }}
                                                </td>
                                            </tr>
                                        </table>
                                    </td>
                                </tr>
                                @if ($referenceId !== '')
                                    <tr>
                                        <td style="padding:0;border-top:1px solid #F0F0F0;">
                                            <table role="presentation" width="100%" cellspacing="0" cellpadding="0" border="0">
                                                <tr>
                                                    <td width="130" bgcolor="#F8F8F8"
                                                        style="width:130px;padding:11px 16px;background-color:#F8F8F8;border-right:1px solid #F0F0F0;font-weight:700;font-size:11px;line-height:1.4;font-family:Arial,Helvetica,sans-serif;color:#8A8A8A;">
                                                        {{ __('mail.manufacturer_additional_information_received.reference_label') }}
                                                    </td>
                                                    <td
                                                        style="padding:11px 16px;font-weight:500;font-size:12px;line-height:1.4;font-family:'Courier New',Courier,monospace;color:#1C1C1C;">
                                                        {{ $referenceId }}
                                                    </td>
                                                </tr>
                                            </table>
                                        </td>
                                    </tr>
                                @endif
                            </table>

                            <table role="presentation" width="100%" cellspacing="0" cellpadding="0" border="0"
                                style="margin-top:16px;background-color:#FBF7EE;border:1.5px solid #E8D5A8;border-left:4px solid #9A7A3A;border-radius:8px;border-collapse:separate;">
                                <tr>
                                    <td style="padding:14px 16px;">
                                        <div
                                            style="font-weight:900;font-size:8.5px;line-height:1;font-family:Arial,Helvetica,sans-serif;letter-spacing:1.6px;text-transform:uppercase;color:#9A7A3A;margin-bottom:5px;">
                                            {{ __('mail.manufacturer_additional_information_received.next_step_heading') }}
                                        </div>
                                        <div
                                            style="font-weight:400;font-size:13px;line-height:1.65;font-family:Arial,Helvetica,sans-serif;color:#464646;">
                                            {{ __('mail.manufacturer_additional_information_received.next_step_body') }}
                                        </div>
                                    </td>
                                </tr>
                            </table>
                        </td>
                    </tr>

                    {{-- CTA --}}
                    <tr>
                        <td bgcolor="#FFFFFF" style="padding:26px 30px 30px;background-color:#FFFFFF;">
                            <a href="{{ $dashboardUrl }}"
                                style="display:inline-block;padding:14px 30px;background-color:#3B2800;color:#FFFFFF;font-weight:900;font-size:12px;line-height:1;font-family:Arial,Helvetica,sans-serif;letter-spacing:0.6px;text-transform:uppercase;text-decoration:none;border-radius:8px;">{{ __('mail.manufacturer_additional_information_received.cta') }}</a>
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
                                        {{ strtolower($platformName) }}</td>
                                    <td align="right"
                                        style="font-weight:700;font-size:8px;line-height:1;font-family:Arial,Helvetica,sans-serif;letter-spacing:0.8px;text-transform:uppercase;color:#B4B4B4;">
                                        {{ __('mail.manufacturer_additional_information_received.footer_tag') }}</td>
                                </tr>
                            </table>
                            <table role="presentation" width="100%" cellspacing="0" cellpadding="0" border="0"
                                style="margin:10px 0;">
                                <tr>
                                    <td height="1" bgcolor="#E6E6E6"
                                        style="height:1px;background-color:#E6E6E6;font-size:0;line-height:0;">&nbsp;</td>
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
