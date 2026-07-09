@php
    $platformName = config('app.name', 'SourceNest');
    $logoUrl = public_url('images/mail/sourcenest-logo.png');
    $frontendUrl = rtrim((string) config('app.frontend_url', config('app.url')), '/');
    $mailIconStyle = 'display:block;border:0;outline:none;text-decoration:none;margin:0 auto;';
    $heroIconUrl = public_url('images/mail/svg/inquiry-mail-admin.svg');
    $initials = trim($initials ?? 'SN') !== '' ? trim($initials ?? 'SN') : 'SN';
    $contactName = trim($contactName ?? '') !== '' ? trim($contactName ?? '') : $platformName;
    $contactSubline = trim($contactSubline ?? ($contactMeta ?? ''));
    $messageBody = trim($message ?? '');
    $receivedAt = trim($receivedAt ?? now()->format('M j · g:i A'));
    $detailsRows = is_array($details ?? null) ? $details : [];
    $inquiryTags = is_array($inquiryTags ?? null) ? $inquiryTags : [];
    $ctaUrl = $ctaUrl ?? ($adminPanelUrl ?? \App\Support\Mail\MailNotificationHelper::frontendUrl('admin/contacts'));
    $contactsListUrl = $contactsListUrl ?? \App\Support\Mail\MailNotificationHelper::frontendUrl('admin/contacts');
    $adminHomeUrl = \App\Support\Mail\MailNotificationHelper::frontendUrl('admin');
    $adminSettingsUrl = \App\Support\Mail\MailNotificationHelper::frontendUrl('admin/settings');
    $adminDocsUrl = \App\Support\Mail\MailNotificationHelper::frontendUrl('admin/docs');
@endphp
<!DOCTYPE html>
<html lang="en" xmlns="http://www.w3.org/1999/xhtml" xmlns:v="urn:schemas-microsoft-com:vml"
    xmlns:o="urn:schemas-microsoft-com:office:office">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <title>{{ __('mail.admin_new_inquiry.subject') }}</title>
    <!--[if mso]>
    <noscript>
        <xml>
            <o:OfficeDocumentSettings>
                <o:PixelsPerInch>96</o:PixelsPerInch>
            </o:OfficeDocumentSettings>
        </xml>
    </noscript>
    <![endif]-->
</head>

