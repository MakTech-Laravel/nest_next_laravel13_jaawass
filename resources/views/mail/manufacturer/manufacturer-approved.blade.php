@php
    $platformName = config('app.name', 'SourceNest');
    $logoUrl = public_url('images/mail/sourcenest-logo-white.png');
    $frontendUrl = rtrim((string) config('app.frontend_url', config('app.url')), '/');
    $supportEmail = config('mail.from.address', 'support@sourcenest.com');
    $mailIconStyle = 'display:block;border:0;outline:none;text-decoration:none;margin:0 auto;';
    $recipientName = trim($name ?? ($firstName ?? '')) !== '' ? trim($name ?? ($firstName ?? '')) : 'there';
    $companyName = trim($company ?? '') !== '' ? trim($company ?? '') : $platformName;
    $approvedDate = $approvedDate ?? ($decisionDate ?? now()->format('F j, Y'));
    $ctaUrl = $ctaUrl ?? \App\Support\Mail\MailNotificationHelper::frontendUrl('pricing');
    $planDetailsUrl = $planDetailsUrl ?? \App\Support\Mail\MailNotificationHelper::frontendUrl('pricing');
    $planAmount = $planAmount ?? '$199';
    $globeWatermarkUrl = public_url('images/mail/svg/globe-hero-watermark.svg');
    $checkFlowIconUrl = public_url('images/mail/svg/check-flow.svg');
    $checkBrandIconUrl = public_url('images/mail/svg/check-brand.svg');
    $lockWarnIconUrl = public_url('images/mail/svg/lock-warn.svg');
    $flowSteps = [
        [
            'state' => 'done',
            'icon' => $checkFlowIconUrl,
            'number' => null,
            'label' => __('mail.manufacturer_approved.flow_step_1_label'),
            'desc' => __('mail.manufacturer_approved.flow_step_1_desc'),
        ],
        [
            'state' => 'done',
            'icon' => $checkFlowIconUrl,
            'number' => null,
            'label' => __('mail.manufacturer_approved.flow_step_2_label'),
            'desc' => __('mail.manufacturer_approved.flow_step_2_desc'),
        ],
        [
            'state' => 'current',
            'icon' => null,
            'number' => '3',
            'label' => __('mail.manufacturer_approved.flow_step_3_label'),
            'desc' => __('mail.manufacturer_approved.flow_step_3_desc'),
        ],
        [
            'state' => 'upcoming',
            'icon' => null,
            'number' => '4',
            'label' => __('mail.manufacturer_approved.flow_step_4_label'),
            'desc' => __('mail.manufacturer_approved.flow_step_4_desc'),
        ],
    ];
    $accessRows = [
        __('mail.manufacturer_approved.access_row_1'),
        __('mail.manufacturer_approved.access_row_2'),
        __('mail.manufacturer_approved.access_row_3'),
        __('mail.manufacturer_approved.access_row_4'),
    ];
    $planItems = [
        __('mail.manufacturer_approved.plan_item_1'),
        __('mail.manufacturer_approved.plan_item_2'),
        __('mail.manufacturer_approved.plan_item_3'),
        __('mail.manufacturer_approved.plan_item_4'),
        __('mail.manufacturer_approved.plan_item_5'),
    ];
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
    <title>{{ __('mail.manufacturer_approved.subject') }}</title>
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
        style="display:none !important;visibility:hidden;mso-hide:all;font-size:1px;color:#F0F0F0;line-height:1px;max-height:0;max-width:0;opacity:0;overflow:hidden;">{{ __('mail.manufacturer_approved.preheader') }}</span>

    <table role="presentation" width="100%" cellspacing="0" cellpadding="0" border="0"
        style="background-color:#F0F0F0;">
        <tr>
            <td align="center" style="padding:24px 12px;">
                <table role="presentation" width="600" cellspacing="0" cellpadding="0" border="0"
                    style="max-width:600px;width:100%;background-color:#FFFFFF;border-radius:14px;overflow:hidden;">

                    {{-- Header B: dark brand --}}
                    <tr>
                        <td bgcolor="#3B2800" style="padding:20px 30px;background-color:#3B2800;">
                            <table role="presentation" width="100%" cellspacing="0" cellpadding="0" border="0">
                                <tr>
                                    <td align="left" valign="middle">
                                        @if (!empty($logoUrl))
                                            <img src="{{ $logoUrl }}" alt="{{ $platformName }}" width="140"
                                                style="display:block;height:auto;max-height:68px;width:auto;border:0;outline:none;text-decoration:none;">
                                        @else
                                            <div
                                                style="font-weight:900;font-size:21px;line-height:1;font-family:Arial,Helvetica,sans-serif;color:#FFFFFF;letter-spacing:-0.6px;">
                                                sourcenest</div>
                                        @endif
                                    </td>
                                    <td align="right" valign="middle">
                                        <span
                                            style="display:inline-block;padding:4px 12px;border-radius:20px;border:1.5px solid rgba(200,169,106,0.18);background-color:transparent;font-weight:700;font-size:9px;line-height:1;font-family:Arial,Helvetica,sans-serif;letter-spacing:0.4px;text-transform:uppercase;color:rgba(200,169,106,0.5);">{{ __('mail.manufacturer_approved.badge') }}</span>
                                    </td>
                                </tr>
                            </table>
                        </td>
                    </tr>

                    {{-- Hero H1: tinted with globe watermark --}}
                    <tr>
                        <td bgcolor="#FBF7EE"
                            background="{{ $globeWatermarkUrl }}"
                            style="padding:34px 30px 40px;background-color:#FBF7EE;background-image:url('{{ $globeWatermarkUrl }}');background-repeat:no-repeat;background-position:right -24px top -24px;background-size:210px 210px;border-bottom:1.5px solid #E8D5A8;">
                            <!--[if gte mso 9]>
                            <v:rect xmlns:v="urn:schemas-microsoft-com:vml" fill="true" stroke="false" style="position:absolute;width:600px;height:220px;">
                                <v:fill type="frame" src="{{ $globeWatermarkUrl }}" color="#FBF7EE" />
                            </v:rect>
                            <![endif]-->
                            <table role="presentation" width="100%" cellspacing="0" cellpadding="0" border="0">
                                <tr>
                                    <td valign="top" style="padding-right:0;">
                                        <table role="presentation" cellspacing="0" cellpadding="0" border="0"
                                            style="margin-bottom:14px;border-collapse:separate;border-spacing:0;">
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
                                                                {{ __('mail.manufacturer_approved.pill') }}</td>
                                                        </tr>
                                                    </table>
                                                </td>
                                            </tr>
                                        </table>
                                        <table role="presentation" cellspacing="0" cellpadding="0" border="0"
                                            style="margin-bottom:13px;">
                                            <tr>
                                                <td width="20" valign="middle"
                                                    style="line-height:0;font-size:0;padding-right:8px;">
                                                    <span
                                                        style="display:block;width:20px;height:2px;border-radius:1px;background-color:#E8D5A8;">&nbsp;</span>
                                                </td>
                                                <td valign="middle"
                                                    style="font-weight:800;font-size:8.5px;line-height:1;font-family:Arial,Helvetica,sans-serif;letter-spacing:2px;text-transform:uppercase;color:#9A7A3A;">
                                                    {{ __('mail.manufacturer_approved.eyebrow') }}</td>
                                            </tr>
                                        </table>
                                        <div
                                            style="font-weight:500;font-size:31px;line-height:1.17;font-family:Georgia,'Times New Roman',serif;color:#3B2800;letter-spacing:-0.2px;max-width:380px;">
                                            {!! __('mail.manufacturer_approved.hero_headline') !!}
                                        </div>
                                        <div
                                            style="padding-top:12px;font-weight:400;font-size:13.5px;line-height:1.78;font-family:Arial,Helvetica,sans-serif;color:#666666;max-width:380px;">
                                            {{ __('mail.manufacturer_approved.hero_subheadline') }}</div>
                                    </td>
                                </tr>
                            </table>
                        </td>
                    </tr>

                    {{-- Greeting + flow tracker + approval banner --}}
                    <tr>
                        <td bgcolor="#FFFFFF"
                            style="padding:28px 30px;background-color:#FFFFFF;border-bottom:1px solid #F0F0F0;">
                            <div
                                style="font-weight:500;font-size:17px;line-height:1;font-family:Georgia,'Times New Roman',serif;color:#3B2800;margin-bottom:13px;">
                                {{ __('mail.manufacturer_approved.greeting', ['name' => $recipientName]) }}</div>
                            <p
                                style="margin:0 0 12px 0;font-weight:400;font-size:13.5px;line-height:1.88;font-family:Arial,Helvetica,sans-serif;color:#464646;">
                                {{ __('mail.manufacturer_approved.intro_paragraph_1') }}</p>
                            <p
                                style="margin:0;font-weight:400;font-size:13.5px;line-height:1.88;font-family:Arial,Helvetica,sans-serif;color:#464646;">
                                {!! __('mail.manufacturer_approved.intro_paragraph_2') !!}</p>

                            {{-- Flow tracker --}}
                            <table role="presentation" width="100%" cellspacing="0" cellpadding="0" border="0"
                                style="margin-top:20px;background-color:#F8F8F8;border:1.5px solid #E6E6E6;border-radius:12px;border-collapse:separate;">
                                <tr>
                                    <td style="padding:20px 12px;">
                                        <table role="presentation" width="100%" cellspacing="0" cellpadding="0"
                                            border="0">
                                            <tr>
                                                @foreach ($flowSteps as $step)
                                                    @php
                                                        $circleBg = match ($step['state']) {
                                                            'done' => '#EAFAF2',
                                                            'current' => '#FFF8E4',
                                                            default => '#F0F0F0',
                                                        };
                                                        $circleBorder = match ($step['state']) {
                                                            'done' => '#6ECFA0',
                                                            'current' => '#C07800',
                                                            default => '#D6D6D6',
                                                        };
                                                        $labelColor = match ($step['state']) {
                                                            'done' => '#0A5C32',
                                                            'current' => '#7A4D00',
                                                            default => '#8A8A8A',
                                                        };
                                                        $descColor = match ($step['state']) {
                                                            'done' => '#0E8A4A',
                                                            'current' => '#C07800',
                                                            default => '#B4B4B4',
                                                        };
                                                        $numberColor = $step['state'] === 'current' ? '#7A4D00' : '#8A8A8A';
                                                    @endphp
                                                    <td width="135" align="center" valign="top"
                                                        style="width:25%;padding:0 4px;">
                                                        <table role="presentation" width="100%" cellspacing="0"
                                                            cellpadding="0" border="0" align="center">
                                                            <tr>
                                                                <td align="center" style="padding-bottom:8px;">
                                                                    <table role="presentation" cellspacing="0"
                                                                        cellpadding="0" border="0" align="center"
                                                                        style="border-collapse:separate;border-spacing:0;">
                                                                        <tr>
                                                                            <td width="34" height="34"
                                                                                align="center" valign="middle"
                                                                                bgcolor="{{ $circleBg }}"
                                                                                style="width:34px;height:34px;background-color:{{ $circleBg }};border:2px solid {{ $circleBorder }};border-radius:17px;">
                                                                                @if (!empty($step['icon']))
                                                                                    <img src="{{ $step['icon'] }}"
                                                                                        width="14" height="14"
                                                                                        alt=""
                                                                                        style="{{ $mailIconStyle }}">
                                                                                @else
                                                                                    <span
                                                                                        style="font-weight:800;font-size:11px;line-height:1;font-family:Arial,Helvetica,sans-serif;color:{{ $numberColor }};">{{ $step['number'] }}</span>
                                                                                @endif
                                                                            </td>
                                                                        </tr>
                                                                    </table>
                                                                </td>
                                                            </tr>
                                                            <tr>
                                                                <td align="center"
                                                                    style="font-weight:800;font-size:9px;line-height:1.3;font-family:Arial,Helvetica,sans-serif;letter-spacing:0.5px;text-transform:uppercase;color:{{ $labelColor }};">
                                                                    {{ $step['label'] }}</td>
                                                            </tr>
                                                            <tr>
                                                                <td align="center"
                                                                    style="padding-top:4px;font-weight:400;font-size:10px;line-height:1.4;font-family:Arial,Helvetica,sans-serif;color:{{ $descColor }};">
                                                                    {{ $step['desc'] }}</td>
                                                            </tr>
                                                        </table>
                                                    </td>
                                                @endforeach
                                            </tr>
                                        </table>
                                    </td>
                                </tr>
                            </table>

                            {{-- Approval banner --}}
                            <table role="presentation" width="100%" cellspacing="0" cellpadding="0" border="0"
                                style="margin-top:16px;background-color:#FBF7EE;border:1.5px solid #E8D5A8;border-radius:10px;border-collapse:separate;">
                                <tr>
                                    <td style="padding:15px 16px;">
                                        <table role="presentation" width="100%" cellspacing="0" cellpadding="0"
                                            border="0">
                                            <tr>
                                                <td width="34" valign="top" style="width:34px;padding-right:13px;">
                                                    <table role="presentation" cellspacing="0" cellpadding="0"
                                                        border="0" style="border-collapse:separate;border-spacing:0;">
                                                        <tr>
                                                            <td width="34" height="34" align="center"
                                                                valign="middle" bgcolor="#E8D5A8"
                                                                style="width:34px;height:34px;background-color:#E8D5A8;border:1.5px solid #C8A96A;border-radius:8px;">
                                                                @if (!empty($checkBrandIconUrl))
                                                                    <img src="{{ $checkBrandIconUrl }}" width="14"
                                                                        height="14" alt=""
                                                                        style="{{ $mailIconStyle }}">
                                                                @endif
                                                            </td>
                                                        </tr>
                                                    </table>
                                                </td>
                                                <td valign="top">
                                                    <div
                                                        style="font-weight:500;font-size:15px;line-height:1.3;font-family:Georgia,'Times New Roman',serif;color:#3B2800;margin-bottom:3px;">
                                                        {{ __('mail.manufacturer_approved.approval_banner_title', ['date' => $approvedDate]) }}
                                                    </div>
                                                    <div
                                                        style="font-weight:400;font-size:12px;line-height:1.55;font-family:Arial,Helvetica,sans-serif;color:#8A8A8A;">
                                                        {{ __('mail.manufacturer_approved.approval_banner_body') }}
                                                    </div>
                                                </td>
                                            </tr>
                                        </table>
                                    </td>
                                </tr>
                            </table>
                        </td>
                    </tr>

                    {{-- Access state --}}
                    <tr>
                        <td bgcolor="#F8F8F8"
                            style="padding:28px 30px;background-color:#F8F8F8;border-bottom:1px solid #F0F0F0;">
                            <table role="presentation" width="100%" cellspacing="0" cellpadding="0" border="0"
                                style="margin-bottom:18px;border-collapse:separate;">
                                <tr>
                                    <td width="3" bgcolor="#E8D5A8"
                                        style="width:3px;background-color:#E8D5A8;border-radius:2px;font-size:0;line-height:0;">
                                        &nbsp;</td>
                                    <td
                                        style="padding-left:9px;font-weight:500;font-size:17px;line-height:1;font-family:Georgia,'Times New Roman',serif;color:#3B2800;">
                                        {!! __('mail.manufacturer_approved.access_section_title') !!}</td>
                                </tr>
                            </table>

                            <table role="presentation" width="100%" cellspacing="0" cellpadding="0" border="0"
                                style="border:1.5px solid #F0C040;border-radius:10px;border-collapse:separate;">
                                <tr>
                                    <td bgcolor="#FFF8E4"
                                        style="padding:14px 16px;background-color:#FFF8E4;border-bottom:1px solid #F0C040;">
                                        <table role="presentation" width="100%" cellspacing="0" cellpadding="0"
                                            border="0">
                                            <tr>
                                                <td width="32" valign="top" style="width:32px;">
                                                    <table role="presentation" cellspacing="0" cellpadding="0"
                                                        border="0" style="border-collapse:separate;border-spacing:0;">
                                                        <tr>
                                                            <td width="32" height="32" align="center"
                                                                valign="middle" bgcolor="#FFF8E4"
                                                                style="width:32px;height:32px;background-color:#FFF8E4;border:1.5px solid #F0C040;border-radius:8px;">
                                                                @if (!empty($lockWarnIconUrl))
                                                                    <img src="{{ $lockWarnIconUrl }}" width="14"
                                                                        height="14" alt=""
                                                                        style="{{ $mailIconStyle }}">
                                                                @endif
                                                            </td>
                                                        </tr>
                                                    </table>
                                                </td>
                                                <td valign="top" style="padding-left:12px;">
                                                    <div
                                                        style="font-weight:500;font-size:15px;line-height:1.3;font-family:Georgia,'Times New Roman',serif;color:#7A4D00;margin-bottom:3px;">
                                                        {{ __('mail.manufacturer_approved.access_title') }}</div>
                                                    <div
                                                        style="font-weight:500;font-size:11.5px;line-height:1.4;font-family:Arial,Helvetica,sans-serif;color:#8A8A8A;">
                                                        {{ __('mail.manufacturer_approved.access_sub') }}</div>
                                                </td>
                                            </tr>
                                        </table>
                                    </td>
                                </tr>
                                @foreach ($accessRows as $row)
                                    <tr>
                                        <td style="padding:11px 16px;border-top:1px solid #F0F0F0;">
                                            <table role="presentation" width="100%" cellspacing="0" cellpadding="0"
                                                border="0">
                                                <tr>
                                                    <td width="19" valign="top"
                                                        style="width:19px;line-height:0;font-size:0;padding-top:4px;">
                                                        <span
                                                            style="display:block;width:7px;height:7px;border-radius:50%;background-color:#C07800;">&nbsp;</span>
                                                    </td>
                                                    <td valign="top"
                                                        style="font-weight:500;font-size:12.5px;line-height:1.4;font-family:Arial,Helvetica,sans-serif;color:#B4B4B4;text-decoration:line-through;">
                                                        {{ $row }}</td>
                                                </tr>
                                            </table>
                                        </td>
                                    </tr>
                                @endforeach
                            </table>

                            <table role="presentation" width="100%" cellspacing="0" cellpadding="0" border="0"
                                style="margin-top:14px;background-color:#FFF8E4;border:1.5px solid #F0C040;border-left:4px solid #7A4D00;border-radius:8px;border-collapse:separate;">
                                <tr>
                                    <td style="padding:14px 16px;">
                                        <div
                                            style="font-weight:900;font-size:8.5px;line-height:1;font-family:Arial,Helvetica,sans-serif;letter-spacing:1.6px;text-transform:uppercase;color:#7A4D00;margin-bottom:5px;">
                                            {{ __('mail.manufacturer_approved.alert_heading') }}</div>
                                        <div
                                            style="font-weight:400;font-size:13px;line-height:1.65;font-family:Arial,Helvetica,sans-serif;color:#464646;">
                                            {!! __('mail.manufacturer_approved.alert_body') !!}</div>
                                    </td>
                                </tr>
                            </table>
                        </td>
                    </tr>

                    {{-- Price card --}}
                    <tr>
                        <td bgcolor="#FFFFFF"
                            style="padding:28px 30px;background-color:#FFFFFF;border-bottom:1px solid #F0F0F0;">
                            <table role="presentation" width="100%" cellspacing="0" cellpadding="0" border="0"
                                style="margin-bottom:18px;border-collapse:separate;">
                                <tr>
                                    <td width="3" bgcolor="#E8D5A8"
                                        style="width:3px;background-color:#E8D5A8;border-radius:2px;font-size:0;line-height:0;">
                                        &nbsp;</td>
                                    <td
                                        style="padding-left:9px;font-weight:500;font-size:17px;line-height:1;font-family:Georgia,'Times New Roman',serif;color:#3B2800;">
                                        {!! __('mail.manufacturer_approved.plan_section_title') !!}</td>
                                </tr>
                            </table>

                            <table role="presentation" width="100%" cellspacing="0" cellpadding="0" border="0"
                                bgcolor="#3B2800"
                                style="background-color:#3B2800;border-radius:10px;border-collapse:separate;">
                                <tr>
                                    <td style="padding:22px 20px;">
                                        <table role="presentation" width="100%" cellspacing="0" cellpadding="0"
                                            border="0">
                                            <tr>
                                                <td width="160" valign="top"
                                                    style="width:160px;padding-right:20px;vertical-align:top;">
                                                    <div
                                                        style="font-weight:900;font-size:8px;line-height:1;font-family:Arial,Helvetica,sans-serif;letter-spacing:2px;text-transform:uppercase;color:#C8A96A;margin-bottom:6px;">
                                                        {{ __('mail.manufacturer_approved.plan_eyebrow') }}</div>
                                                    <div
                                                        style="font-weight:600;font-size:44px;line-height:1;font-family:Georgia,'Times New Roman',serif;color:#FFFFFF;">
                                                        {{ $planAmount }}</div>
                                                    <div
                                                        style="padding-top:3px;font-weight:500;font-size:11.5px;line-height:1;font-family:Arial,Helvetica,sans-serif;color:rgba(255,255,255,0.38);">
                                                        {{ __('mail.manufacturer_approved.plan_period') }}</div>
                                                </td>
                                                <td valign="top" style="vertical-align:top;">
                                                    <div
                                                        style="font-weight:900;font-size:8px;line-height:1;font-family:Arial,Helvetica,sans-serif;letter-spacing:1.8px;text-transform:uppercase;color:#C8A96A;margin-bottom:9px;">
                                                        {{ __('mail.manufacturer_approved.plan_unlock_heading') }}</div>
                                                    @foreach ($planItems as $item)
                                                        <table role="presentation" width="100%" cellspacing="0"
                                                            cellpadding="0" border="0"
                                                            style="margin-bottom:7px;">
                                                            <tr>
                                                                <td width="12" valign="top"
                                                                    style="width:12px;padding-top:2px;font-weight:400;font-size:10px;line-height:1.5;font-family:Arial,Helvetica,sans-serif;color:#C8A96A;">
                                                                    –</td>
                                                                <td valign="top"
                                                                    style="font-weight:400;font-size:12px;line-height:1.5;font-family:Arial,Helvetica,sans-serif;color:rgba(255,255,255,0.55);">
                                                                    {{ $item }}</td>
                                                            </tr>
                                                        </table>
                                                    @endforeach
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
                        <td bgcolor="#FFFFFF"
                            style="padding:26px 30px 30px;background-color:#FFFFFF;border-top:1px solid #F0F0F0;">
                            <table role="presentation" cellspacing="0" cellpadding="0" border="0">
                                <tr>
                                    <td>
                                        <a href="{{ $ctaUrl }}"
                                            style="display:inline-block;padding:14px 30px;background-color:#9A7A3A;color:#FFFFFF;font-weight:900;font-size:12px;line-height:1;font-family:Arial,Helvetica,sans-serif;letter-spacing:0.6px;text-transform:uppercase;text-decoration:none;border-radius:8px;">{{ $ctaLabel ?? __('mail.manufacturer_approved.cta') }}</a>
                                    </td>
                                </tr>
                                <tr>
                                    <td style="padding-top:10px;">
                                        <a href="{{ $planDetailsUrl }}"
                                            style="font-weight:600;font-size:12.5px;line-height:1;font-family:Arial,Helvetica,sans-serif;color:#B4B4B4;text-decoration:none;">{{ __('mail.manufacturer_approved.cta_secondary') }}</a>
                                    </td>
                                </tr>
                                <tr>
                                    <td style="padding-top:12px;">
                                        <div
                                            style="font-weight:400;font-size:12px;line-height:1.75;font-family:Arial,Helvetica,sans-serif;color:#8A8A8A;">
                                            {!! __('mail.manufacturer_approved.cta_note', ['email' => e($supportEmail)]) !!}
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
                            <table role="presentation" width="100%" cellspacing="0" cellpadding="0" border="0">
                                <tr>
                                    <td
                                        style="font-weight:900;font-size:13px;line-height:1;font-family:Arial,Helvetica,sans-serif;color:#3B2800;letter-spacing:-0.4px;">
                                        sourcenest</td>
                                    <td align="right"
                                        style="font-weight:700;font-size:8px;line-height:1;font-family:Arial,Helvetica,sans-serif;letter-spacing:0.8px;text-transform:uppercase;color:#B4B4B4;">
                                        {{ __('mail.manufacturer_approved.footer_tag') }}</td>
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
                              
                                <span style="margin:0 5px;font-size:9px;color:#E6E6E6;">·</span>
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
