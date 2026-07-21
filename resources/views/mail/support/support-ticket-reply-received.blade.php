@php
    $platformName = config('app.name', 'SourceNest');
    $logoUrl = public_url('images/mail/sourcenest-logo.png');
    $heroIconUrl = public_url('images/mail/svg/support-ticket-hero.svg');
    $frontendUrl = rtrim((string) config('app.frontend_url', config('app.url')), '/');
    $supportEmail = config('mail.from.address', 'no-reply@sourcenest.com');
    $ticketNumber = $ticketNumber ?? $referenceId ?? '';
    $ticketSubject = $ticketSubject ?? $subject ?? '';
    $replyPreview = trim(strip_tags((string) ($messageBodyPlain ?? '')));
    $ctaUrl = $ctaUrl ?? \App\Support\Mail\MailNotificationHelper::frontendUrl('dashboard/buyer/support-tickets');
    $ctaLabel = $ctaLabel ?? 'View Ticket';
@endphp
<!DOCTYPE html>
<html lang="en" xmlns="http://www.w3.org/1999/xhtml">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="x-apple-disable-message-reformatting">
    <meta name="format-detection" content="telephone=no,address=no,email=no,date=no,url=no">
    <meta name="color-scheme" content="light only">
    <title>We received your reply — {{ $ticketNumber }}</title>
    <style type="text/css">
        :root { color-scheme: light only; supported-color-schemes: light; }
        html, body { margin:0 !important; padding:0 !important; width:100% !important; -webkit-text-size-adjust:100%; }
        img { border:0; outline:none; text-decoration:none; display:block; max-width:100%; height:auto; }
        table { border-collapse:collapse; mso-table-lspace:0; mso-table-rspace:0; }
        @media only screen and (max-width:640px) {
            .email-outer { padding:12px 8px !important; }
            .email-pad { padding-left:18px !important; padding-right:18px !important; }
            .email-cta { display:block !important; width:100% !important; box-sizing:border-box !important; text-align:center !important; }
        }
    </style>
</head>
<body style="margin:0;padding:0;background-color:#F4F0EA;font-family:Arial,Helvetica,sans-serif;">
    <div style="display:none;font-size:1px;line-height:1px;max-height:0;max-width:0;opacity:0;overflow:hidden;">
        We received your reply for {{ $ticketNumber }}.
    </div>

    <table role="presentation" width="100%" cellspacing="0" cellpadding="0" border="0" style="background-color:#F4F0EA;">
        <tr>
            <td align="center" class="email-outer" style="padding:24px 12px;">
                <table role="presentation" width="700" cellspacing="0" cellpadding="0" border="0"
                    style="max-width:700px;width:100%;background-color:#FFFFFF;border-radius:14px;overflow:hidden;">
                    <tr>
                        <td class="email-pad" bgcolor="#FFFFFF" style="padding:20px 30px;border-bottom:1px solid #F0F0F0;">
                            @if (! empty($logoUrl))
                                <a href="{{ $frontendUrl }}?source=email" target="_blank" style="display:inline-block;text-decoration:none;">
                                    <img src="{{ $logoUrl }}" alt="{{ $platformName }}" width="160" height="48"
                                        style="height:48px;width:auto;max-width:180px;">
                                </a>
                            @else
                                <span style="font-weight:900;font-size:21px;color:#3B2800;">sourcenest</span>
                            @endif
                        </td>
                    </tr>

                    <tr>
                        <td class="email-pad" bgcolor="#FBF7EE"
                            style="padding:28px 30px;background-color:#FBF7EE;border-bottom:1px solid #E8D5A8;">
                            @if (! empty($heroIconUrl))
                                <img src="{{ $heroIconUrl }}" width="36" height="36" alt=""
                                    style="margin-bottom:16px;border:0;">
                            @endif
                            <div style="font-family:Georgia,'Times New Roman',serif;font-size:24px;line-height:1.25;color:#3B2800;">
                                We received <em style="color:#9A7A3A;">your reply.</em>
                            </div>
                            <div style="padding-top:8px;font-size:13.5px;line-height:1.7;color:#666666;">
                                Your message has been added to {{ $ticketNumber }}. Our support team will review it and respond as soon as possible.
                            </div>
                        </td>
                    </tr>

                    <tr>
                        <td class="email-pad" bgcolor="#FFFFFF" style="padding:28px 30px;">
                            <table role="presentation" width="100%" cellspacing="0" cellpadding="0" border="0"
                                style="background-color:#F8F8F8;border:1px solid #E6E6E6;border-radius:10px;">
                                <tr>
                                    <td style="padding:14px 16px;font-size:11px;font-weight:700;color:#8A8A8A;">TICKET</td>
                                    <td align="right" style="padding:14px 16px;font-size:12px;font-weight:800;color:#3B2800;">{{ $ticketNumber }}</td>
                                </tr>
                                @if ($ticketSubject !== '')
                                    <tr>
                                        <td style="padding:14px 16px;border-top:1px solid #E6E6E6;font-size:11px;font-weight:700;color:#8A8A8A;">SUBJECT</td>
                                        <td align="right" style="padding:14px 16px;border-top:1px solid #E6E6E6;font-size:12px;color:#3B2800;">{{ $ticketSubject }}</td>
                                    </tr>
                                @endif
                            </table>

                            @if ($replyPreview !== '')
                                <div style="margin-top:18px;padding:16px 18px;background-color:#FBF7EE;border-left:4px solid #9A7A3A;border-radius:8px;">
                                    <div style="margin-bottom:7px;font-size:9px;font-weight:900;letter-spacing:1.3px;text-transform:uppercase;color:#9A7A3A;">Your reply</div>
                                    <div style="font-size:13px;line-height:1.75;color:#464646;">{{ $replyPreview }}</div>
                                </div>
                            @endif

                            <table role="presentation" cellspacing="0" cellpadding="0" border="0" style="margin-top:22px;">
                                <tr>
                                    <td bgcolor="#3B2800" style="background-color:#3B2800;border-radius:8px;">
                                        <a class="email-cta" href="{{ $ctaUrl }}" target="_blank"
                                            style="display:inline-block;padding:14px 30px;color:#FFFFFF;font-size:12px;font-weight:900;text-decoration:none;text-transform:uppercase;border-radius:8px;">{{ $ctaLabel }}</a>
                                    </td>
                                </tr>
                            </table>
                        </td>
                    </tr>

                    <tr>
                        <td class="email-pad" bgcolor="#F8F8F8"
                            style="padding:18px 30px;background-color:#F8F8F8;border-top:1px solid #E6E6E6;font-size:10.5px;line-height:1.6;color:#8A8A8A;">
                            This is an automated acknowledgement. You do not need to reply to this email.
                            <span style="color:#D6D6D6;margin:0 5px;">·</span>{{ $supportEmail }}
                        </td>
                    </tr>
                </table>
            </td>
        </tr>
    </table>
</body>
</html>
