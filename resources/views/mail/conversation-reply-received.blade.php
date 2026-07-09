@php
    $platformName = config('app.name', 'SourceNest');
    $logoUrl = public_url('images/mail/sourcenest-logo.png');
    $frontendUrl = rtrim((string) config('app.frontend_url', config('app.url')), '/');
    $mailIconStyle = 'display:block;border:0;outline:none;text-decoration:none;margin:0 auto;';
    $recipientName = trim($recipientName ?? $name ?? '') !== '' ? trim($recipientName ?? $name ?? '') : 'there';
    $variant = ($recipientRole ?? '') === 'manufacturer' ? 'manufacturer' : 'buyer';
    $tx = static fn (string $key, array $replace = []) => __("mail.conversation_message_received.{$variant}.{$key}", $replace);
    $ctaUrl = $ctaUrl ?? \App\Support\Mail\MailNotificationHelper::frontendUrl(
        $variant === 'manufacturer' ? 'dashboard/manufacturer/messages' : 'dashboard/buyer/messages'
    );
    $inboxUrl = $inboxUrl ?? $ctaUrl;
    $globeWatermarkUrl = public_url('images/mail/svg/globe-large.svg');
    $messageIconUrl = public_url('images/mail/svg/email.svg');
    $inquiryTags = $inquiryTags ?? [];
    $steps = [
        ['title' => $tx('step_1_title'), 'body' => $tx('step_1_body')],
        ['title' => $tx('step_2_title'), 'body' => $tx('step_2_body')],
        ['title' => $tx('step_3_title'), 'body' => $tx('step_3_body')],
    ];
    $pillDotColor = $variant === 'buyer' ? '#1258B8' : '#9A7A3A';
