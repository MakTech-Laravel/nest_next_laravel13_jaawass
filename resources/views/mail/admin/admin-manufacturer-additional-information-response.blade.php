@php
    $platformName = config('app.name', 'SourceNest');
    $logoUrl = public_url('images/mail/sourcenest-logo.png');
    $frontendUrl = rtrim((string) config('app.frontend_url', config('app.url')), '/');
    $userIconUrl = public_url('images/mail/svg/inbox.svg');
    $adminName = trim($adminName ?? '') !== '' ? trim($adminName) : 'there';
    $manufacturerName = trim($manufacturerName ?? '') !== '' ? trim($manufacturerName) : 'there';
    $companyName = trim($companyName ?? '') !== '' ? trim($companyName) : $platformName;
    $referenceId = trim($referenceId ?? '');
    $submittedAt = $submittedAt ?? now()->format('F j, Y g:i A');
    $responseCount = (int) ($responseCount ?? (is_array($responses ?? null) ? count($responses) : 0));
    $responses = is_array($responses ?? null) ? $responses : [];
    $reviewUrl = $reviewUrl ?? \App\Support\Mail\MailNotificationHelper::frontendUrl('admin/manufacturer-registrations');
    $adminPanelUrl = \App\Support\Mail\MailNotificationHelper::frontendUrl('admin');
    $allRegistrationsUrl = \App\Support\Mail\MailNotificationHelper::frontendUrl('admin/manufacturer-registrations');
    $heroHeadline = str_replace(
        '<em>',
        '<em style="font-style:italic;color:#9A7A3A;">',
        (string) __('mail.admin_manufacturer_additional_information_response.hero_headline')
    );
    $detailsRows = array_filter([
        __('mail.admin_manufacturer_additional_information_response.company') => $companyName,
        __('mail.admin_manufacturer_additional_information_response.contact') => $manufacturerName,
        __('mail.admin_manufacturer_additional_information_response.submitted') => $submittedAt,
        __('mail.admin_manufacturer_additional_information_response.response_count') => (string) $responseCount,
        __('mail.admin_manufacturer_additional_information_response.reference_label') => $referenceId,
    ], fn ($value) => $value !== null && $value !== '');
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
    <title>{{ __('mail.admin_manufacturer_additional_information_response.subject') }}</title>
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
        style="display:none !important;visibility:hidden;mso-hide:all;font-size:1px;color:#F0F0F0;line-height:1px;max-height:0;max-width:0;opacity:0;overflow:hidden;">{{ __('mail.admin_manufacturer_additional_information_response.preheader') }}</span>

    <table role="presentation" width="100%" cellspacing="0" cellpadding="0" border="0"
        style="background-color:#F0F0F0;">
        <tr>
            <td align="center" style="padding:24px 12px;">
                <table role="presentation" width="600" cellspacing="0" cellpadding="0" border="0"
                    style="max-width:600px;width:100%;background-color:#FFFFFF;border-radius:14px;overflow:hidden;">

                    {{-- Header: accent bar + logo + badge --}}
                    <tr>
                        <td bgcolor="#FFFFFF"
                            style="padding:0;background-color:#FFFFFF;border-bottom:1.5px solid #F0F0F0;">
                            <table role="presentation" width="100%" cellspacing="0" cellpadding="0" border="0">
                                <tr>
                                    <td width="4" bgcolor="#9A7A3A"
                                        style="width:4px;background-color:#9A7A3A;font-size:0;line-height:0;">&nbsp;
                                    </td>
                                    <td style="padding:20px 26px;">
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
                                                            {{ strtolower($platformName) }}</div>
                                                    @endif
                                                </td>
                                                <td align="right" valign="middle">
                                                    <table role="presentation" cellspacing="0" cellpadding="0"
                                                        border="0"
                                                        style="border-collapse:separate;border-spacing:0;">
                                                        <tr>
                                                            <td
                                                                style="padding:4px 11px;border-radius:6px;border:1.5px solid #F0C080;background-color:#FFF5E6;">
                                                                <table role="presentation" cellspacing="0"
                                                                    cellpadding="0" border="0">
                                                                    <tr>
                                                                        <td width="5" valign="middle"
                                                                            style="line-height:0;font-size:0;padding-right:5px;">
                                                                            <span
                                                                                style="display:block;width:5px;height:5px;border-radius:50%;background-color:#D07800;">&nbsp;</span>
                                                                        </td>
                                                                        <td valign="middle"
                                                                            style="font-weight:700;font-size:9px;line-height:1;font-family:Arial,Helvetica,sans-serif;letter-spacing:0.4px;text-transform:uppercase;color:#8A8A8A;">
                                                                            {{ __('mail.admin_manufacturer_additional_information_response.badge') }}
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
                        </td>
                    </tr>

                    {{-- Hero --}}
                    <tr>
                        <td bgcolor="#F8F8F8"
                            style="padding:22px 30px;background-color:#F8F8F8;border-bottom:1.5px solid #E6E6E6;">
                            <table role="presentation" width="100%" cellspacing="0" cellpadding="0" border="0">
                                <tr>
                                    <td width="44" valign="middle" style="width:44px;padding-right:16px;">
                                        <table role="presentation" cellspacing="0" cellpadding="0" border="0"
                                            style="border-collapse:separate;border-spacing:0;">
                                            <tr>
                                                <td width="44" height="44" align="center" valign="middle"
                                                    bgcolor="#FFFFFF"
                                                    style="width:44px;height:44px;min-width:44px;background-color:#FFFFFF;border:1.5px solid #D6D6D6;border-radius:10px;text-align:center;vertical-align:middle;line-height:44px;mso-line-height-rule:exactly;">
                                                    @if (!empty($userIconUrl))
                                                        <img src="{{ $userIconUrl }}" width="20" height="20"
                                                            alt=""
                                                            style="display:block;width:20px;height:20px;margin:0 auto;border:0;outline:none;text-decoration:none;">
                                                    @endif
                                                </td>
                                            </tr>
                                        </table>
                                    </td>
                                    <td valign="middle">
                                        <table role="presentation" cellspacing="0" cellpadding="0" border="0"
                                            style="margin-bottom:6px;">
                                            <tr>
                                                <td width="20" valign="middle"
                                                    style="line-height:0;font-size:0;padding-right:8px;">
                                                    <span
                                                        style="display:block;width:20px;height:2px;border-radius:1px;background-color:#D6D6D6;">&nbsp;</span>
                                                </td>
                                                <td valign="middle"
                                                    style="font-weight:800;font-size:8.5px;line-height:1;font-family:Arial,Helvetica,sans-serif;letter-spacing:2px;text-transform:uppercase;color:#8A8A8A;">
                                                    {{ __('mail.admin_manufacturer_additional_information_response.eyebrow') }}
                                                </td>
                                            </tr>
                                        </table>
                                        <div
                                            style="font-weight:500;font-size:20px;line-height:1.17;font-family:Georgia,'Times New Roman',serif;color:#1C1C1C;letter-spacing:-0.2px;">
                                            {!! $heroHeadline !!}
                                        </div>
                                        <div
                                            style="padding-top:4px;font-weight:400;font-size:13px;line-height:1.78;font-family:Arial,Helvetica,sans-serif;color:#8A8A8A;">
                                            {{ __('mail.admin_manufacturer_additional_information_response.hero_subheadline') }}
                                        </div>
                                    </td>
                                </tr>
                            </table>
                        </td>
                    </tr>

                    {{-- Submission details --}}
                    <tr>
                        <td bgcolor="#FFFFFF"
                            style="padding:28px 30px;background-color:#FFFFFF;border-bottom:1px solid #F0F0F0;">
                            <table role="presentation" cellspacing="0" cellpadding="0" border="0"
                                style="margin-bottom:18px;border-collapse:separate;">
                                <tr>
                                    <td width="3" bgcolor="#E8D5A8"
                                        style="width:3px;background-color:#E8D5A8;border-radius:2px;font-size:0;line-height:0;">
                                        &nbsp;</td>
                                    <td
                                        style="padding-left:9px;font-weight:500;font-size:17px;line-height:1;font-family:Georgia,'Times New Roman',serif;color:#3B2800;">
                                        {!! __('mail.admin_manufacturer_additional_information_response.details_heading') !!}
                                    </td>
                                </tr>
                            </table>

                            <table role="presentation" width="100%" cellspacing="0" cellpadding="0" border="0"
                                style="border:1.5px solid #E6E6E6;border-radius:10px;border-collapse:separate;">
                                <tr>
                                    <td bgcolor="#F8F8F8"
                                        style="padding:11px 16px;background-color:#F8F8F8;border-bottom:1px solid #E6E6E6;">
                                        <table role="presentation" width="100%" cellspacing="0" cellpadding="0"
                                            border="0">
                                            <tr>
                                                <td
                                                    style="font-weight:900;font-size:9px;line-height:1;font-family:Arial,Helvetica,sans-serif;letter-spacing:1.2px;text-transform:uppercase;color:#8A8A8A;">
                                                    {{ __('mail.admin_manufacturer_additional_information_response.record_title') }}
                                                </td>
                                                <td align="right">
                                                    <span
                                                        style="display:inline-block;padding:2px 10px;border-radius:20px;border:1.5px solid #F0C040;background-color:#FFF8E4;font-weight:800;font-size:9px;line-height:1;font-family:Arial,Helvetica,sans-serif;color:#7A4D00;">{{ __('mail.admin_manufacturer_additional_information_response.status_pending') }}</span>
                                                </td>
                                            </tr>
                                        </table>
                                    </td>
                                </tr>
                                @foreach ($detailsRows as $label => $value)
                                    <tr>
                                        <td style="padding:0;border-top:1px solid #F0F0F0;">
                                            <table role="presentation" width="100%" cellspacing="0"
                                                cellpadding="0" border="0">
                                                <tr>
                                                    <td width="130" bgcolor="#F8F8F8" valign="top"
                                                        style="width:130px;padding:11px 16px;background-color:#F8F8F8;border-right:1px solid #F0F0F0;font-weight:700;font-size:11px;line-height:1.4;font-family:Arial,Helvetica,sans-serif;color:#8A8A8A;">
                                                        {{ $label }}</td>
                                                    <td valign="top"
                                                        style="padding:11px 16px;font-weight:{{ $loop->first ? '800' : '500' }};font-size:{{ $loop->last ? '12px' : '12.5px' }};line-height:1.4;font-family:{{ $loop->last ? 'Courier New,Courier,monospace' : 'Arial,Helvetica,sans-serif' }};color:#1C1C1C;">
                                                        {{ $value }}</td>
                                                </tr>
                                            </table>
                                        </td>
                                    </tr>
                                @endforeach
                            </table>

                            <div
                                style="padding-top:18px;font-weight:400;font-size:13.5px;line-height:1.78;font-family:Arial,Helvetica,sans-serif;color:#464646;">
                                {{ __('mail.admin_manufacturer_additional_information_response.message_intro', [
                                    'name' => $adminName,
                                    'company' => $companyName,
                                    'contact' => $manufacturerName,
                                ]) }}
                            </div>
                        </td>
                    </tr>

                    {{-- Manufacturer responses --}}
                    @if (!empty($responses))
                        <tr>
                            <td bgcolor="#F8F8F8"
                                style="padding:28px 30px;background-color:#F8F8F8;border-bottom:1px solid #F0F0F0;">
                                <table role="presentation" cellspacing="0" cellpadding="0" border="0"
                                    style="margin-bottom:18px;border-collapse:separate;">
                                    <tr>
                                        <td width="3" bgcolor="#E8D5A8"
                                            style="width:3px;background-color:#E8D5A8;border-radius:2px;font-size:0;line-height:0;">
                                            &nbsp;</td>
                                        <td
                                            style="padding-left:9px;font-weight:500;font-size:17px;line-height:1;font-family:Georgia,'Times New Roman',serif;color:#3B2800;">
                                            {!! __('mail.admin_manufacturer_additional_information_response.responses_heading') !!}
                                        </td>
                                    </tr>
                                </table>

                                <!--[if !mso]><!-->
                                <div
                                    style="background-color:#FFFFFF;border:1.5px solid #E6E6E6;border-radius:10px;overflow:hidden;">
                                    <!--<![endif]-->
                                    <!--[if mso]>
                                <table role="presentation" width="100%" cellspacing="0" cellpadding="0" border="0" style="width:100%;background-color:#FFFFFF;border:1.5px solid #E6E6E6;"><tr><td>
                                <![endif]-->
                                    <table role="presentation" width="100%" cellspacing="0" cellpadding="0"
                                        border="0" style="width:100%;background-color:#FFFFFF;border-collapse:collapse;">
                                        @foreach ($responses as $index => $response)
                                            <tr>
                                                <td bgcolor="#FFFFFF"
                                                    style="padding:13px 15px;background-color:#FFFFFF;{{ $loop->last ? '' : 'border-bottom:1px solid #F0F0F0;' }}">
                                                    <table role="presentation" width="100%" cellspacing="0"
                                                        cellpadding="0" border="0">
                                                        <tr>
                                                            <td width="28" valign="top"
                                                                style="width:28px;padding-right:12px;padding-top:1px;">
                                                                <table role="presentation" cellspacing="0"
                                                                    cellpadding="0" border="0"
                                                                    style="border-collapse:separate;border-spacing:0;">
                                                                    <tr>
                                                                        <td width="22" height="22"
                                                                            align="center" valign="middle"
                                                                            bgcolor="#FFF8E4"
                                                                            style="width:22px;height:22px;min-width:22px;background-color:#FFF8E4;border:1.5px dashed #F0C040;border-radius:11px;font-weight:800;font-size:10px;line-height:22px;mso-line-height-rule:exactly;font-family:Arial,Helvetica,sans-serif;color:#7A4D00;text-align:center;">
                                                                            {{ $index + 1 }}</td>
                                                                    </tr>
                                                                </table>
                                                            </td>
                                                            <td valign="top">
                                                                <div
                                                                    style="font-weight:700;font-size:13px;line-height:1.3;font-family:Arial,Helvetica,sans-serif;color:#2E2E2E;margin-bottom:3px;">
                                                                    {{ $response['typeLabel'] ?? __('mail.admin_manufacturer_additional_information_response.response_fallback') }}
                                                                </div>
                                                                @if (!empty($response['message']))
                                                                    <div
                                                                        style="font-weight:400;font-size:12px;line-height:1.55;font-family:Arial,Helvetica,sans-serif;color:#8A8A8A;white-space:pre-wrap;margin-bottom:{{ !empty($response['fileName']) ? '6px' : '0' }};">
                                                                        {{ $response['message'] }}</div>
                                                                @endif
                                                                @if (!empty($response['fileName']))
                                                                    <div
                                                                        style="font-weight:600;font-size:11.5px;line-height:1.4;font-family:Arial,Helvetica,sans-serif;color:#9A7A3A;">
                                                                        {{ __('mail.admin_manufacturer_additional_information_response.response_file', ['file' => $response['fileName']]) }}
                                                                    </div>
                                                                @endif
                                                            </td>
                                                        </tr>
                                                    </table>
                                                </td>
                                            </tr>
                                        @endforeach
                                    </table>
                                    <!--[if mso]>
                                </td></tr></table>
                                <![endif]-->
                                    <!--[if !mso]><!-->
                                </div>
                                <!--<![endif]-->

                                <!--[if !mso]><!-->
                                <div
                                    style="margin-top:16px;background-color:#FBF7EE;border:1.5px solid #E8D5A8;border-left:4px solid #9A7A3A;border-radius:8px;overflow:hidden;">
                                    <!--<![endif]-->
                                    <!--[if mso]>
                                <table role="presentation" width="100%" cellspacing="0" cellpadding="0" border="0" style="margin-top:16px;width:100%;background-color:#FBF7EE;border:1.5px solid #E8D5A8;border-left:4px solid #9A7A3A;"><tr><td>
                                <![endif]-->
                                    <table role="presentation" width="100%" cellspacing="0" cellpadding="0"
                                        border="0"
                                        style="width:100%;background-color:#FBF7EE;border-collapse:collapse;">
                                        <tr>
                                            <td style="padding:14px 16px;">
                                                <div
                                                    style="font-weight:900;font-size:8.5px;line-height:1;font-family:Arial,Helvetica,sans-serif;letter-spacing:1.6px;text-transform:uppercase;color:#9A7A3A;margin-bottom:5px;">
                                                    {{ __('mail.admin_manufacturer_additional_information_response.alert_label') }}
                                                </div>
                                                <div
                                                    style="font-weight:400;font-size:13px;line-height:1.65;font-family:Arial,Helvetica,sans-serif;color:#464646;">
                                                    {!! __('mail.admin_manufacturer_additional_information_response.alert_body') !!}
                                                </div>
                                            </td>
                                        </tr>
                                    </table>
                                    <!--[if mso]>
                                </td></tr></table>
                                <![endif]-->
                                    <!--[if !mso]><!-->
                                </div>
                                <!--<![endif]-->
                            </td>
                        </tr>
                    @endif

                    {{-- CTA --}}
                    <tr>
                        <td bgcolor="#FFFFFF"
                            style="padding:26px 30px 30px;background-color:#FFFFFF;border-top:1px solid #F0F0F0;">
                            <table role="presentation" cellspacing="0" cellpadding="0" border="0">
                                <tr>
                                    <td style="padding-right:10px;">
                                        <a href="{{ $reviewUrl }}"
                                            style="display:inline-block;padding:14px 30px;background-color:#3B2800;color:#FFFFFF;font-weight:900;font-size:12px;line-height:1;font-family:Arial,Helvetica,sans-serif;letter-spacing:0.6px;text-transform:uppercase;text-decoration:none;border-radius:8px;">{{ __('mail.admin_manufacturer_additional_information_response.cta') }}</a>
                                    </td>
                                    <td>
                                        <a href="{{ $allRegistrationsUrl }}"
                                            style="display:inline-block;padding:12px 24px;background-color:transparent;color:#3B2800;font-weight:900;font-size:12px;line-height:1;font-family:Arial,Helvetica,sans-serif;letter-spacing:0.6px;text-transform:uppercase;text-decoration:none;border-radius:8px;border:2px solid #D6D6D6;">{{ __('mail.admin_manufacturer_additional_information_response.cta_secondary') }}</a>
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
                                        {{ strtolower($platformName) }}</td>
                                    <td align="right"
                                        style="font-weight:700;font-size:8px;line-height:1;font-family:Arial,Helvetica,sans-serif;letter-spacing:0.8px;text-transform:uppercase;color:#B4B4B4;">
                                        {{ __('mail.demo.footer_tags.admin') }}</td>
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
                                <a href="{{ $adminPanelUrl }}"
                                    style="color:#B4B4B4;text-decoration:none;">{{ __('mail.admin_manufacturer_additional_information_response.footer_admin_panel') }}</a>
                                <span style="margin:0 5px;font-size:9px;color:#E6E6E6;">·</span>
                                <a href="{{ $allRegistrationsUrl }}"
                                    style="color:#B4B4B4;text-decoration:none;">{{ __('mail.admin_manufacturer_additional_information_response.cta_secondary') }}</a>
                            </span>
                        </td>
                    </tr>

                </table>
            </td>
        </tr>
    </table>
</body>

</html>
