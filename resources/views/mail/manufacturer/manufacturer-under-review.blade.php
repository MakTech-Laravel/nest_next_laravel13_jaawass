@php
    $platformName = config('app.name', 'SourceNest');
    $logoUrl = public_url('images/mail/sourcenest-logo-white.png');
    $frontendUrl = rtrim((string) config('app.frontend_url', config('app.url')), '/');
    $supportEmail = config('mail.from.address', 'support@sourcenest.com');
    $mailIconStyle = 'display:block;border:0;outline:none;text-decoration:none;margin:0 auto;';
    $recipientName = trim($name ?? ($firstName ?? '')) !== '' ? trim($name ?? ($firstName ?? '')) : 'there';
    $companyName = trim($company ?? '') !== '' ? trim($company ?? '') : $platformName;
    $submittedDate = $submittedAt ?? now()->format('F j, Y');
    $ctaUrl = $ctaUrl ?? \App\Support\Mail\MailNotificationHelper::frontendUrl('dashboard/manufacturer');
    $globeWatermarkUrl = public_url('images/mail/svg/globe-hero-watermark.svg');
    $lockIconUrl = public_url('images/mail/svg/lock.svg');
    $flowSteps = [
        [
            'state' => 'current',
            'number' => '1',
            'label' => __('mail.manufacturer_under_review.flow_step_1_label'),
            'desc' => __('mail.manufacturer_under_review.flow_step_1_desc'),
        ],
        [
            'state' => 'upcoming',
            'number' => '2',
            'label' => __('mail.manufacturer_under_review.flow_step_2_label'),
            'desc' => __('mail.manufacturer_under_review.flow_step_2_desc'),
        ],
        [
            'state' => 'upcoming',
            'number' => '3',
            'label' => __('mail.manufacturer_under_review.flow_step_3_label'),
            'desc' => __('mail.manufacturer_under_review.flow_step_3_desc'),
        ],
        [
            'state' => 'upcoming',
            'number' => '4',
            'label' => __('mail.manufacturer_under_review.flow_step_4_label'),
            'desc' => __('mail.manufacturer_under_review.flow_step_4_desc'),
        ],
    ];
    $accessRows = [
        __('mail.manufacturer_under_review.access_row_1'),
        __('mail.manufacturer_under_review.access_row_2'),
        __('mail.manufacturer_under_review.access_row_3'),
        __('mail.manufacturer_under_review.access_row_4'),
    ];
    $timelineItems = [
        [
            'tag' => __('mail.manufacturer_under_review.timeline_1_tag'),
            'title' => __('mail.manufacturer_under_review.timeline_1_title'),
            'body' => __('mail.manufacturer_under_review.timeline_1_body'),
        ],
        [
            'tag' => __('mail.manufacturer_under_review.timeline_2_tag'),
            'title' => __('mail.manufacturer_under_review.timeline_2_title'),
            'body' => __('mail.manufacturer_under_review.timeline_2_body'),
        ],
        [
            'tag' => __('mail.manufacturer_under_review.timeline_3_tag'),
            'title' => __('mail.manufacturer_under_review.timeline_3_title'),
            'body' => __('mail.manufacturer_under_review.timeline_3_body'),
        ],
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
    <title>{{ __('mail.manufacturer_under_review.subject') }}</title>
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
        .email-card { border-collapse: separate !important; border-spacing: 0 !important; }
        @media only screen and (max-width: 640px) {
            .email-outer { padding: 12px 8px !important; }
            .email-card { border-radius: 10px !important; }
            .email-pad { padding-left: 18px !important; padding-right: 18px !important; }
            .email-hero-title { font-size: 24px !important; line-height: 1.2 !important; }
        }
    </style>
</head>

<body
    style="margin:0;padding:0;background-color:#F0F0F0;font-family:Arial,Helvetica,sans-serif;-webkit-text-size-adjust:100%;-ms-text-size-adjust:100%;">
    <span
        style="display:none !important;visibility:hidden;mso-hide:all;font-size:1px;color:#F0F0F0;line-height:1px;max-height:0;max-width:0;opacity:0;overflow:hidden;">{{ __('mail.manufacturer_under_review.preheader') }}</span>

    <table role="presentation" width="100%" cellspacing="0" cellpadding="0" border="0"
        style="background-color:#F0F0F0;">
        <tr>
            <td align="center" class="email-outer" style="padding:24px 12px;">
                {{-- Wrapper div: tables ignore overflow/radius in many clients & browsers --}}
                <!--[if mso]>
                <table role="presentation" width="600" cellspacing="0" cellpadding="0" border="0" style="width:600px;background-color:#FFFFFF;border:1px solid #E6E6E6;"><tr><td>
                <![endif]-->
                <!--[if !mso]><!-->
                <div class="email-card" style="max-width:600px;width:100%;margin:0 auto;background-color:#FFFFFF;border:1px solid #E6E6E6;border-radius:14px;overflow:hidden;">
                <!--<![endif]-->
                <table role="presentation" width="600" cellspacing="0" cellpadding="0" border="0"
                    style="max-width:600px;width:100%;background-color:#FFFFFF;border-collapse:collapse;border-spacing:0;">

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
                                            style="display:inline-block;padding:4px 12px;border-radius:20px;border:1.5px solid rgba(200,169,106,0.18);background-color:transparent;font-weight:700;font-size:9px;line-height:1;font-family:Arial,Helvetica,sans-serif;letter-spacing:0.4px;text-transform:uppercase;color:rgba(200,169,106,0.5);">{{ __('mail.manufacturer_under_review.badge') }}</span>
                                    </td>
                                </tr>
                            </table>
                        </td>
                    </tr>

                    {{-- Hero H1: tinted with globe watermark (matches design — large faint globe, cropped right/top) --}}
                    <tr>
                        <td class="email-pad" bgcolor="#FBF7EE" background="{{ $globeWatermarkUrl }}"
                            style="padding:34px 30px 40px;background-color:#FBF7EE;background-image:url('{{ $globeWatermarkUrl }}');background-repeat:no-repeat;background-position:right -24px top -24px;background-size:210px 210px;border-bottom:1.5px solid #E8D5A8;">
                            <!--[if gte mso 9]>
                            <v:rect xmlns:v="urn:schemas-microsoft-com:vml" fill="true" stroke="false" style="position:absolute;width:600px;height:220px;">
                                <v:fill type="frame" src="{{ $globeWatermarkUrl }}" color="#FBF7EE" />
                            </v:rect>
                            <![endif]-->
                            <table role="presentation" width="100%" cellspacing="0" cellpadding="0" border="0">
                                <tr>
                                    <td valign="top" style="max-width:420px;">
                                        <table role="presentation" cellspacing="0" cellpadding="0" border="0"
                                            style="margin-bottom:14px;">
                                            <tr>
                                                <td>
                                                    <span
                                                        style="display:inline-block;padding:4px 11px;border-radius:20px;border:1.5px solid #E8D5A8;background-color:#FBF7EE;">
                                                        <span
                                                            style="display:inline-block;width:5px;height:5px;border-radius:50%;background-color:#9A7A3A;vertical-align:middle;margin-right:5px;">&nbsp;</span>
                                                        <span
                                                            style="font-weight:800;font-size:8.5px;line-height:1;font-family:Arial,Helvetica,sans-serif;letter-spacing:1.2px;text-transform:uppercase;color:#666666;vertical-align:middle;">{{ __('mail.manufacturer_under_review.pill') }}</span>
                                                    </span>
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
                                                    {{ __('mail.manufacturer_under_review.eyebrow') }}</td>
                                            </tr>
                                        </table>
                                        <div class="email-hero-title"
                                            style="font-weight:500;font-size:31px;line-height:1.17;font-family:Georgia,'Times New Roman',serif;color:#3B2800;letter-spacing:-0.2px;">
                                            {!! __('mail.manufacturer_under_review.hero_headline') !!}
                                        </div>
                                        <div
                                            style="padding-top:12px;font-weight:400;font-size:13.5px;line-height:1.78;font-family:Arial,Helvetica,sans-serif;color:#666666;max-width:380px;">
                                            {{ __('mail.manufacturer_under_review.hero_subheadline') }}</div>
                                    </td>
                                </tr>
                            </table>
                        </td>
                    </tr>

                    {{-- Greeting + flow tracker + status --}}
                    <tr>
                        <td bgcolor="#FFFFFF"
                            style="padding:28px 30px;background-color:#FFFFFF;border-bottom:1px solid #F0F0F0;">
                            <div
                                style="font-weight:500;font-size:17px;line-height:1;font-family:Georgia,'Times New Roman',serif;color:#3B2800;margin-bottom:13px;">
                                {{ __('mail.manufacturer_under_review.greeting', ['name' => $recipientName]) }}</div>
                            <p
                                style="margin:0;font-weight:400;font-size:13.5px;line-height:1.88;font-family:Arial,Helvetica,sans-serif;color:#464646;">
                                {!! __('mail.manufacturer_under_review.intro', ['company' => e($companyName)]) !!}</p>

                            {{-- Flow tracker — fixed-height circles + bgcolor connector bars --}}
                            <!--[if !mso]><!-->
                            <div style="margin-top:20px;background-color:#F8F8F8;border:1.5px solid #E6E6E6;border-radius:12px;overflow:hidden;">
                            <!--<![endif]-->
                            <!--[if mso]>
                            <table role="presentation" width="100%" cellspacing="0" cellpadding="0" border="0" style="margin-top:20px;width:100%;background-color:#F8F8F8;border:1.5px solid #E6E6E6;"><tr><td>
                            <![endif]-->
                            <table role="presentation" width="100%" cellspacing="0" cellpadding="0" border="0"
                                style="width:100%;background-color:#F8F8F8;border-collapse:collapse;">
                                <tr>
                                    <td style="padding:22px 10px 18px;">

                                        {{-- Step circles + continuous connector line --}}
                                        <table role="presentation" width="100%" cellspacing="0" cellpadding="0" border="0">
                                            <tr>
                                                @foreach ($flowSteps as $index => $step)
                                                    @php
                                                        $isCurrent = $step['state'] === 'current';
                                                        $isLast = $index === count($flowSteps) - 1;
                                                        $isFirst = $index === 0;
                                                        $circleBg = $isCurrent ? '#FFF8E4' : '#F0F0F0';
                                                        $circleBorder = $isCurrent ? '#C07800' : '#D6D6D6';
                                                        $numberColor = $isCurrent ? '#7A4D00' : '#8A8A8A';
                                                        // Continuous line: gold only between step 1 (current) and step 2
                                                        $leftBar = $isFirst ? '#F8F8F8' : ($index === 1 ? '#C07800' : '#D6D6D6');
                                                        $rightBar = $isLast ? '#F8F8F8' : ($isCurrent ? '#C07800' : '#D6D6D6');
                                                    @endphp
                                                    <td width="25%" align="center" valign="middle" style="width:25%;">
                                                        <table role="presentation" width="100%" cellspacing="0" cellpadding="0" border="0">
                                                            <tr>
                                                                {{-- Left connector bar --}}
                                                                <td width="50%" valign="middle" style="width:50%;font-size:0;line-height:0;">
                                                                    <table role="presentation" width="100%" cellspacing="0" cellpadding="0" border="0">
                                                                        <tr>
                                                                            <td height="2" bgcolor="{{ $leftBar }}"
                                                                                style="height:2px;line-height:2px;font-size:0;background-color:{{ $leftBar }};">&nbsp;</td>
                                                                        </tr>
                                                                    </table>
                                                                </td>
                                                                {{-- Circle (fixed size — same for active and inactive) --}}
                                                                <td width="40" height="40" align="center" valign="middle"
                                                                    style="width:40px;height:40px;min-width:40px;padding:0;">
                                                                    <table role="presentation" width="36" height="36" cellspacing="0" cellpadding="0" border="0" align="center"
                                                                        style="width:36px;height:36px;border-collapse:separate;border-spacing:0;">
                                                                        <tr>
                                                                            <td width="36" height="36" align="center" valign="middle"
                                                                                bgcolor="{{ $circleBg }}"
                                                                                style="width:36px;height:36px;min-width:36px;max-width:36px;max-height:36px;box-sizing:border-box;background-color:{{ $circleBg }};border:2px solid {{ $circleBorder }};border-radius:18px;font-weight:800;font-size:12px;line-height:32px;mso-line-height-rule:exactly;font-family:Arial,Helvetica,sans-serif;color:{{ $numberColor }};text-align:center;{{ $isCurrent ? 'box-shadow:0 0 0 3px rgba(192,120,0,0.18);' : '' }}">
                                                                                {{ $step['number'] }}
                                                                            </td>
                                                                        </tr>
                                                                    </table>
                                                                </td>
                                                                {{-- Right connector bar --}}
                                                                <td width="50%" valign="middle" style="width:50%;font-size:0;line-height:0;">
                                                                    <table role="presentation" width="100%" cellspacing="0" cellpadding="0" border="0">
                                                                        <tr>
                                                                            <td height="2" bgcolor="{{ $rightBar }}"
                                                                                style="height:2px;line-height:2px;font-size:0;background-color:{{ $rightBar }};">&nbsp;</td>
                                                                        </tr>
                                                                    </table>
                                                                </td>
                                                            </tr>
                                                        </table>
                                                    </td>
                                                @endforeach
                                            </tr>
                                        </table>

                                        {{-- Labels under circles --}}
                                        <table role="presentation" width="100%" cellspacing="0" cellpadding="0" border="0" style="margin-top:10px;">
                                            <tr>
                                                @foreach ($flowSteps as $step)
                                                    @php
                                                        $isCurrent = $step['state'] === 'current';
                                                        $labelColor = $isCurrent ? '#7A4D00' : '#8A8A8A';
                                                        $descColor = $isCurrent ? '#C07800' : '#B4B4B4';
                                                    @endphp
                                                    <td width="25%" align="center" valign="top" style="width:25%;padding:0 4px;">
                                                        <div style="font-weight:800;font-size:9px;line-height:1.3;font-family:Arial,Helvetica,sans-serif;letter-spacing:0.5px;text-transform:uppercase;color:{{ $labelColor }};text-align:center;">
                                                            {{ $step['label'] }}
                                                        </div>
                                                        <div style="padding-top:4px;font-weight:400;font-size:10px;line-height:1.4;font-family:Arial,Helvetica,sans-serif;color:{{ $descColor }};text-align:center;">
                                                            {{ $step['desc'] }}
                                                        </div>
                                                    </td>
                                                @endforeach
                                            </tr>
                                        </table>

                                    </td>
                                </tr>
                            </table>
                            <!--[if mso]>
                            </td></tr></table>
                            <![endif]-->
                            <!--[if !mso]><!-->
                            </div>
                            <!--<![endif]-->

                            {{-- Status chip --}}
                            <!--[if !mso]><!-->
                            <div style="margin-top:16px;background-color:#FFF8E4;border:1.5px solid #F0C040;border-radius:8px;overflow:hidden;">
                            <!--<![endif]-->
                            <!--[if mso]>
                            <table role="presentation" width="100%" cellspacing="0" cellpadding="0" border="0" style="margin-top:16px;width:100%;background-color:#FFF8E4;border:1.5px solid #F0C040;"><tr><td>
                            <![endif]-->
                            <table role="presentation" width="100%" cellspacing="0" cellpadding="0" border="0"
                                style="width:100%;background-color:#FFF8E4;border-collapse:collapse;">
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
                                                                    style="display:inline-block;width:7px;height:7px;border-radius:50%;background-color:#C07800;vertical-align:middle;">&nbsp;</span>
                                                            </td>
                                                            <td valign="middle"
                                                                style="font-weight:700;font-size:12.5px;line-height:1;font-family:Arial,Helvetica,sans-serif;color:#1C1C1C;">
                                                                {{ __('mail.manufacturer_under_review.status_label') }}
                                                            </td>
                                                        </tr>
                                                    </table>
                                                </td>
                                                <td align="right" valign="middle"
                                                    style="font-weight:500;font-size:11px;line-height:1;font-family:Arial,Helvetica,sans-serif;color:#8A8A8A;white-space:nowrap;">
                                                    {{ $submittedDate }}</td>
                                            </tr>
                                        </table>
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

                    {{-- Access state --}}
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
                                        {!! __('mail.manufacturer_under_review.access_section_title') !!}</td>
                                </tr>
                            </table>

                            {{-- Access card — div wrapper for reliable border/radius in email --}}
                            <!--[if !mso]><!-->
                            <div style="background-color:#FFFFFF;border:1.5px solid #EEAAAA;border-radius:10px;overflow:hidden;">
                            <!--<![endif]-->
                            <!--[if mso]>
                            <table role="presentation" width="100%" cellspacing="0" cellpadding="0" border="0" style="width:100%;background-color:#FFFFFF;border:1.5px solid #EEAAAA;"><tr><td>
                            <![endif]-->
                            <table role="presentation" width="100%" cellspacing="0" cellpadding="0" border="0"
                                style="width:100%;background-color:#FFFFFF;border-collapse:collapse;">
                                <tr>
                                    <td bgcolor="#FEF2F2"
                                        style="padding:14px 16px;background-color:#FEF2F2;border-bottom:1px solid #EEAAAA;">
                                        <table role="presentation" width="100%" cellspacing="0" cellpadding="0"
                                            border="0">
                                            <tr>
                                                <td width="32" valign="middle" style="width:32px;">
                                                    <table role="presentation" cellspacing="0" cellpadding="0" border="0"
                                                        style="border-collapse:separate;border-spacing:0;">
                                                        <tr>
                                                            <td width="32" height="32" align="center" valign="middle"
                                                                bgcolor="#FEF2F2"
                                                                style="width:32px;height:32px;min-width:32px;background-color:#FEF2F2;border:1.5px solid #EEAAAA;border-radius:8px;text-align:center;vertical-align:middle;line-height:32px;mso-line-height-rule:exactly;">
                                                                @if (!empty($lockIconUrl))
                                                                    <img src="{{ $lockIconUrl }}" width="14" height="14"
                                                                        alt=""
                                                                        style="display:block;width:14px;height:14px;margin:0 auto;border:0;outline:none;text-decoration:none;">
                                                                @endif
                                                            </td>
                                                        </tr>
                                                    </table>
                                                </td>
                                                <td valign="middle" style="padding-left:12px;">
                                                    <div
                                                        style="font-weight:500;font-size:15px;line-height:1.3;font-family:Georgia,'Times New Roman',serif;color:#7A1818;margin-bottom:3px;">
                                                        {{ __('mail.manufacturer_under_review.access_title') }}</div>
                                                    <div
                                                        style="font-weight:500;font-size:11.5px;line-height:1.4;font-family:Arial,Helvetica,sans-serif;color:#8A8A8A;">
                                                        {{ __('mail.manufacturer_under_review.access_sub') }}</div>
                                                </td>
                                            </tr>
                                        </table>
                                    </td>
                                </tr>
                                @foreach ($accessRows as $row)
                                    <tr>
                                        <td bgcolor="#FFFFFF"
                                            style="padding:11px 16px;background-color:#FFFFFF;@if (!$loop->first) border-top:1px solid #F0F0F0; @endif">
                                            <table role="presentation" width="100%" cellspacing="0"
                                                cellpadding="0" border="0">
                                                <tr>
                                                    <td width="19" valign="middle"
                                                        style="width:19px;line-height:0;font-size:0;">
                                                        <span
                                                            style="display:inline-block;width:7px;height:7px;border-radius:50%;background-color:#C42828;vertical-align:middle;">&nbsp;</span>
                                                    </td>
                                                    <td valign="middle"
                                                        style="font-weight:500;font-size:12.5px;line-height:1.4;font-family:Arial,Helvetica,sans-serif;color:#B4B4B4;text-decoration:line-through;">
                                                        {{ $row }}</td>
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

                            {{-- Alert note — div wrapper for reliable border/radius --}}
                            <!--[if !mso]><!-->
                            <div style="margin-top:14px;background-color:#FFF8E4;border:1.5px solid #F0C040;border-left:4px solid #7A4D00;border-radius:8px;overflow:hidden;">
                            <!--<![endif]-->
                            <!--[if mso]>
                            <table role="presentation" width="100%" cellspacing="0" cellpadding="0" border="0" style="margin-top:14px;width:100%;background-color:#FFF8E4;border:1.5px solid #F0C040;border-left:4px solid #7A4D00;"><tr><td>
                            <![endif]-->
                            <table role="presentation" width="100%" cellspacing="0" cellpadding="0" border="0"
                                style="width:100%;background-color:#FFF8E4;border-collapse:collapse;">
                                <tr>
                                    <td style="padding:14px 16px;">
                                        <div
                                            style="font-weight:900;font-size:8.5px;line-height:1;font-family:Arial,Helvetica,sans-serif;letter-spacing:1.6px;text-transform:uppercase;color:#7A4D00;margin-bottom:5px;">
                                            {{ __('mail.manufacturer_under_review.alert_heading') }}</div>
                                        <div
                                            style="font-weight:400;font-size:13px;line-height:1.65;font-family:Arial,Helvetica,sans-serif;color:#464646;">
                                            {!! __('mail.manufacturer_under_review.alert_body') !!}</div>
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

                    {{-- Review timeline --}}
                    <tr>
                        <td bgcolor="#FFFFFF"
                            style="padding:28px 30px;background-color:#FFFFFF;border-bottom:1px solid #F0F0F0;">
                            <table role="presentation" width="100%" cellspacing="0" cellpadding="0"
                                border="0" style="margin-bottom:18px;border-collapse:separate;">
                                <tr>
                                    <td width="3" bgcolor="#E8D5A8"
                                        style="width:3px;background-color:#E8D5A8;border-radius:2px;font-size:0;line-height:0;">
                                        &nbsp;</td>
                                    <td
                                        style="padding-left:9px;font-weight:500;font-size:17px;line-height:1;font-family:Georgia,'Times New Roman',serif;color:#3B2800;">
                                        {!! __('mail.manufacturer_under_review.review_section_title') !!}</td>
                                </tr>
                            </table>

                            @foreach ($timelineItems as $item)
                                <table role="presentation" width="100%" cellspacing="0" cellpadding="0"
                                    border="0"
                                    @if (!$loop->last) style="border-bottom:1px solid #F0F0F0;" @endif>
                                    <tr>
                                        <td width="68" valign="top"
                                            style="padding:13px 0;font-weight:800;font-size:9px;line-height:1;font-family:Arial,Helvetica,sans-serif;letter-spacing:0.7px;text-transform:uppercase;color:#9A7A3A;">
                                            {{ $item['tag'] }}</td>
                                        <td valign="top" style="padding:13px 0;">
                                            <div
                                                style="font-weight:700;font-size:13px;line-height:1;font-family:Arial,Helvetica,sans-serif;color:#1C1C1C;margin-bottom:3px;">
                                                {{ $item['title'] }}</div>
                                            <div
                                                style="font-weight:400;font-size:12px;line-height:1.55;font-family:Arial,Helvetica,sans-serif;color:#8A8A8A;">
                                                {{ $item['body'] }}</div>
                                        </td>
                                    </tr>
                                </table>
                            @endforeach
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
                                            style="display:inline-block;padding:14px 30px;background-color:#3B2800;color:#FFFFFF;font-weight:900;font-size:12px;line-height:1;font-family:Arial,Helvetica,sans-serif;letter-spacing:0.6px;text-transform:uppercase;text-decoration:none;border-radius:8px;">{{ $ctaLabel ?? __('mail.manufacturer_under_review.cta') }}</a>
                                    </td>
                                </tr>
                                <tr>
                                    <td style="padding-top:10px;">
                                        <a href="mailto:{{ $supportEmail }}"
                                            style="font-weight:600;font-size:12.5px;line-height:1;font-family:Arial,Helvetica,sans-serif;color:#B4B4B4;text-decoration:none;">{{ __('mail.manufacturer_under_review.cta_ghost', ['email' => $supportEmail]) }}</a>
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
                                        {{ __('mail.manufacturer_under_review.footer_tag') }}</td>
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
                <!--[if !mso]><!-->
                </div>
                <!--<![endif]-->
                <!--[if mso]>
                </td></tr></table>
                <![endif]-->
            </td>
        </tr>
    </table>
</body>

</html>