@endphp
<!DOCTYPE html>
<html lang="en" xmlns="http://www.w3.org/1999/xhtml" xmlns:v="urn:schemas-microsoft-com:vml" xmlns:o="urn:schemas-microsoft-com:office:office">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <title>{{ __('mail.conversation_message_received.subject', ['senderName' => $senderName ?? '']) }}</title>
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
<body style="margin:0;padding:0;background-color:#F0F0F0;font-family:Arial,Helvetica,sans-serif;-webkit-text-size-adjust:100%;-ms-text-size-adjust:100%;">
    <span style="display:none !important;visibility:hidden;mso-hide:all;font-size:1px;color:#F0F0F0;line-height:1px;max-height:0;max-width:0;opacity:0;overflow:hidden;">{{ __('mail.conversation_message_received.preheader') }}</span>

    <table role="presentation" width="100%" cellspacing="0" cellpadding="0" border="0" style="background-color:#F0F0F0;">
        <tr>
            <td align="center" style="padding:24px 12px;">
                <table role="presentation" width="600" cellspacing="0" cellpadding="0" border="0" style="max-width:600px;width:100%;background-color:#FFFFFF;border-radius:14px;overflow:hidden;">

                    {{-- Header --}}
                    <tr>
                        <td bgcolor="#FFFFFF" style="padding:20px 30px;background-color:#FFFFFF;border-bottom:1.5px solid #F0F0F0;">
                            <table role="presentation" width="100%" cellspacing="0" cellpadding="0" border="0">
                                <tr>
                                    <td align="left" valign="middle">
                                        @if (!empty($logoUrl))
                                            <img src="{{ $logoUrl }}" alt="{{ $platformName }}" width="140" style="display:block;height:auto;max-height:68px;width:auto;border:0;outline:none;text-decoration:none;">
                                        @else
                                            <div style="font-weight:900;font-size:21px;line-height:1;font-family:Arial,Helvetica,sans-serif;color:#3B2800;letter-spacing:-0.6px;">sourcenest</div>
                                        @endif
                                    </td>
                                    <td align="right" valign="middle">
                                        <span style="display:inline-block;padding:4px 12px;border-radius:20px;border:1.5px solid #E6E6E6;background-color:#F8F8F8;font-weight:700;font-size:9px;line-height:1;font-family:Arial,Helvetica,sans-serif;letter-spacing:0.4px;text-transform:uppercase;color:#8A8A8A;">{{ $tx('badge') }}</span>
                                    </td>
                                </tr>
                            </table>
                        </td>
                    </tr>

                    {{-- Hero (h1 style with globe watermark) --}}
                    <tr>
                        <td bgcolor="#FBF7EE" style="padding:34px 30px 40px;background-color:#FBF7EE;border-bottom:1.5px solid #E8D5A8;">
                            <table role="presentation" width="100%" cellspacing="0" cellpadding="0" border="0">
                                <tr>
                                    <td valign="top" style="padding-right:16px;">
                                        <table role="presentation" cellspacing="0" cellpadding="0" border="0" style="margin-bottom:14px;">
                                            <tr>
                                                <td style="padding-right:8px;line-height:0;font-size:0;" valign="middle">
                                                    <span style="display:inline-block;width:7px;height:7px;border-radius:50%;background-color:{{ $pillDotColor }};">&nbsp;</span>
                                                </td>
                                                <td valign="middle" style="font-weight:800;font-size:8.5px;line-height:1;font-family:Arial,Helvetica,sans-serif;letter-spacing:1.6px;text-transform:uppercase;color:{{ $variant === 'buyer' ? '#1258B8' : '#9A7A3A' }};">{{ $tx('pill') }}</td>
                                            </tr>
                                        </table>
                                        <div style="font-weight:500;font-size:28px;line-height:1.12;font-family:Georgia,'Times New Roman',serif;color:#3B2800;letter-spacing:-0.3px;">
                                            {!! $tx('hero_headline') !!}
                                        </div>
                                        <div style="padding-top:8px;font-weight:400;font-size:13px;line-height:1.78;font-family:Arial,Helvetica,sans-serif;color:#666666;max-width:340px;">{{ $tx('hero_subheadline') }}</div>
                                    </td>
                                    <td width="120" align="right" valign="top" style="line-height:0;font-size:0;">
                                        @if (!empty($globeWatermarkUrl))
                                            <img src="{{ $globeWatermarkUrl }}" width="110" height="110" alt="" style="display:block;border:0;outline:none;opacity:0.06;">
                                        @endif
                                    </td>
                                </tr>
                            </table>
                        </td>
                    </tr>

                    {{-- Intro + message card --}}
                    <tr>
                        <td bgcolor="#FFFFFF" style="padding:28px 30px;background-color:#FFFFFF;border-bottom:1px solid #F0F0F0;">
                            <div style="font-weight:500;font-size:17px;line-height:1;font-family:Georgia,'Times New Roman',serif;color:#3B2800;margin-bottom:13px;">{{ $tx('greeting', ['name' => $recipientName]) }}</div>
                            <p style="margin:0;font-weight:400;font-size:13.5px;line-height:1.88;font-family:Arial,Helvetica,sans-serif;color:#464646;">{{ $tx('intro', ['sender' => $senderName ?? '']) }}</p>

                            <table role="presentation" width="100%" cellspacing="0" cellpadding="0" border="0" style="margin-top:20px;border:1.5px solid #E6E6E6;border-radius:12px;overflow:hidden;border-collapse:separate;">
                                <tr>
                                    <td bgcolor="#F8F8F8" style="padding:16px 18px;background-color:#F8F8F8;border-bottom:1px solid #E6E6E6;">
                                        <table role="presentation" width="100%" cellspacing="0" cellpadding="0" border="0">
                                            <tr>
                                                <td width="44" valign="top">
                                                    <div style="width:40px;height:40px;border-radius:10px;background-color:#3B2800;color:#FFFFFF;font-weight:800;font-size:13px;line-height:40px;font-family:Arial,Helvetica,sans-serif;text-align:center;">{{ $senderInitials ?? 'SN' }}</div>
                                                </td>
                                                <td valign="top" style="padding-left:12px;">
                                                    <div style="font-weight:800;font-size:13px;line-height:1.3;font-family:Arial,Helvetica,sans-serif;color:#1C1C1C;">{{ $senderDisplayName ?? $senderName ?? '' }}</div>
                                                    @if (!empty($senderMeta))
                                                        <div style="font-weight:600;font-size:11px;line-height:1.4;font-family:Arial,Helvetica,sans-serif;color:#8A8A8A;margin-top:3px;">{{ $senderMeta }}</div>
                                                    @endif
                                                </td>
                                                @if (!empty($messageTimestamp))
                                                    <td align="right" valign="top" style="font-weight:600;font-size:10px;line-height:1.3;font-family:Arial,Helvetica,sans-serif;color:#B4B4B4;white-space:nowrap;">{{ $messageTimestamp }}</td>
                                                @endif
                                            </tr>
                                        </table>
                                    </td>
                                </tr>
                                @if (!empty($messagePreview))
                                    <tr>
                                        <td style="padding:16px 18px;">
                                            <table role="presentation" cellspacing="0" cellpadding="0" border="0" style="margin-bottom:10px;">
                                                <tr>
                                                    <td width="22" valign="top" style="padding-right:8px;">
                                                        @if (!empty($messageIconUrl))
                                                            <img src="{{ $messageIconUrl }}" width="16" height="16" alt="" style="{{ $mailIconStyle }}">
                                                        @endif
                                                    </td>
                                                    <td valign="top" style="font-weight:600;font-size:10px;line-height:1;font-family:Arial,Helvetica,sans-serif;letter-spacing:1px;text-transform:uppercase;color:#8A8A8A;">{{ $tx('message_label') }}</td>
                                                </tr>
                                            </table>
                                            <div style="font-weight:500;font-size:13px;line-height:1.65;font-family:Arial,Helvetica,sans-serif;color:#464646;">{!! $messagePreview !!}</div>
                                        </td>
                                    </tr>
                                @endif
                                @if (!empty($inquiryTags))
                                    <tr>
                                        <td style="padding:0 18px 16px 18px;">
                                            @foreach ($inquiryTags as $tag)
                                                <span style="display:inline-block;font-weight:600;font-size:10px;line-height:1;font-family:Arial,Helvetica,sans-serif;color:#666666;background-color:#F8F8F8;border:1px solid #E6E6E6;border-radius:6px;padding:4px 8px;margin:0 6px 6px 0;">{{ $tag['label'] ?? '' }} <strong style="color:#1C1C1C;">{{ $tag['value'] ?? '' }}</strong></span>
                                            @endforeach
                                        </td>
                                    </tr>
                                @endif
                            </table>

                            <table role="presentation" width="100%" cellspacing="0" cellpadding="0" border="0" style="margin-top:16px;background-color:#EAFAF2;border:1.5px solid #6ECFA0;border-radius:10px;border-collapse:separate;">
                                <tr>
                                    <td style="padding:15px 16px;">
                                        <div style="font-weight:900;font-size:8.5px;line-height:1;font-family:Arial,Helvetica,sans-serif;letter-spacing:1.6px;text-transform:uppercase;color:#0A5C32;margin-bottom:5px;">{{ $tx('insight_label') }}</div>
                                        <div style="font-weight:400;font-size:13px;line-height:1.65;font-family:Arial,Helvetica,sans-serif;color:#464646;">{!! $tx('insight_body') !!}</div>
                                    </td>
                                </tr>
                            </table>
                        </td>
                    </tr>

                    {{-- Steps --}}
                    <tr>
                        <td bgcolor="#F8F8F8" style="padding:28px 30px;background-color:#F8F8F8;border-bottom:1px solid #F0F0F0;">
                            <table role="presentation" cellspacing="0" cellpadding="0" border="0" style="margin-bottom:18px;">
                                <tr>
                                    <td width="3" bgcolor="#E8D5A8" style="background-color:#E8D5A8;border-radius:2px;">&nbsp;</td>
                                    <td style="padding-left:9px;font-weight:500;font-size:17px;line-height:1;font-family:Georgia,'Times New Roman',serif;color:#3B2800;">{!! $tx('steps_title') !!}</td>
                                </tr>
                            </table>

                            @foreach ($steps as $index => $step)
                                <table role="presentation" width="100%" cellspacing="0" cellpadding="0" border="0" @if (! $loop->last) style="margin-bottom:0;border-bottom:1px solid #F0F0F0;" @endif>
                                    <tr>
                                        <td width="40" valign="top" style="padding:15px 0;">
                                            <table role="presentation" cellspacing="0" cellpadding="0" border="0">
                                                <tr>
                                                    <td width="26" height="26" align="center" valign="middle" bgcolor="#FBF7EE" style="width:26px;height:26px;background-color:#FBF7EE;border:1.5px solid #E8D5A8;border-radius:50%;font-weight:900;font-size:11px;line-height:1;font-family:Arial,Helvetica,sans-serif;color:#9A7A3A;">{{ $index + 1 }}</td>
                                                </tr>
                                                @if (! $loop->last)
                                                    <tr>
                                                        <td align="center" style="padding-top:4px;line-height:0;font-size:0;">
                                                            <span style="display:block;width:1px;height:24px;background-color:#E6E6E6;">&nbsp;</span>
                                                        </td>
                                                    </tr>
                                                @endif
                                            </table>
                                        </td>
                                        <td valign="top" style="padding:15px 0;">
                                            <div style="font-weight:700;font-size:13.5px;line-height:1.2;font-family:Arial,Helvetica,sans-serif;color:#1C1C1C;margin-bottom:3px;">{{ $step['title'] }}</div>
                                            <div style="font-weight:400;font-size:12.5px;line-height:1.55;font-family:Arial,Helvetica,sans-serif;color:#8A8A8A;">{{ $step['body'] }}</div>
                                        </td>
                                    </tr>
                                </table>
                            @endforeach
                        </td>
                    </tr>

                    {{-- CTA --}}
                    <tr>
                        <td bgcolor="#FFFFFF" style="padding:26px 30px 30px;background-color:#FFFFFF;border-top:1px solid #F0F0F0;">
                            <table role="presentation" cellspacing="0" cellpadding="0" border="0">
                                <tr>
                                    <td>
                                        <a href="{{ $ctaUrl }}" style="display:inline-block;padding:14px 30px;background-color:#3B2800;color:#FFFFFF;font-weight:900;font-size:12px;line-height:1;font-family:Arial,Helvetica,sans-serif;letter-spacing:0.6px;text-transform:uppercase;text-decoration:none;border-radius:8px;">{{ $ctaLabel ?? $tx('cta') }}</a>
                                    </td>
                                </tr>
                                <tr>
                                    <td style="padding-top:10px;">
                                        <a href="{{ $inboxUrl }}" style="font-weight:600;font-size:12.5px;line-height:1;font-family:Arial,Helvetica,sans-serif;color:#B4B4B4;text-decoration:none;">{{ $tx('cta_secondary') }}</a>
                                    </td>
                                </tr>
                            </table>
                        </td>
                    </tr>

                    {{-- Footer --}}
                    <tr>
                        <td bgcolor="#F8F8F8" style="padding:18px 30px;background-color:#F8F8F8;border-top:1px solid #E6E6E6;">
                            <table role="presentation" width="100%" cellspacing="0" cellpadding="0" border="0">
                                <tr>
                                    <td style="font-weight:900;font-size:13px;line-height:1;font-family:Arial,Helvetica,sans-serif;color:#3B2800;letter-spacing:-0.4px;">sourcenest</td>
                                    <td align="right" style="font-weight:700;font-size:8px;line-height:1;font-family:Arial,Helvetica,sans-serif;letter-spacing:0.8px;text-transform:uppercase;color:#B4B4B4;">{{ __('mail.conversation_message_received.footer_tag') }}</td>
                                </tr>
                            </table>
                            <table role="presentation" width="100%" cellspacing="0" cellpadding="0" border="0" style="margin:10px 0;">
                                <tr>
                                    <td height="1" bgcolor="#E6E6E6" style="height:1px;background-color:#E6E6E6;font-size:0;line-height:0;">&nbsp;</td>
                                </tr>
                            </table>
                            <span style="font-weight:600;font-size:10.5px;line-height:1;font-family:Arial,Helvetica,sans-serif;color:#B4B4B4;">
                                <a href="{{ $frontendUrl }}/unsubscribe" style="color:#B4B4B4;text-decoration:none;">Unsubscribe</a>
                                <span style="margin:0 5px;font-size:9px;color:#E6E6E6;">·</span>
                                <a href="{{ $frontendUrl }}/privacy" style="color:#B4B4B4;text-decoration:none;">Privacy</a>
                                <span style="margin:0 5px;font-size:9px;color:#E6E6E6;">·</span>
                                <a href="{{ $frontendUrl }}/terms" style="color:#B4B4B4;text-decoration:none;">Terms</a>
                            </span>
                        </td>
                    </tr>

                </table>
            </td>
        </tr>
    </table>
</body>
</html>
