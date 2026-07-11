@php
    $platformName = config('app.name', 'SourceNest');
    $logoUrl = public_url('images/mail/sourcenest-logo-white.png');
    $frontendUrl = rtrim((string) config('app.frontend_url', config('app.url')), '/');
    $supportEmail = config('mail.from.address', 'support@sourcenest.com');
    $mailIconStyle = 'display:block;border:0;outline:none;text-decoration:none;margin:0 auto;';
    $recipientName = trim($manufacturerName ?? ($name ?? ($recipientName ?? ''))) !== ''
        ? trim($manufacturerName ?? ($name ?? ($recipientName ?? '')))
        : 'there';
    $planName = trim($planName ?? '') !== '' ? trim($planName) : __('subscription.plan');
    $startsAt = $startsAt ?? ($activatedAt ?? now()->format('F j, Y'));
    $endsAt = $endsAt ?? '';
    $statusLabel = ucfirst((string) ($status ?? 'active'));
    $paidDisplay = $paidAmountDisplay
        ?? (isset($paidAmount) && $paidAmount !== null && $paidAmount !== ''
            ? (str_starts_with((string) $paidAmount, '$') ? (string) $paidAmount : '$'.ltrim((string) $paidAmount, '$').' USD')
            : null);
    $ctaUrl = $ctaUrl ?? \App\Support\Mail\MailNotificationHelper::frontendUrl('dashboard/manufacturer');
    $productsUrl = $productsUrl ?? \App\Support\Mail\MailNotificationHelper::frontendUrl('dashboard/manufacturer/products');
    $billingUrl = $billingUrl ?? \App\Support\Mail\MailNotificationHelper::frontendUrl('settings/billing');
    $globeWatermarkUrl = public_url('images/mail/svg/globe-watermark-activated.svg');
    $checkFlowIconUrl = public_url('images/mail/svg/check-flow.svg');
    $checkSuccessIconUrl = public_url('images/mail/svg/check-success.svg');
    $flowSteps = [
        [
            'state' => 'done',
            'icon' => $checkFlowIconUrl,
            'label' => __('mail.subscription_created.flow_step_1_label'),
            'desc' => __('mail.subscription_created.flow_step_1_desc'),
        ],
        [
            'state' => 'done',
            'icon' => $checkFlowIconUrl,
            'label' => __('mail.subscription_created.flow_step_2_label'),
            'desc' => __('mail.subscription_created.flow_step_2_desc'),
        ],
        [
            'state' => 'done',
            'icon' => $checkFlowIconUrl,
            'label' => __('mail.subscription_created.flow_step_3_label'),
            'desc' => __('mail.subscription_created.flow_step_3_desc'),
        ],
        [
            'state' => 'active',
            'icon' => $checkFlowIconUrl,
            'label' => __('mail.subscription_created.flow_step_4_label'),
            'desc' => __('mail.subscription_created.flow_step_4_desc'),
        ],
    ];
    $accessRows = [
        __('mail.subscription_created.access_row_1'),
        __('mail.subscription_created.access_row_2'),
        __('mail.subscription_created.access_row_3'),
        __('mail.subscription_created.access_row_4'),
    ];
    $nextSteps = [
        [
            'n' => '1',
            'title' => __('mail.subscription_created.next_step_1_title'),
            'body' => __('mail.subscription_created.next_step_1_body'),
        ],
        [
            'n' => '2',
            'title' => __('mail.subscription_created.next_step_2_title'),
            'body' => __('mail.subscription_created.next_step_2_body'),
        ],
        [
            'n' => '3',
            'title' => __('mail.subscription_created.next_step_3_title'),
            'body' => __('mail.subscription_created.next_step_3_body'),
        ],
    ];
    $buyerJourney = [
        [
            'n' => '01',
            'body' => __('mail.subscription_created.journey_1'),
        ],
        [
            'n' => '02',
            'body' => __('mail.subscription_created.journey_2'),
        ],
        [
            'n' => '03',
            'body' => __('mail.subscription_created.journey_3'),
        ],
        [
            'n' => '04',
            'body' => __('mail.subscription_created.journey_4'),
        ],
    ];
    $detailRows = array_filter([
        [
            'label' => __('mail.subscription_created.detail_plan'),
            'value' => $planName,
            'bold' => true,
            'color' => null,
        ],
        $paidDisplay ? [
            'label' => __('mail.subscription_created.detail_amount'),
            'value' => $paidDisplay,
            'bold' => false,
            'color' => null,
        ] : null,
        $startsAt !== '' ? [
            'label' => __('mail.subscription_created.detail_start'),
            'value' => $startsAt,
            'bold' => false,
            'color' => null,
        ] : null,
        $endsAt !== '' ? [
            'label' => __('mail.subscription_created.detail_renewal'),
            'value' => $endsAt,
            'bold' => false,
            'color' => null,
        ] : null,
        [
            'label' => __('mail.subscription_created.detail_status'),
            'value' => $statusLabel,
            'bold' => true,
            'color' => '#0A5C32',
        ],
    ]);
