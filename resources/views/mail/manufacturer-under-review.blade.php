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
    $globeWatermarkUrl = public_url('images/mail/svg/globe-large.svg');
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
</head>

<body
    style="margin:0;padding:0;background-color:#F0F0F0;font-family:Arial,Helvetica,sans-serif;-webkit-text-size-adjust:100%;-ms-text-size-adjust:100%;">
    <span
        style="display:none !important;visibility:hidden;mso-hide:all;font-size:1px;color:#F0F0F0;line-height:1px;max-height:0;max-width:0;opacity:0;overflow:hidden;">{{ __('mail.manufacturer_under_review.preheader') }}</span>

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
                                            style="display:inline-block;padding:4px 12px;border-radius:20px;border:1.5px solid rgba(200,169,106,0.18);background-color:transparent;font-weight:700;font-size:9px;line-height:1;font-family:Arial,Helvetica,sans-serif;letter-spacing:0.4px;text-transform:uppercase;color:rgba(200,169,106,0.5);">{{ __('mail.manufacturer_under_review.badge') }}</span>
                                    </td>
                                </tr>
                            </table>
                        </td>
                    </tr>

                    {{-- Hero H1: tinted with globe watermark --}}
                    <tr>
                        <td bgcolor="#FBF7EE"
                            style="padding:34px 30px 40px;background-color:#FBF7EE;border-bottom:1.5px solid #E8D5A8; overflow: hidden;">
                            <table role="presentation" width="100%" cellspacing="0" cellpadding="0" border="0">
                                <tr>
                                    <td valign="top" style="padding-right:16px;">
                                        <table role="presentation" cellspacing="0" cellpadding="0" border="0"
                                            style="margin-bottom:14px;">
                                            <tr>
                                                <td
                                                    style="padding:4px 11px;border-radius:20px;border:1.5px solid #E8D5A8;background-color:#FBF7EE;">
                                                    <table role="presentation" cellspacing="0" cellpadding="0"
                                                        border="0">
                                                        <tr>
                                                            <td width="5" valign="middle"
                                                                style="line-height:0;font-size:0;">
                                                                <span
                                                                    style="display:block;width:5px;height:5px;border-radius:50%;background-color:#9A7A3A;">&nbsp;</span>
                                                            </td>
                                                            <td valign="middle"
                                                                style="padding-left:5px;font-weight:800;font-size:8.5px;line-height:1;font-family:Arial,Helvetica,sans-serif;letter-spacing:1.2px;text-transform:uppercase;color:#666666;">
                                                                {{ __('mail.manufacturer_under_review.pill') }}</td>
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
                                                    {{ __('mail.manufacturer_under_review.eyebrow') }}</td>
                                            </tr>
                                        </table>
                                        <div
                                            style="font-weight:500;font-size:31px;line-height:1.17;font-family:Georgia,'Times New Roman',serif;color:#3B2800;letter-spacing:-0.2px;">
                                            {!! __('mail.manufacturer_under_review.hero_headline') !!}
                                        </div>
                                        <div
                                            style="padding-top:12px;font-weight:400;font-size:13.5px;line-height:1.78;font-family:Arial,Helvetica,sans-serif;color:#666666;max-width:380px;">
                                            {{ __('mail.manufacturer_under_review.hero_subheadline') }}</div>
                                    </td>
                                    <td width="120" align="right" valign="top" style="line-height:0;font-size:0;">
                                        @if (!empty($globeWatermarkUrl))
                                            <img src="{{ $globeWatermarkUrl }}" width="110" height="110"
                                                alt=""
                                                style="display:block;border:0;outline:none;opacity:0.06; position: absolute;top: 4%;right: 29%;width: 200px; height: 200px;">
                                        @endif
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
                                                        $circleBg =
                                                            $step['state'] === 'current' ? '#FFF8E4' : '#F0F0F0';
                                                        $circleBorder =
                                                            $step['state'] === 'current' ? '#C07800' : '#D6D6D6';
                                                        $numberColor =
                                                            $step['state'] === 'current' ? '#7A4D00' : '#8A8A8A';
                                                        $labelColor =
                                                            $step['state'] === 'current' ? '#7A4D00' : '#8A8A8A';
                                                        $descColor =
                                                            $step['state'] === 'current' ? '#C07800' : '#B4B4B4';
                                                    @endphp
                                                    <td width="135" align="center" valign="top"
                                                        style="width:25%;padding:0 4px;">
                                                        <table role="presentation" width="100%" cellspacing="0"
                                                            cellpadding="0" border="0" align="center">
                                                            <tr>
                                                                <td align="center" style="padding-bottom:8px;">
                                                                    <table role="presentation" cellspacing="0"
                                                                        cellpadding="0" border="0"
                                                                        align="center">
                                                                        <tr>
                                                                            <td width="34" height="34"
                                                                                align="center" valign="middle"
                                                                                bgcolor="{{ $circleBg }}"
                                                                                style="width:34px;height:34px;background-color:{{ $circleBg }};border:2px solid {{ $circleBorder }};border-radius:50%;font-weight:800;font-size:11px;line-height:34px;font-family:Arial,Helvetica,sans-serif;color:{{ $numberColor }};">
                                                                                {{ $step['number'] }}</td>
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

                            {{-- Status chip --}}
                            <table role="presentation" width="100%" cellspacing="0" cellpadding="0"
                                border="0"
                                style="margin-top:16px;background-color:#FFF8E4;border:1.5px solid #F0C040;border-radius:8px;border-collapse:separate;">
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
                                                                    style="display:block;width:7px;height:7px;border-radius:50%;background-color:#C07800;">&nbsp;</span>
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
                        </td>
                    </tr>

                    {{-- Access state --}}
                    <tr>
                        <td bgcolor="#F8F8F8"
                            style="padding:28px 30px;background-color:#F8F8F8;border-bottom:1px solid #F0F0F0;">
                            <table role="presentation" width="100%" cellspacing="0" cellpadding="0"
                                border="0" style="margin-bottom:18px;">
                                <tr>
                                    <td width="3" bgcolor="#E8D5A8"
                                        style="width:3px;background-color:#E8D5A8;border-radius:2px;font-size:0;line-height:0;">
                                        &nbsp;</td>
                                    <td
                                        style="padding-left:9px;font-weight:500;font-size:17px;line-height:1;font-family:Georgia,'Times New Roman',serif;color:#3B2800;">
                                        {!! __('mail.manufacturer_under_review.access_section_title') !!}</td>
                                </tr>
                            </table>

                            <table role="presentation" width="100%" cellspacing="0" cellpadding="0"
                                border="0"
                                style="border:1.5px solid #EEAAAA;border-radius:10px;border-collapse:separate;">
                                <tr>
                                    <td bgcolor="#FEF2F2"
                                        style="padding:14px 16px;background-color:#FEF2F2;border-bottom:1px solid #EEAAAA;">
                                        <table role="presentation" width="100%" cellspacing="0" cellpadding="0"
                                            border="0">
                                            <tr>
                                                <td width="32" valign="top" style="width:32px;">
                                                    <table role="presentation" cellspacing="0" cellpadding="0"
                                                        border="0">
                                                        <tr>
                                                            <td width="32" height="32" align="center"
                                                                valign="middle" bgcolor="#FEF2F2"
                                                                style="width:32px;height:32px;background-color:#FEF2F2;border:1.5px solid #EEAAAA;border-radius:8px;">
                                                                @if (!empty($lockIconUrl))
                                                                    <img src="{{ $lockIconUrl }}" width="14"
                                                                        height="14" alt=""
                                                                        style="{{ $mailIconStyle }}">
                                                                @endif
                                                            </td>
                                                        </tr>
                                                    </table>
                                                </td>
                                                <td valign="top" style="padding-left:12px;">
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
                                        <td style="padding:11px 16px;border-top:1px solid #F0F0F0;">
                                            <table role="presentation" width="100%" cellspacing="0"
                                                cellpadding="0" border="0">
                                                <tr>
                                                    <td width="19" valign="top"
                                                        style="width:19px;line-height:0;font-size:0;padding-top:4px;">
                                                        <span
                                                            style="display:block;width:7px;height:7px;border-radius:50%;background-color:#C42828;">&nbsp;</span>
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

                            <table role="presentation" width="100%" cellspacing="0" cellpadding="0"
                                border="0"
                                style="margin-top:14px;background-color:#FFF8E4;border:1.5px solid #F0C040;border-left:4px solid #7A4D00;border-radius:8px;border-collapse:separate;">
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
                        </td>
                    </tr>

                    {{-- Review timeline --}}
                    <tr>
                        <td bgcolor="#FFFFFF"
                            style="padding:28px 30px;background-color:#FFFFFF;border-bottom:1px solid #F0F0F0;">
                            <table role="presentation" width="100%" cellspacing="0" cellpadding="0"
                                border="0" style="margin-bottom:18px;">
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
                                <a href="{{ $frontendUrl }}/unsubscribe"
                                    style="color:#B4B4B4;text-decoration:none;">Unsubscribe</a>
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
