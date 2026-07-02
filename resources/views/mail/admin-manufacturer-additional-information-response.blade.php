<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <title>{{ __('mail.admin_manufacturer_additional_information_response.subject') }}</title>
    <link href="https://fonts.googleapis.com/css2?family=EB+Garamond:ital,wght@0,400;0,500;1,400&family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">
</head>

<body style="margin:0;padding:0;background-color:#ede7d9;font-family:'Inter',system-ui,-apple-system,BlinkMacSystemFont,'Segoe UI',sans-serif;-webkit-font-smoothing:antialiased;">
    <span style="display:none !important;visibility:hidden;mso-hide:all;font-size:1px;color:#ede7d9;line-height:1px;max-height:0;max-width:0;opacity:0;overflow:hidden;">
        {{ __('mail.admin_manufacturer_additional_information_response.preheader') }}
    </span>

    <table role="presentation" width="100%" cellspacing="0" cellpadding="0" border="0" style="background-color:#ede7d9;">
        <tr>
            <td align="center" style="padding:40px 16px;">
                <table role="presentation" width="680" cellspacing="0" cellpadding="0" border="0" style="max-width:680px;width:100%;background-color:#ffffff;border:1px solid #d4c9b0;border-radius:12px;overflow:hidden;box-shadow:0 6px 32px rgba(44,37,23,0.13);">

                    {{-- HEADER --}}
                    <tr>
                        <td align="center" style="padding:40px 40px 24px 40px;background-color:#2c2517;">
                            <table role="presentation" cellspacing="0" cellpadding="0" border="0" align="center" style="margin:0 auto 16px auto;">
                                <tr>
                                    <td style="vertical-align:middle;padding-right:8px;">
                                        <table role="presentation" cellspacing="0" cellpadding="0" border="0">
                                            <tr>
                                                <td align="center" style="width:30px;height:30px;border:1.5px solid #b89d5e;border-radius:50%;">
                                                    <span style="display:inline-block;width:12px;height:12px;border-radius:50%;background-color:#b89d5e;"></span>
                                                </td>
                                            </tr>
                                        </table>
                                    </td>
                                    <td style="vertical-align:middle;">
                                        <span style="font-size:11px;font-weight:600;letter-spacing:0.2em;text-transform:uppercase;color:#d4bc8a;font-family:'Inter',system-ui,sans-serif;">SourceNest</span>
                                    </td>
                                </tr>
                            </table>

                            <table role="presentation" cellspacing="0" cellpadding="0" border="0" align="center" style="margin:0 auto 16px auto;">
                                <tr>
                                    <td style="width:60px;height:1px;background-color:#b89d5e;opacity:0.45;font-size:0;line-height:0;">&nbsp;</td>
                                </tr>
                            </table>

                            <p style="margin:0 0 8px 0;font-size:9.5px;font-weight:500;letter-spacing:0.22em;text-transform:uppercase;color:#d4bc8a;opacity:0.65;font-family:'Inter',system-ui,sans-serif;">
                                {{ __('mail.admin_manufacturer_additional_information_response.header_eyebrow') }}
                            </p>
                            <h1 style="margin:0 0 8px 0;font-family:'EB Garamond',Georgia,serif;font-size:34px;font-weight:400;color:#f5f0e8;line-height:1.2;">
                                {{ __('mail.admin_manufacturer_additional_information_response.header_title') }}
                            </h1>
                            <p style="margin:0;font-family:'EB Garamond',Georgia,serif;font-style:italic;font-size:14px;color:#d4bc8a;opacity:0.8;">
                                {{ __('mail.admin_manufacturer_additional_information_response.header_subtitle') }}
                            </p>
                        </td>
                    </tr>

                    {{-- BODY --}}
                    <tr>
                        <td style="padding:24px 40px 40px 40px;background-color:#f5f0e8;">

                            {{-- Alert Banner --}}
                            <table role="presentation" width="100%" cellspacing="0" cellpadding="0" border="0" style="margin:0 0 24px 0;background-color:#fdf3e3;border:1px solid #e8c07a;border-left:4px solid #c47a2a;border-radius:8px;">
                                <tr>
                                    <td style="padding:16px 24px;">
                                        <table role="presentation" cellspacing="0" cellpadding="0" border="0" width="100%">
                                            <tr>
                                                <td width="36" style="vertical-align:top;padding-right:16px;">
                                                    <table role="presentation" cellspacing="0" cellpadding="0" border="0">
                                                        <tr>
                                                            <td align="center" style="width:36px;height:36px;border-radius:50%;background-color:#c47a2a;font-size:18px;font-weight:700;color:#ffffff;line-height:36px;">!</td>
                                                        </tr>
                                                    </table>
                                                </td>
                                                <td style="vertical-align:top;">
                                                    <span style="display:inline-block;font-size:9px;font-weight:700;letter-spacing:0.18em;text-transform:uppercase;color:#c47a2a;background-color:rgba(196,122,42,0.12);border-radius:3px;padding:2px 7px;margin-bottom:4px;">
                                                        {{ __('mail.admin_manufacturer_additional_information_response.alert_tag') }}
                                                    </span>
                                                    <p style="margin:0 0 3px 0;font-size:14px;font-weight:600;color:#2c2517;font-family:'Inter',system-ui,sans-serif;">
                                                        {{ __('mail.admin_manufacturer_additional_information_response.alert_heading') }}
                                                    </p>
                                                    <p style="margin:0;font-size:12px;color:#7a6e5a;font-family:'Inter',system-ui,sans-serif;">
                                                        {{ __('mail.admin_manufacturer_additional_information_response.alert_meta', ['date' => $submittedAt, 'reference' => $referenceId]) }}
                                                    </p>
                                                </td>
                                            </tr>
                                        </table>
                                    </td>
                                </tr>
                            </table>

                            {{-- Message Block --}}
                            <table role="presentation" width="100%" cellspacing="0" cellpadding="0" border="0" style="margin:0 0 24px 0;background-color:#ffffff;border:1px solid #d4c9b0;border-radius:8px;">
                                <tr>
                                    <td style="padding:24px;">
                                        <p style="margin:0 0 8px 0;font-size:9.5px;font-weight:600;letter-spacing:0.18em;text-transform:uppercase;color:#a89880;font-family:'Inter',system-ui,sans-serif;">
                                            {{ __('mail.admin_manufacturer_additional_information_response.message_heading') }}
                                        </p>
                                        <p style="margin:0;font-size:14px;line-height:1.7;color:#7a6e5a;font-family:'Inter',system-ui,sans-serif;">
                                            {{ __('mail.admin_manufacturer_additional_information_response.message_intro', [
                                                'name' => $adminName,
                                                'company' => $companyName,
                                                'contact' => $manufacturerName,
                                            ]) }}
                                        </p>
                                    </td>
                                </tr>
                            </table>

                            @if (!empty($responses))
                                <p style="margin:0 0 16px 0;font-size:9.5px;font-weight:600;letter-spacing:0.2em;text-transform:uppercase;color:#a89880;font-family:'Inter',system-ui,sans-serif;">
                                    {{ __('mail.admin_manufacturer_additional_information_response.responses_heading') }}
                                </p>

                                @foreach ($responses as $index => $response)
                                    <table role="presentation" width="100%" cellspacing="0" cellpadding="0" border="0" style="margin:0 0 8px 0;background-color:#ffffff;border:1px solid #d4c9b0;border-radius:8px;">
                                        <tr>
                                            <td style="padding:16px;">
                                                <table role="presentation" cellspacing="0" cellpadding="0" border="0" width="100%">
                                                    <tr>
                                                        <td width="28" style="vertical-align:top;padding-right:16px;">
                                                            <table role="presentation" cellspacing="0" cellpadding="0" border="0">
                                                                <tr>
                                                                    <td align="center" style="width:28px;height:28px;border-radius:50%;background-color:#2c2517;font-size:11px;font-weight:700;color:#d4bc8a;line-height:28px;font-family:'Inter',system-ui,sans-serif;">
                                                                        {{ $index + 1 }}
                                                                    </td>
                                                                </tr>
                                                            </table>
                                                        </td>
                                                        <td style="vertical-align:top;">
                                                            <p style="margin:0 0 3px 0;font-size:13.5px;font-weight:600;color:#2c2517;font-family:'Inter',system-ui,sans-serif;">
                                                                {{ $response['typeLabel'] }}
                                                            </p>
                                                            @if (!empty($response['message']))
                                                                <p style="margin:0 0 6px 0;font-size:12.5px;line-height:1.55;color:#7a6e5a;white-space:pre-wrap;font-family:'Inter',system-ui,sans-serif;">{{ $response['message'] }}</p>
                                                            @endif
                                                            @if (!empty($response['fileName']))
                                                                <p style="margin:0;font-size:12px;color:#7a6e5a;font-family:'Inter',system-ui,sans-serif;">
                                                                    {{ __('mail.admin_manufacturer_additional_information_response.response_file', ['file' => $response['fileName']]) }}
                                                                </p>
                                                            @endif
                                                        </td>
                                                    </tr>
                                                </table>
                                            </td>
                                        </tr>
                                    </table>
                                @endforeach
                            @endif

                            {{-- CTA --}}
                            <table role="presentation" width="100%" cellspacing="0" cellpadding="0" border="0" style="margin:24px 0;">
                                <tr>
                                    <td align="center">
                                        <table role="presentation" cellspacing="0" cellpadding="0" border="0" style="margin:0 auto;">
                                            <tr>
                                                <td style="border-radius:8px;background-color:#2c2517;border:1px solid #b89d5e;">
                                                    <a href="{{ $reviewUrl }}" style="display:inline-block;padding:14px 36px;font-size:13px;font-weight:600;letter-spacing:0.08em;color:#d4bc8a;text-decoration:none;font-family:'Inter',system-ui,sans-serif;">
                                                        {{ __('mail.admin_manufacturer_additional_information_response.cta') }}
                                                    </a>
                                                </td>
                                            </tr>
                                        </table>
                                        <p style="margin:8px 0 0 0;font-size:11.5px;color:#a89880;font-family:'Inter',system-ui,sans-serif;">
                                            {{ __('mail.admin_manufacturer_additional_information_response.cta_link_note') }}
                                            <a href="{{ $reviewUrl }}" style="color:#c47a2a;text-decoration:underline;word-break:break-all;">{{ $reviewUrl }}</a>
                                        </p>
                                    </td>
                                </tr>
                            </table>

                            {{-- Signature Row --}}
                            <table role="presentation" width="100%" cellspacing="0" cellpadding="0" border="0" style="margin-top:16px;border-top:1px solid #d4c9b0;padding-top:24px;">
                                <tr>
                                    <td width="33%" style="vertical-align:bottom;padding-right:12px;">
                                        <table role="presentation" cellspacing="0" cellpadding="0" border="0">
                                            <tr>
                                                <td style="width:130px;height:1px;background-color:#d4c9b0;font-size:0;line-height:0;padding-bottom:5px;">&nbsp;</td>
                                            </tr>
                                        </table>
                                        <p style="margin:0;font-family:'EB Garamond',Georgia,serif;font-style:italic;font-size:15px;color:#2c2517;">
                                            {{ __('mail.admin_manufacturer_additional_information_response.signature_name') }}
                                        </p>
                                        <p style="margin:3px 0 0 0;font-size:9.5px;font-weight:600;letter-spacing:0.15em;text-transform:uppercase;color:#a89880;font-family:'Inter',system-ui,sans-serif;">
                                            {{ __('mail.admin_manufacturer_additional_information_response.signature_role') }}
                                        </p>
                                    </td>
                                    <td width="34%" align="center" style="vertical-align:bottom;padding:0 12px;">
                                        <table role="presentation" cellspacing="0" cellpadding="0" border="0" align="center">
                                            <tr>
                                                <td align="center" style="width:60px;height:60px;border-radius:50%;background-color:#2c2517;border:2px solid #b89d5e;text-align:center;vertical-align:middle;">
                                                    <span style="display:block;font-size:16px;color:#b89d5e;line-height:1;">&#10003;</span>
                                                    <span style="display:block;font-size:6px;font-weight:700;letter-spacing:0.1em;text-transform:uppercase;color:#d4bc8a;line-height:1.2;margin-top:2px;">Source<br>Nest</span>
                                                </td>
                                            </tr>
                                        </table>
                                    </td>
                                    <td width="33%" align="right" style="vertical-align:bottom;padding-left:12px;">
                                        <p style="margin:0 0 3px 0;font-size:9.5px;letter-spacing:0.12em;text-transform:uppercase;color:#a89880;font-family:'Inter',system-ui,sans-serif;">
                                            {{ __('mail.admin_manufacturer_additional_information_response.reference_label') }}
                                        </p>
                                        <p style="margin:0;font-size:13px;font-weight:600;color:#2c2517;font-family:'Inter',system-ui,sans-serif;">
                                            {{ $referenceId }}
                                        </p>
                                        <p style="margin:2px 0 0 0;font-size:11px;color:#a89880;font-family:'Inter',system-ui,sans-serif;">
                                            {{ __('mail.admin_manufacturer_additional_information_response.footer_site') }}
                                        </p>
                                    </td>
                                </tr>
                            </table>

                        </td>
                    </tr>

                    {{-- FOOTER --}}
                    <tr>
                        <td style="padding:16px 40px;background-color:#3d3220;">
                            <table role="presentation" width="100%" cellspacing="0" cellpadding="0" border="0">
                                <tr>
                                    <td width="20%" style="vertical-align:middle;">
                                        <span style="font-size:10px;font-weight:600;letter-spacing:0.18em;text-transform:uppercase;color:#d4bc8a;opacity:0.8;font-family:'Inter',system-ui,sans-serif;">SourceNest</span>
                                    </td>
                                    <td width="60%" align="center" style="vertical-align:middle;">
                                        <p style="margin:0;font-size:9.5px;color:#d4bc8a;opacity:0.4;line-height:1.55;text-align:center;font-family:'Inter',system-ui,sans-serif;">
                                            {{ __('mail.admin_manufacturer_additional_information_response.footer') }}
                                        </p>
                                    </td>
                                    <td width="20%" align="right" style="vertical-align:middle;">
                                        <span style="font-size:10px;color:#d4bc8a;opacity:0.6;font-family:'Inter',system-ui,sans-serif;">{{ __('mail.admin_manufacturer_additional_information_response.footer_site') }}</span>
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