@endphp
<!DOCTYPE html>
<html lang="en" xmlns="http://www.w3.org/1999/xhtml" xmlns:v="urn:schemas-microsoft-com:vml"
    xmlns:o="urn:schemas-microsoft-com:office:office">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <title>{{ __('mail.subscription_created.subject') }}</title>
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
        style="display:none !important;visibility:hidden;mso-hide:all;font-size:1px;color:#F0F0F0;line-height:1px;max-height:0;max-width:0;opacity:0;overflow:hidden;">{{ __('mail.subscription_created.preheader') }}</span>

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
                                            style="display:inline-block;padding:4px 12px;border-radius:20px;border:1.5px solid rgba(200,169,106,0.18);background-color:transparent;font-weight:700;font-size:9px;line-height:1;font-family:Arial,Helvetica,sans-serif;letter-spacing:0.4px;text-transform:uppercase;color:rgba(200,169,106,0.5);">{{ __('mail.subscription_created.badge') }}</span>
                                    </td>
                                </tr>
                            </table>
                        </td>
                    </tr>

                    {{-- Hero H1: tinted with globe watermark --}}
                    <tr>
                        <td bgcolor="#FBF7EE" background="{{ $globeWatermarkUrl }}"
                            style="padding:34px 30px 40px;background-color:#FBF7EE;background-image:url('{{ $globeWatermarkUrl }}');background-repeat:no-repeat;background-position:right -24px top -24px;background-size:210px 210px;border-bottom:1.5px solid #E8D5A8;">
                            <!--[if gte mso 9]>
                            <v:rect xmlns:v="urn:schemas-microsoft-com:vml" fill="true" stroke="false" style="position:absolute;width:600px;height:220px;">
                                <v:fill type="frame" src="{{ $globeWatermarkUrl }}" color="#FBF7EE" />
                            </v:rect>
                            <![endif]-->
                            <table role="presentation" width="100%" cellspacing="0" cellpadding="0" border="0">
                                <tr>
                                    <td valign="top">
                                        <table role="presentation" cellspacing="0" cellpadding="0" border="0"
                                            style="margin-bottom:14px;">
                                            <tr>
                                                <td
                                                    style="padding:4px 11px;border-radius:20px;border:1.5px solid #6ECFA0;background-color:#EAFAF2;">
                                                    <table role="presentation" cellspacing="0" cellpadding="0"
                                                        border="0">
                                                        <tr>
                                                            <td width="5" valign="middle"
                                                                style="line-height:0;font-size:0;">
                                                                <span
                                                                    style="display:block;width:5px;height:5px;border-radius:50%;background-color:#0E8A4A;">&nbsp;</span>
                                                            </td>
                                                            <td valign="middle"
                                                                style="padding-left:5px;font-weight:800;font-size:8.5px;line-height:1;font-family:Arial,Helvetica,sans-serif;letter-spacing:1.2px;text-transform:uppercase;color:#0A5C32;">
                                                                {{ __('mail.subscription_created.pill') }}</td>
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
                                                    {{ __('mail.subscription_created.eyebrow') }}</td>
                                            </tr>
                                        </table>
                                        <div
                                            style="font-weight:500;font-size:31px;line-height:1.17;font-family:Georgia,'Times New Roman',serif;color:#3B2800;letter-spacing:-0.2px;max-width:380px;">
                                            {!! __('mail.subscription_created.hero_headline') !!}
                                        </div>
                                        <div
                                            style="padding-top:12px;font-weight:400;font-size:13.5px;line-height:1.78;font-family:Arial,Helvetica,sans-serif;color:#666666;max-width:380px;">
                                            {{ __('mail.subscription_created.hero_subheadline') }}</div>
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
                                {{ __('mail.subscription_created.greeting', ['name' => $recipientName]) }}</div>
                            <p
                                style="margin:0;font-weight:400;font-size:13.5px;line-height:1.88;font-family:Arial,Helvetica,sans-serif;color:#464646;">
                                {!! __('mail.subscription_created.intro_body', ['platform' => $platformName]) !!}</p>

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
                                                        $circleBg = '#EAFAF2';
                                                        $circleBorder = '#6ECFA0';
                                                        $labelColor = '#0A5C32';
                                                        $descColor = '#0E8A4A';
                                                        if ($step['state'] === 'active') {
                                                            $circleBg = '#EAFAF2';
                                                            $circleBorder = '#6ECFA0';
                                                        }
                                                    @endphp
                                                    <td width="135" align="center" valign="top"
                                                        style="width:25%;padding:0 4px;">
                                                        <table role="presentation" width="100%" cellspacing="0"
                                                            cellpadding="0" border="0" align="center">
                                                            <tr>
                                                                <td align="center" style="padding-bottom:8px;">
                                                                    <table role="presentation" cellspacing="0"
                                                                        cellpadding="0" border="0" align="center">
                                                                        <tr>
                                                                            <td width="34" height="34"
                                                                                align="center" valign="middle"
                                                                                bgcolor="{{ $circleBg }}"
                                                                                style="width:34px;height:34px;background-color:{{ $circleBg }};border:2px solid {{ $circleBorder }};border-radius:50%;">
                                                                                <img src="{{ $step['icon'] }}"
                                                                                    width="14" height="14" alt=""
                                                                                    style="{{ $mailIconStyle }}">
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

                            {{-- Active status chip --}}
                            <table role="presentation" width="100%" cellspacing="0" cellpadding="0" border="0"
                                style="margin-top:16px;background-color:#EAFAF2;border:1.5px solid #6ECFA0;border-radius:8px;border-collapse:separate;">
                                <tr>
                                    <td style="padding:12px 15px;">
                                        <table role="presentation" cellspacing="0" cellpadding="0" border="0">
                                            <tr>
                                                <td width="8" valign="middle"
                                                    style="line-height:0;font-size:0;padding-right:10px;">
                                                    <span
                                                        style="display:block;width:7px;height:7px;border-radius:50%;background-color:#0E8A4A;">&nbsp;</span>
                                                </td>
                                                <td valign="middle"
                                                    style="font-weight:700;font-size:12.5px;line-height:1;font-family:Arial,Helvetica,sans-serif;color:#0A5C32;">
                                                    {{ __('mail.subscription_created.status_chip') }}</td>
                                            </tr>
                                        </table>
                                    </td>
                                </tr>
                            </table>
                        </td>
                    </tr>

                    {{-- Full access unlocked --}}
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
                                        {!! __('mail.subscription_created.access_section_title') !!}</td>
                                </tr>
                            </table>

                            <table role="presentation" width="100%" cellspacing="0" cellpadding="0" border="0"
                                style="border:1.5px solid #6ECFA0;border-radius:10px;border-collapse:separate;">
                                <tr>
                                    <td bgcolor="#EAFAF2"
                                        style="padding:14px 16px;background-color:#EAFAF2;border-bottom:1px solid #6ECFA0;">
                                        <table role="presentation" width="100%" cellspacing="0" cellpadding="0"
                                            border="0">
                                            <tr>
                                                <td width="32" valign="top" style="width:32px;">
                                                    <table role="presentation" cellspacing="0" cellpadding="0"
                                                        border="0">
                                                        <tr>
                                                            <td width="32" height="32" align="center"
                                                                valign="middle" bgcolor="#EAFAF2"
                                                                style="width:32px;height:32px;background-color:#EAFAF2;border:1.5px solid #6ECFA0;border-radius:8px;">
                                                                <img src="{{ $checkSuccessIconUrl }}" width="14"
                                                                    height="14" alt=""
                                                                    style="{{ $mailIconStyle }}">
                                                            </td>
                                                        </tr>
                                                    </table>
                                                </td>
                                                <td valign="top" style="padding-left:12px;">
                                                    <div
                                                        style="font-weight:500;font-size:15px;line-height:1.3;font-family:Georgia,'Times New Roman',serif;color:#0A5C32;margin-bottom:3px;">
                                                        {{ __('mail.subscription_created.access_title') }}</div>
                                                    <div
                                                        style="font-weight:500;font-size:11.5px;line-height:1.4;font-family:Arial,Helvetica,sans-serif;color:#8A8A8A;">
                                                        {{ __('mail.subscription_created.access_sub') }}</div>
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
                                                            style="display:block;width:7px;height:7px;border-radius:50%;background-color:#0E8A4A;">&nbsp;</span>
                                                    </td>
                                                    <td valign="top"
                                                        style="font-weight:500;font-size:12.5px;line-height:1.4;font-family:Arial,Helvetica,sans-serif;color:#0A5C32;">
                                                        {{ $row }}</td>
                                                </tr>
                                            </table>
                                        </td>
                                    </tr>
                                @endforeach
                            </table>
                        </td>
                    </tr>

                    {{-- Subscription details --}}
                    <tr>
                        <td bgcolor="#FFFFFF"
                            style="padding:28px 30px;background-color:#FFFFFF;border-bottom:1px solid #F0F0F0;">
                            <table role="presentation" width="100%" cellspacing="0" cellpadding="0" border="0"
                                style="margin-bottom:18px;">
                                <tr>
                                    <td width="3" bgcolor="#E8D5A8"
                                        style="width:3px;background-color:#E8D5A8;border-radius:2px;font-size:0;line-height:0;">
                                        &nbsp;</td>
                                    <td
                                        style="padding-left:9px;font-weight:500;font-size:17px;line-height:1;font-family:Georgia,'Times New Roman',serif;color:#3B2800;">
                                        {!! __('mail.subscription_created.details_section_title') !!}</td>
                                </tr>
                            </table>

                            <table role="presentation" width="100%" cellspacing="0" cellpadding="0" border="0"
                                style="border:1.5px solid #E6E6E6;border-radius:10px;border-collapse:separate;overflow:hidden;">
                                <tr>
                                    <td bgcolor="#F8F8F8"
                                        style="padding:11px 16px;background-color:#F8F8F8;border-bottom:1px solid #E6E6E6;">
                                        <table role="presentation" width="100%" cellspacing="0" cellpadding="0"
                                            border="0">
                                            <tr>
                                                <td
                                                    style="font-weight:900;font-size:9px;line-height:1;font-family:Arial,Helvetica,sans-serif;letter-spacing:1.2px;text-transform:uppercase;color:#8A8A8A;">
                                                    {{ __('mail.subscription_created.details_heading') }}</td>
                                                <td align="right">
                                                    <span
                                                        style="display:inline-block;padding:2px 10px;border-radius:20px;border:1.5px solid #6ECFA0;background-color:#EAFAF2;font-weight:800;font-size:9px;line-height:1;font-family:Arial,Helvetica,sans-serif;color:#0A5C32;">{{ __('mail.subscription_created.paid_badge') }}</span>
                                                </td>
                                            </tr>
                                        </table>
                                    </td>
                                </tr>
                                @foreach ($detailRows as $row)
                                    <tr>
                                        <td style="border-top:1px solid #F0F0F0;padding:0;">
                                            <table role="presentation" width="100%" cellspacing="0" cellpadding="0"
                                                border="0">
                                                <tr>
                                                    <td width="110" bgcolor="#F8F8F8"
                                                        style="width:110px;padding:11px 16px;background-color:#F8F8F8;border-right:1px solid #F0F0F0;font-weight:700;font-size:11px;line-height:1;font-family:Arial,Helvetica,sans-serif;color:#8A8A8A;">
                                                        {{ $row['label'] }}</td>
                                                    <td
                                                        style="padding:11px 16px;font-weight:{{ !empty($row['bold']) ? '800' : '500' }};font-size:12.5px;line-height:1.3;font-family:Arial,Helvetica,sans-serif;color:{{ $row['color'] ?? '#1C1C1C' }};">
                                                        {{ $row['value'] }}</td>
                                                </tr>
                                            </table>
                                        </td>
                                    </tr>
                                @endforeach
                            </table>
                        </td>
                    </tr>

                    {{-- Next steps + buyer journey --}}
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
                                        {!! __('mail.subscription_created.next_section_title') !!}</td>
                                </tr>
                            </table>

                            @foreach ($nextSteps as $index => $step)
                                <table role="presentation" width="100%" cellspacing="0" cellpadding="0" border="0"
                                    style="{{ $index < count($nextSteps) - 1 ? 'border-bottom:1px solid #F0F0F0;' : '' }}">
                                    <tr>
                                        <td width="40" valign="top" style="width:40px;padding:15px 0;">
                                            <table role="presentation" cellspacing="0" cellpadding="0" border="0">
                                                <tr>
                                                    <td width="26" height="26" align="center" valign="middle"
                                                        bgcolor="#FBF7EE"
                                                        style="width:26px;height:26px;background-color:#FBF7EE;border:1.5px solid #E8D5A8;border-radius:50%;font-weight:900;font-size:11px;line-height:1;font-family:Arial,Helvetica,sans-serif;color:#9A7A3A;">
                                                        {{ $step['n'] }}</td>
                                                </tr>
                                            </table>
                                        </td>
                                        <td valign="top" style="padding:15px 0;">
                                            <div
                                                style="font-weight:700;font-size:13.5px;line-height:1;font-family:Arial,Helvetica,sans-serif;color:#1C1C1C;margin-bottom:3px;">
                                                {{ $step['title'] }}</div>
                                            <div
                                                style="font-weight:400;font-size:12.5px;line-height:1.55;font-family:Arial,Helvetica,sans-serif;color:#8A8A8A;">
                                                {{ $step['body'] }}</div>
                                        </td>
                                    </tr>
                                </table>
                            @endforeach

                            {{-- Buyer journey explainer --}}
                            <table role="presentation" width="100%" cellspacing="0" cellpadding="0" border="0"
                                bgcolor="#3B2800"
                                style="margin-top:18px;background-color:#3B2800;border-radius:10px;border-collapse:separate;">
                                <tr>
                                    <td style="padding:22px 18px;">
                                        <div
                                            style="font-weight:500;font-size:16px;line-height:1.2;font-family:Georgia,'Times New Roman',serif;color:#FFFFFF;margin-bottom:14px;">
                                            {!! __('mail.subscription_created.journey_heading') !!}</div>
                                        @foreach ($buyerJourney as $row)
                                            <table role="presentation" width="100%" cellspacing="0" cellpadding="0"
                                                border="0" style="margin-bottom:11px;">
                                                <tr>
                                                    <td width="22" valign="top"
                                                        style="width:22px;font-weight:600;font-size:11.5px;line-height:1.7;font-family:Georgia,'Times New Roman',serif;color:#C8A96A;">
                                                        {{ $row['n'] }}</td>
                                                    <td valign="top"
                                                        style="font-weight:400;font-size:12.5px;line-height:1.65;font-family:Arial,Helvetica,sans-serif;color:rgba(255,255,255,0.55);">
                                                        {!! $row['body'] !!}</td>
                                                </tr>
                                            </table>
                                        @endforeach
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
                                            style="display:inline-block;padding:14px 30px;background-color:#9A7A3A;color:#FFFFFF;font-weight:900;font-size:12px;line-height:1;font-family:Arial,Helvetica,sans-serif;letter-spacing:0.6px;text-transform:uppercase;text-decoration:none;border-radius:8px;">{{ $ctaLabel ?? __('mail.subscription_created.cta') }}</a>
                                    </td>
                                </tr>
                                <tr>
                                    <td style="padding-top:10px;">
                                        <a href="{{ $productsUrl }}"
                                            style="font-weight:600;font-size:12.5px;line-height:1;font-family:Arial,Helvetica,sans-serif;color:#B4B4B4;text-decoration:none;">{{ __('mail.subscription_created.cta_secondary') }}</a>
                                    </td>
                                </tr>
                                <tr>
                                    <td style="padding-top:18px;">
                                        <table role="presentation" width="100%" cellspacing="0" cellpadding="0"
                                            border="0" style="border-top:1px solid #F0F0F0;">
                                            <tr>
                                                <td
                                                    style="padding-top:18px;font-weight:400;font-size:12px;line-height:1.75;font-family:Arial,Helvetica,sans-serif;color:#8A8A8A;">
                                                    {!! __('mail.subscription_created.cta_note', ['url' => e($billingUrl)]) !!}
                                                </td>
                                            </tr>
                                        </table>
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
                                        {{ __('mail.subscription_created.footer_tag') }}</td>
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
