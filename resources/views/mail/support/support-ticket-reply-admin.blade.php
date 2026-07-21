@php
    $platformName = config('app.name', 'SourceNest');
    $logoUrl = public_url('images/mail/sourcenest-logo.png');
    $heroIconUrl = public_url('images/mail/svg/support-reply-admin-hero.svg');
    $frontendUrl = rtrim((string) config('app.frontend_url', config('app.url')), '/');
    $supportEmail = config('mail.from.address', 'no-reply@sourcenest.com');
    $mailIconStyle = 'display:block;border:0;outline:none;text-decoration:none;margin:0 auto;';
    $ticketNumber = $ticketNumber ?? $referenceId ?? '';
    $ticketSubject = $ticketSubject ?? $subject ?? '';
    $senderName = $senderName ?? '';
    $senderType = $senderType ?? 'User';
    $senderInitials = $senderInitials ?? \App\Support\Mail\MailNotificationHelper::initials($senderName !== '' ? $senderName : 'U');
    $replyPreview = trim(strip_tags((string) ($messageBodyPlain ?? $messageBody ?? '')));
    $repliedAt = $repliedAt ?? '';
    $ctaUrl = $ctaUrl ?? \App\Support\Mail\MailNotificationHelper::frontendUrl('admin/customer-supports/tickets');
    $ctaLabel = $ctaLabel ?? 'Open Ticket';
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
    <title>User replied on support ticket — {{ $ticketNumber }}</title>
    <!--[if mso]>
    <noscript>
        <xml>
            <o:OfficeDocumentSettings>
                <o:AllowPNG/>
                <o:PixelsPerInch>96</o:PixelsPerInch>
            </o:OfficeDocumentSettings>
        </xml>
    </noscript>
    <style type="text/css">
        body, table, td, a, span, div { font-family: Arial, Helvetica, sans-serif !important; }
    </style>
    <![endif]-->
    <style type="text/css">
        :root {
            color-scheme: light only;
            supported-color-schemes: light;
        }
        html, body { margin: 0 !important; padding: 0 !important; width: 100% !important; -webkit-text-size-adjust: 100%; -ms-text-size-adjust: 100%; }
        img { border: 0; outline: none; text-decoration: none; -ms-interpolation-mode: bicubic; display: block; max-width: 100%; height: auto; }
        table { border-collapse: collapse; mso-table-lspace: 0pt; mso-table-rspace: 0pt; }
        /* Force light palette even when the device/OS is in dark mode */
        @media (prefers-color-scheme: dark) {
            .email-bg-main { background-color: #F4F0EA !important; }
            .email-card, .email-pad-white { background-color: #FFFFFF !important; }
            .email-hero-bg { background-color: #FBF7EE !important; }
            .email-sec-gs { background-color: #F8F8F8 !important; }
            .email-brand-text { color: #3B2800 !important; }
            .email-body-text { color: #464646 !important; }
            .email-muted-text { color: #8A8A8A !important; }
            .email-cta-btn { background-color: #3B2800 !important; color: #FFFFFF !important; }
        }
        @media only screen and (max-width: 640px) {
            .email-outer { padding: 12px 8px !important; }
            .email-card { border-radius: 10px !important; }
            .email-pad { padding-left: 18px !important; padding-right: 18px !important; }
            .email-hero-title { font-size: 20px !important; line-height: 1.25 !important; }
            .email-section-title { font-size: 15px !important; }
            .email-body-text { font-size: 13px !important; }
            .email-stack { display: block !important; width: 100% !important; }
            .email-stack-icon { padding-right: 0 !important; padding-bottom: 14px !important; }
            .email-footer-tag { text-align: left !important; display: block !important; padding-top: 6px !important; }
            .email-cta { display: block !important; width: 100% !important; text-align: center !important; box-sizing: border-box !important; }
            .email-evl-lbl { display: block !important; width: 100% !important; border-right: none !important; border-bottom: 1px solid #F0F0F0 !important; }
            .email-evl-val { display: block !important; width: 100% !important; }
            .email-inq-ts { display: block !important; text-align: left !important; padding-top: 8px !important; }
        }
    </style>
</head>

<body class="email-bg-main" style="margin:0;padding:0;background-color:#F4F0EA;font-family:Arial,Helvetica,sans-serif;-webkit-text-size-adjust:100%;-ms-text-size-adjust:100%;">
    <div style="display:none;font-size:1px;line-height:1px;max-height:0;max-width:0;opacity:0;overflow:hidden;mso-hide:all;">
        User replied on support ticket #{{ $ticketNumber }}&nbsp;&zwnj;&nbsp;&zwnj;&nbsp;&zwnj;&nbsp;&zwnj;&nbsp;&zwnj;&nbsp;&zwnj;&nbsp;&zwnj;&nbsp;&zwnj;&nbsp;&zwnj;&nbsp;&zwnj;
    </div>

    <table role="presentation" width="100%" cellspacing="0" cellpadding="0" border="0" class="email-bg-main" style="background-color:#F4F0EA;">
        <tr>
            <td align="center" class="email-outer" style="padding:24px 12px;">
                <table role="presentation" width="700" cellspacing="0" cellpadding="0" border="0" class="email-card"
                    style="max-width:700px;width:100%;background-color:#FFFFFF;border-radius:14px;overflow:hidden;">

                    {{-- Header --}}
                    <tr>
                        <td class="email-pad email-pad-white" bgcolor="#FFFFFF"
                            style="padding:20px 30px;background-color:#FFFFFF;border-bottom:1.5px solid #F0F0F0;">
                            @if (! empty($logoUrl))
                                <a href="{{ $frontendUrl }}?source=email" target="_blank" style="text-decoration:none;display:inline-block;">
                                    <img src="{{ $logoUrl }}" alt="{{ $platformName }}" width="160" height="48"
                                        style="height:48px;width:auto;max-width:180px;display:block;border:0;outline:none;">
                                </a>
                            @else
                                <span class="email-brand-text" style="font-weight:900;font-size:21px;line-height:1;font-family:Arial,Helvetica,sans-serif;color:#3B2800;">sourcenest</span>
                            @endif
                        </td>
                    </tr>

                    {{-- Hero H5 — user replied --}}
                    <tr>
                        <td class="email-pad email-hero-bg" bgcolor="#FBF7EE"
                            style="padding:26px 30px;background-color:#FBF7EE;background-image:linear-gradient(135deg,#FBF7EE 0%,#FFFFFF 55%);border-bottom:1.5px solid #E8D5A8;">
                            <table role="presentation" width="100%" cellspacing="0" cellpadding="0" border="0">
                                <tr>
                                    <td class="email-stack email-stack-icon" width="76" valign="middle" style="width:76px;padding-right:18px;">
                                        <table role="presentation" cellspacing="0" cellpadding="0" border="0" style="border-collapse:separate;">
                                            <tr>
                                                <td width="58" height="58" align="center" valign="middle" bgcolor="#2E2E2E"
                                                    style="width:58px;height:58px;background-color:#2E2E2E; solid #2E2E2E;border-radius:14px;">
                                                    @if (! empty($heroIconUrl))
                                                        <img src="{{ $heroIconUrl }}" width="26" height="26" alt="" style="{{ $mailIconStyle }}">
                                                    @endif
                                                </td>
                                            </tr>
                                        </table>
                                    </td>
                                    <td class="email-stack" valign="middle">
                                        <table role="presentation" cellspacing="0" cellpadding="0" border="0" style="margin-bottom:10px;">
                                            <tr>
                                                <td>
                                                    <span style="display:inline-block;padding:4px 11px;border-radius:20px;border:1.5px solid #D6D6D6;background-color:#F8F8F8;">
                                                        <span style="display:inline-block;width:5px;height:5px;border-radius:50%;background-color:#8A8A8A;vertical-align:middle;margin-right:5px;">&nbsp;</span>
                                                        <span style="font-weight:800;font-size:8.5px;line-height:1;font-family:Arial,Helvetica,sans-serif;letter-spacing:1.2px;text-transform:uppercase;color:#666666;vertical-align:middle;">User Replied</span>
                                                    </span>
                                                </td>
                                            </tr>
                                        </table>
                                        <div class="email-hero-title email-brand-text"
                                            style="font-weight:500;font-size:22px;line-height:1.17;font-family:Georgia,'Times New Roman',serif;color:#3B2800;letter-spacing:-0.2px;">
                                            The user has <em style="font-style:italic;color:#9A7A3A;">replied</em> to ticket #{{ $ticketNumber }}.
                                        </div>
                                        <div class="email-muted-text" style="padding-top:6px;font-weight:400;font-size:13px;line-height:1.78;font-family:Arial,Helvetica,sans-serif;color:#666666;">
                                            A user has posted a new reply on an open support ticket. Please review and respond.
                                        </div>
                                    </td>
                                </tr>
                            </table>
                        </td>
                    </tr>

                    {{-- Ticket details + From --}}
                    <tr>
                        <td class="email-pad email-pad-white" bgcolor="#FFFFFF" style="padding:28px 30px;background-color:#FFFFFF;border-bottom:1px solid #F0F0F0;">
                            <table role="presentation" cellspacing="0" cellpadding="0" border="0" style="margin-bottom:18px;border-collapse:separate;">
                                <tr>
                                    <td width="3" bgcolor="#E8D5A8" style="width:3px;height:18px;background-color:#E8D5A8;border-radius:2px;font-size:0;line-height:0;">&nbsp;</td>
                                    <td class="email-section-title email-brand-text" style="padding-left:9px;font-weight:500;font-size:17px;line-height:1;font-family:Georgia,'Times New Roman',serif;color:#3B2800;">Ticket details</td>
                                </tr>
                            </table>

                            <table role="presentation" width="100%" cellspacing="0" cellpadding="0" border="0"
                                style="background-color:#FFFFFF;border:1.5px solid #E6E6E6;border-radius:10px;border-collapse:separate;overflow:hidden;">
                                <tr>
                                    <td bgcolor="#F8F8F8" style="padding:11px 16px;background-color:#F8F8F8;border-bottom:1px solid #E6E6E6;">
                                        <table role="presentation" width="100%" cellspacing="0" cellpadding="0" border="0">
                                            <tr>
                                                <td style="font-weight:900;font-size:9px;line-height:1;font-family:Arial,Helvetica,sans-serif;letter-spacing:1.2px;text-transform:uppercase;color:#8A8A8A;">Ticket Details</td>
                                                <td align="right">
                                                    <span style="display:inline-block;padding:2px 10px;border-radius:20px;border:1.5px solid #F0C040;background-color:#FFF8E4;font-weight:800;font-size:9px;line-height:1;font-family:Arial,Helvetica,sans-serif;color:#7A4D00;">Awaiting Reply</span>
                                                </td>
                                            </tr>
                                        </table>
                                    </td>
                                </tr>
                                @foreach ([
                                    ['Ticket #', $ticketNumber],
                                    ['Subject', $ticketSubject],
                                ] as [$label, $value])
                                    @if (trim((string) $value) !== '')
                                        <tr>
                                            <td style="padding:0;">
                                                <table role="presentation" width="100%" cellspacing="0" cellpadding="0" border="0">
                                                    <tr>
                                                        <td class="email-evl-lbl" width="110" bgcolor="#F8F8F8" valign="middle"
                                                            style="width:110px;padding:11px 16px;background-color:#F8F8F8;border-top:1px solid #F0F0F0;border-right:1px solid #F0F0F0;font-weight:700;font-size:11px;line-height:1;font-family:Arial,Helvetica,sans-serif;color:#8A8A8A;">{{ $label }}</td>
                                                        <td class="email-evl-val" valign="middle" style="padding:11px 16px;border-top:1px solid #F0F0F0;font-weight:500;font-size:12.5px;line-height:1.4;font-family:Arial,Helvetica,sans-serif;color:#1C1C1C;">{{ $value }}</td>
                                                    </tr>
                                                </table>
                                            </td>
                                        </tr>
                                    @endif
                                @endforeach
                            </table>

                            <table role="presentation" width="100%" cellspacing="0" cellpadding="0" border="0"
                                style="margin-top:12px;background-color:#FFFFFF;border:1.5px solid #E6E6E6;border-radius:10px;border-collapse:separate;overflow:hidden;">
                                <tr>
                                    <td bgcolor="#F8F8F8" style="padding:11px 16px;background-color:#F8F8F8;border-bottom:1px solid #E6E6E6;">
                                        <span style="font-weight:900;font-size:9px;line-height:1;font-family:Arial,Helvetica,sans-serif;letter-spacing:1.2px;text-transform:uppercase;color:#8A8A8A;">From</span>
                                    </td>
                                </tr>
                                @foreach ([
                                    ['Name', $senderName],
                                    ['Account type', $senderType],
                                ] as [$label, $value])
                                    @if (trim((string) $value) !== '')
                                        <tr>
                                            <td style="padding:0;">
                                                <table role="presentation" width="100%" cellspacing="0" cellpadding="0" border="0">
                                                    <tr>
                                                        <td class="email-evl-lbl" width="110" bgcolor="#F8F8F8" valign="middle"
                                                            style="width:110px;padding:11px 16px;background-color:#F8F8F8;border-top:1px solid #F0F0F0;border-right:1px solid #F0F0F0;font-weight:700;font-size:11px;line-height:1;font-family:Arial,Helvetica,sans-serif;color:#8A8A8A;">{{ $label }}</td>
                                                        <td class="email-evl-val" valign="middle" style="padding:11px 16px;border-top:1px solid #F0F0F0;font-weight:500;font-size:12.5px;line-height:1.4;font-family:Arial,Helvetica,sans-serif;color:#1C1C1C;">{{ $value }}</td>
                                                    </tr>
                                                </table>
                                            </td>
                                        </tr>
                                    @endif
                                @endforeach
                            </table>
                        </td>
                    </tr>

                    {{-- New reply preview --}}
                    @if ($replyPreview !== '')
                        <tr>
                            <td class="email-pad email-sec-gs" bgcolor="#F8F8F8" style="padding:28px 30px;background-color:#F8F8F8;border-bottom:1px solid #F0F0F0;">
                                <table role="presentation" cellspacing="0" cellpadding="0" border="0" style="margin-bottom:18px;border-collapse:separate;">
                                    <tr>
                                        <td width="3" bgcolor="#E8D5A8" style="width:3px;height:18px;background-color:#E8D5A8;border-radius:2px;font-size:0;line-height:0;">&nbsp;</td>
                                        <td class="email-section-title email-brand-text" style="padding-left:9px;font-weight:500;font-size:17px;line-height:1;font-family:Georgia,'Times New Roman',serif;color:#3B2800;">New reply</td>
                                    </tr>
                                </table>

                                <table role="presentation" width="100%" cellspacing="0" cellpadding="0" border="0"
                                    style="background-color:#FFFFFF;border:1.5px solid #E6E6E6;border-radius:10px;border-collapse:separate;overflow:hidden;">
                                    <tr>
                                        <td bgcolor="#F8F8F8" style="padding:14px 17px;background-color:#F8F8F8;border-bottom:1px solid #E6E6E6;">
                                            <table role="presentation" width="100%" cellspacing="0" cellpadding="0" border="0">
                                                <tr>
                                                    <td width="38" valign="middle" style="width:38px;padding-right:12px;">
                                                        <div style="width:38px;height:38px;background-color:#3B2800;border-radius:8px;text-align:center;line-height:38px;font-weight:600;font-size:14px;font-family:Georgia,'Times New Roman',serif;color:#C8A96A;">{{ $senderInitials }}</div>
                                                    </td>
                                                    <td valign="middle">
                                                        <div class="email-brand-text" style="font-weight:800;font-size:13.5px;line-height:1;font-family:Arial,Helvetica,sans-serif;color:#3B2800;">{{ $senderName !== '' ? $senderName : 'User' }}</div>
                                                        <div class="email-muted-text" style="font-weight:500;font-size:11.5px;line-height:1;font-family:Arial,Helvetica,sans-serif;color:#8A8A8A;margin-top:4px;">{{ $senderType }} · sourceNest</div>
                                                    </td>
                                                    @if ($repliedAt !== '')
                                                        <td class="email-inq-ts" align="right" valign="middle" style="font-weight:500;font-size:11px;line-height:1;font-family:Arial,Helvetica,sans-serif;color:#B4B4B4;">{{ $repliedAt }}</td>
                                                    @endif
                                                </tr>
                                            </table>
                                        </td>
                                    </tr>
                                    <tr>
                                        <td style="padding:17px 19px;">
                                            <p class="email-body-text" style="margin:0;font-weight:400;font-style:italic;font-size:13.5px;line-height:1.8;font-family:Arial,Helvetica,sans-serif;color:#464646;">{{ $replyPreview }}</p>
                                        </td>
                                    </tr>
                                </table>
                            </td>
                        </tr>
                    @endif

                    {{-- CTA --}}
                    <tr>
                        <td class="email-pad email-pad-white" bgcolor="#FFFFFF" style="padding:26px 30px 30px;background-color:#FFFFFF;">
                            <table role="presentation" cellspacing="0" cellpadding="0" border="0" style="border-collapse:separate;">
                                <tr>
                                    <td bgcolor="#3B2800" style="border-radius:8px;background-color:#3B2800;">
                                        <a class="email-cta email-cta-btn" href="{{ $ctaUrl }}"
                                            style="display:inline-block;padding:14px 30px;background-color:#3B2800;color:#FFFFFF;font-weight:900;font-size:12px;line-height:1;font-family:Arial,Helvetica,sans-serif;letter-spacing:0.6px;text-transform:uppercase;text-decoration:none;border-radius:8px;">{{ $ctaLabel }}</a>
                                    </td>
                                </tr>
                            </table>
                            <p class="email-muted-text" style="margin:18px 0 0 0;padding-top:18px;border-top:1px solid #F0F0F0;font-weight:400;font-size:12px;line-height:1.75;font-family:Arial,Helvetica,sans-serif;color:#8A8A8A;">
                                Log in to the admin panel to read the full reply and respond.
                            </p>
                        </td>
                    </tr>

                    {{-- Footer --}}
                    <tr>
                        <td class="email-pad email-sec-gs" bgcolor="#F8F8F8" style="padding:18px 30px;background-color:#F8F8F8;border-top:1px solid #E6E6E6;">
                            <table role="presentation" width="100%" cellspacing="0" cellpadding="0" border="0">
                                <tr>
                                    <td class="email-brand-text" style="font-weight:900;font-size:13px;line-height:1;font-family:Arial,Helvetica,sans-serif;color:#3B2800;letter-spacing:-0.4px;">sourcenest</td>
                                    <td class="email-footer-tag" align="right" style="font-weight:700;font-size:8px;line-height:1;font-family:Arial,Helvetica,sans-serif;letter-spacing:0.8px;text-transform:uppercase;color:#B4B4B4;">Global Sourcing Platform</td>
                                </tr>
                            </table>
                            <table role="presentation" width="100%" cellspacing="0" cellpadding="0" border="0" style="margin:10px 0;">
                                <tr>
                                    <td height="1" bgcolor="#E6E6E6" style="height:1px;background-color:#E6E6E6;font-size:0;line-height:0;">&nbsp;</td>
                                </tr>
                            </table>
                            <span class="email-muted-text" style="font-weight:600;font-size:10.5px;line-height:1;font-family:Arial,Helvetica,sans-serif;color:#8A8A8A;">Automated notification — please do not reply to this email.</span>
                            <span style="font-size:9px;color:#D6D6D6;margin:0 5px;">·</span>
                            <span class="email-muted-text" style="font-weight:600;font-size:10.5px;line-height:1;font-family:Arial,Helvetica,sans-serif;color:#8A8A8A;">{{ $supportEmail }}</span>
                        </td>
                    </tr>

                </table>
            </td>
        </tr>
    </table>
</body>

</html>