<body
    style="margin:0;padding:0;background-color:#F0F0F0;font-family:Arial,Helvetica,sans-serif;-webkit-text-size-adjust:100%;-ms-text-size-adjust:100%;">
    <span
        style="display:none !important;visibility:hidden;mso-hide:all;font-size:1px;color:#F0F0F0;line-height:1px;max-height:0;max-width:0;opacity:0;overflow:hidden;">{{ __('mail.admin_new_inquiry.preheader') }}</span>

    <table role="presentation" width="100%" cellspacing="0" cellpadding="0" border="0"
        style="background-color:#F0F0F0;">
        <tr>
            <td align="center" style="padding:24px 12px;">
                <table role="presentation" width="600" cellspacing="0" cellpadding="0" border="0"
                    style="max-width:600px;width:100%;background-color:#FFFFFF;border-radius:14px;overflow:hidden;">

                    {{-- Header A: white with admin pill --}}
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
                                            style="display:inline-block;padding:4px 11px;border-radius:20px;border:1.5px solid #F0C080;background-color:#FFF5E6;font-weight:800;font-size:8.5px;line-height:1;font-family:Arial,Helvetica,sans-serif;letter-spacing:1.2px;text-transform:uppercase;color:#666666;">
                                            <span
                                                style="display:inline-block;width:5px;height:5px;border-radius:50%;background-color:#D07800;vertical-align:middle;margin-right:5px;">&nbsp;</span>{{ __('mail.admin_new_inquiry.badge') }}
                                        </span>
                                    </td>
                                </tr>
                            </table>
                        </td>
                    </tr>

                    {{-- Hero H6: compact admin gray --}}
                    <tr>
                        <td bgcolor="#F8F8F8"
                            style="padding:22px 30px;background-color:#F8F8F8;border-bottom:1.5px solid #E6E6E6;">
                            <table role="presentation" width="100%" cellspacing="0" cellpadding="0" border="0">
                                <tr>
                                    <td width="44" valign="top" style="padding-right:16px;">
                                        <table role="presentation" cellspacing="0" cellpadding="0" border="0">
                                            <tr>
                                                <td width="44" height="44" align="center" valign="middle"
                                                    bgcolor="#FFFFFF"
                                                    style="width:44px;height:44px;background-color:#FFFFFF;border:1.5px solid #D6D6D6;border-radius:10px;">
                                                    @if (!empty($heroIconUrl))
                                                        <img src="{{ $heroIconUrl }}" width="20" height="20"
                                                            alt=""
                                                            style="{{ $mailIconStyle }}">
                                                    @endif
                                                </td>
                                            </tr>
                                        </table>
                                    </td>
                                    <td valign="top">
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
                                                    {{ __('mail.admin_new_inquiry.eyebrow') }}</td>
                                            </tr>
                                        </table>
                                        <div
                                            style="font-weight:500;font-size:20px;line-height:1.17;font-family:Georgia,'Times New Roman',serif;color:#1C1C1C;letter-spacing:-0.2px;">
                                            {!! __('mail.admin_new_inquiry.hero_headline') !!}
                                        </div>
                                        <div
                                            style="padding-top:4px;font-weight:400;font-size:13px;line-height:1.78;font-family:Arial,Helvetica,sans-serif;color:#8A8A8A;">
                                            {{ __('mail.admin_new_inquiry.hero_subheadline') }}</div>
                                    </td>
                                </tr>
                            </table>
                        </td>
                    </tr>

                    {{-- Inquiry card --}}
                    <tr>
                        <td bgcolor="#FFFFFF"
                            style="padding:28px 30px;background-color:#FFFFFF;border-bottom:1px solid #F0F0F0;">
                            <table role="presentation" width="100%" cellspacing="0" cellpadding="0" border="0"
                                style="border:1.5px solid #E6E6E6;border-radius:10px;border-collapse:separate;">
                                <tr>
                                    <td bgcolor="#F8F8F8"
                                        style="padding:14px 17px;background-color:#F8F8F8;border-bottom:1px solid #E6E6E6;">
                                        <table role="presentation" width="100%" cellspacing="0" cellpadding="0"
                                            border="0">
                                            <tr>
                                                <td width="38" valign="top" style="width:38px;">
                                                    <table role="presentation" cellspacing="0" cellpadding="0"
                                                        border="0">
                                                        <tr>
                                                            <td width="38" height="38" align="center"
                                                                valign="middle" bgcolor="#3B2800"
                                                                style="width:38px;height:38px;background-color:#3B2800;border-radius:8px;font-weight:600;font-size:14px;line-height:1;font-family:Georgia,'Times New Roman',serif;color:#C8A96A;">
                                                                {{ $initials }}</td>
                                                        </tr>
                                                    </table>
                                                </td>
                                                <td valign="top" style="padding-left:12px;">
                                                    <div
                                                        style="font-weight:800;font-size:13.5px;line-height:1;font-family:Arial,Helvetica,sans-serif;color:#3B2800;">
                                                        {{ $contactName }}</div>
                                                    @if ($contactSubline !== '')
                                                        <div
                                                            style="padding-top:2px;font-weight:500;font-size:11.5px;line-height:1.4;font-family:Arial,Helvetica,sans-serif;color:#8A8A8A;">
                                                            {{ $contactSubline }}</div>
                                                    @endif
                                                </td>
                                                <td align="right" valign="top"
                                                    style="font-weight:500;font-size:11px;line-height:1;font-family:Arial,Helvetica,sans-serif;color:#B4B4B4;white-space:nowrap;">
                                                    {{ $receivedAt }}</td>
                                            </tr>
                                        </table>
                                    </td>
                                </tr>
                                @if ($messageBody !== '')
                                    <tr>
                                        <td style="padding:17px 19px;border-bottom:1px solid #F0F0F0;">
                                            <div
                                                style="padding-left:16px;font-weight:400;font-size:13.5px;line-height:1.8;font-family:Arial,Helvetica,sans-serif;font-style:italic;color:#464646;">
                                                {{ $messageBody }}</div>
                                        </td>
                                    </tr>
                                @endif
                                @if (!empty($inquiryTags))
                                    <tr>
                                        <td bgcolor="#F8F8F8" style="padding:10px 17px;background-color:#F8F8F8;">
                                            @foreach ($inquiryTags as $tag)
                                                <span
                                                    style="display:inline-block;margin:0 6px 6px 0;padding:3px 11px;border-radius:20px;border:1.5px solid #E6E6E6;background-color:#FFFFFF;font-weight:600;font-size:11px;line-height:1;font-family:Arial,Helvetica,sans-serif;color:#464646;">
                                                    {{ $tag['label'] ?? '' }}
                                                    <strong
                                                        style="font-weight:800;color:#3B2800;">{{ $tag['value'] ?? '' }}</strong>
                                                </span>
                                            @endforeach
                                        </td>
                                    </tr>
                                @endif
                            </table>
                        </td>
                    </tr>

                    {{-- System record --}}
                    <tr>
                        <td bgcolor="#F8F8F8"
                            style="padding:28px 30px;background-color:#F8F8F8;border-bottom:1px solid #F0F0F0;">
                            <table role="presentation" width="100%" cellspacing="0" cellpadding="0" border="0"
                                style="margin-bottom:18px;">
                                <tr>
                                    <td width="3" bgcolor="#E8D5A8"
                                        style="width:3px;background-color:#E8D5A8;border-radius:2px;font-size:0;line-height:0;">
                                        &nbsp;</td>
                                    <td
                                        style="padding-left:9px;font-weight:500;font-size:17px;line-height:1;font-family:Georgia,'Times New Roman',serif;color:#3B2800;">
                                        {!! __('mail.admin_new_inquiry.record_section_title') !!}</td>
                                </tr>
                            </table>

                            <table role="presentation" width="100%" cellspacing="0" cellpadding="0" border="0"
                                style="border:1.5px solid #E6E6E6;border-radius:10px;border-collapse:separate;">
                                <tr>
                                    <td bgcolor="#F8F8F8"
                                        style="padding:11px 16px;background-color:#F8F8F8;border-bottom:1px solid #E6E6E6;">
                                        <span
                                            style="font-weight:900;font-size:9px;line-height:1;font-family:Arial,Helvetica,sans-serif;letter-spacing:1.2px;text-transform:uppercase;color:#8A8A8A;">{{ __('mail.admin_new_inquiry.record_title') }}</span>
                                    </td>
                                </tr>
                                @foreach ($detailsRows as $label => $value)
                                    @if ($value !== null && $value !== '')
                                        <tr>
                                            <td style="padding:0;border-top:1px solid #F0F0F0;">
                                                <table role="presentation" width="100%" cellspacing="0"
                                                    cellpadding="0" border="0">
                                                    <tr>
                                                        <td width="110" bgcolor="#F8F8F8" valign="top"
                                                            style="width:110px;padding:11px 16px;background-color:#F8F8F8;border-right:1px solid #F0F0F0;font-weight:700;font-size:11px;line-height:1.4;font-family:Arial,Helvetica,sans-serif;color:#8A8A8A;">
                                                            {{ $label }}</td>
                                                        <td valign="top"
                                                            style="padding:11px 16px;font-weight:{{ $label === 'Inquiry ID' ? '500' : '500' }};font-size:{{ $label === 'Inquiry ID' ? '12px' : '12.5px' }};line-height:1.4;font-family:{{ $label === 'Inquiry ID' ? 'Courier New,Courier,monospace' : 'Arial,Helvetica,sans-serif' }};color:#1C1C1C;">
                                                            {{ $value }}</td>
                                                    </tr>
                                                </table>
                                            </td>
                                        </tr>
                                    @endif
                                @endforeach
                            </table>
                        </td>
                    </tr>

                    {{-- CTA --}}
                    <tr>
                        <td bgcolor="#FFFFFF"
                            style="padding:26px 30px 30px;background-color:#FFFFFF;border-top:1px solid #F0F0F0;">
                            <table role="presentation" cellspacing="0" cellpadding="0" border="0">
                                <tr>
                                    <td style="padding-right:10px;">
                                        <a href="{{ $ctaUrl }}"
                                            style="display:inline-block;padding:14px 30px;background-color:#3B2800;color:#FFFFFF;font-weight:900;font-size:12px;line-height:1;font-family:Arial,Helvetica,sans-serif;letter-spacing:0.6px;text-transform:uppercase;text-decoration:none;border-radius:8px;">{{ $ctaLabel ?? __('mail.admin_new_inquiry.cta') }}</a>
                                    </td>
                                    <td>
                                        <a href="{{ $contactsListUrl }}"
                                            style="display:inline-block;padding:12px 24px;background-color:transparent;color:#3B2800;font-weight:900;font-size:12px;line-height:1;font-family:Arial,Helvetica,sans-serif;letter-spacing:0.6px;text-transform:uppercase;text-decoration:none;border-radius:8px;border:2px solid #D6D6D6;">{{ __('mail.admin_new_inquiry.cta_secondary') }}</a>
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
                                        {{ __('mail.admin_new_inquiry.footer_tag') }}</td>
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
                                <a href="{{ $adminHomeUrl }}"
                                    style="color:#B4B4B4;text-decoration:none;">{{ __('mail.admin_new_inquiry.footer_admin_panel') }}</a>
                                <span style="margin:0 5px;font-size:9px;color:#E6E6E6;">·</span>
                                <a href="{{ $adminSettingsUrl }}"
                                    style="color:#B4B4B4;text-decoration:none;">{{ __('mail.admin_new_inquiry.footer_settings') }}</a>
                                <span style="margin:0 5px;font-size:9px;color:#E6E6E6;">·</span>
                                <a href="{{ $adminDocsUrl }}"
                                    style="color:#B4B4B4;text-decoration:none;">{{ __('mail.admin_new_inquiry.footer_docs') }}</a>
                            </span>
                        </td>
                    </tr>

                </table>
            </td>
        </tr>
    </table>
</body>

</html>
