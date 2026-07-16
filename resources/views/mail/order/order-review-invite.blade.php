@php
    $platformName = config('app.name', 'SourceNest');
    $logoUrl = public_url('images/mail/sourcenest-logo.png');
    $heroIconUrl = public_url('images/mail/svg/review-star-hero.svg');
    $starIconUrl = public_url('images/mail/svg/review-star-fill.svg');
    $frontendUrl = rtrim((string) config('app.frontend_url', config('app.url')), '/');
    $supportEmail = config('mail.from.address', 'no-reply@sourcenest.com');
    $mailIconStyle = 'display:block;border:0;outline:none;text-decoration:none;margin:0 auto;';
    $orderNumber = $orderNumber ?? $referenceId ?? '';
    $manufacturerName = $manufacturerName ?? '';
    $productName = $productName ?? '';
    $productId = isset($productId) ? (int) $productId : null;
    $ctaUrl = $ctaUrl ?? \App\Support\Mail\MailNotificationHelper::productUrl($productId, [
        'review' => 'true',
        'order' => $orderNumber !== '' ? $orderNumber : null,
    ]);
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
    <meta name="x-apple-disable-message-reformatting">
    <title>How was your experience with {{ $manufacturerName }}? Leave a review</title>
    <!--[if mso]>
    <noscript>
        <xml>
            <o:OfficeDocumentSettings>
                <o:AllowPNG/>
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
        @media only screen and (max-width: 640px) {
            .email-outer { padding: 12px 8px !important; }
            .email-pad { padding-left: 18px !important; padding-right: 18px !important; }
            .email-hero-title { font-size: 20px !important; }
            .email-stack { display: block !important; width: 100% !important; }
            .email-stack-icon { padding-right: 0 !important; padding-bottom: 14px !important; }
            .email-footer-tag { text-align: left !important; display: block !important; padding-top: 6px !important; }
            .email-cta { display: block !important; width: 100% !important; text-align: center !important; box-sizing: border-box !important; }
            .email-evl-lbl { display: block !important; width: 100% !important; border-right: none !important; border-bottom: 1px solid #F0F0F0 !important; }
            .email-evl-val { display: block !important; width: 100% !important; }
        }
    </style>
</head>

<body style="margin:0;padding:0;background-color:#F4F0EA;font-family:Arial,Helvetica,sans-serif;-webkit-text-size-adjust:100%;-ms-text-size-adjust:100%;">
    <div style="display:none;font-size:1px;line-height:1px;max-height:0;max-width:0;opacity:0;overflow:hidden;mso-hide:all;">
        How was your experience with {{ $manufacturerName }}? Leave a review&nbsp;&zwnj;&nbsp;&zwnj;&nbsp;&zwnj;&nbsp;&zwnj;&nbsp;&zwnj;&nbsp;&zwnj;&nbsp;&zwnj;&nbsp;&zwnj;&nbsp;&zwnj;&nbsp;&zwnj;
    </div>

    <table role="presentation" width="100%" cellspacing="0" cellpadding="0" border="0" style="background-color:#F4F0EA;">
        <tr>
            <td align="center" class="email-outer" style="padding:24px 12px;">
                <table role="presentation" width="700" cellspacing="0" cellpadding="0" border="0"
                    style="max-width:700px;width:100%;background-color:#FFFFFF;border-radius:14px;overflow:hidden;">

                    <tr>
                        <td class="email-pad" bgcolor="#FFFFFF"
                            style="padding:20px 30px;background-color:#FFFFFF;border-bottom:1.5px solid #F0F0F0;">
                            @if (! empty($logoUrl))
                                <a href="{{ $frontendUrl }}?source=email" target="_blank" style="text-decoration:none;display:inline-block;">
                                    <img src="{{ $logoUrl }}" alt="{{ $platformName }}" width="160" height="80"
                                        style="height:48px;width:auto;max-width:180px;display:block;border:0;outline:none;">
                                </a>
                            @else
                                <span style="font-weight:900;font-size:21px;line-height:1;font-family:Arial,Helvetica,sans-serif;color:#3B2800;">sourcenest</span>
                            @endif
                        </td>
                    </tr>

                    <tr>
                        <td class="email-pad" bgcolor="#FBF7EE"
                            style="padding:26px 30px;background-color:#FBF7EE;border-bottom:1.5px solid #E8D5A8;">
                            <table role="presentation" width="100%" cellspacing="0" cellpadding="0" border="0">
                                <tr>
                                    <td class="email-stack email-stack-icon" width="76" valign="middle" style="width:76px;padding-right:18px;">
                                        <table role="presentation" cellspacing="0" cellpadding="0" border="0">
                                            <tr>
                                                <td width="58" height="58" align="center" valign="middle" bgcolor="#3B2800"
                                                    style="width:58px;height:58px;background-color:#3B2800;border:1.5px solid #3B2800;border-radius:14px;">
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
                                                    <span style="display:inline-block;padding:4px 11px;border-radius:20px;border:1.5px solid #E8D5A8;background-color:#FBF7EE;">
                                                        <span style="display:inline-block;width:5px;height:5px;border-radius:50%;background-color:#9A7A3A;vertical-align:middle;margin-right:5px;">&nbsp;</span>
                                                        <span style="font-weight:800;font-size:8.5px;line-height:1;font-family:Arial,Helvetica,sans-serif;letter-spacing:1.2px;text-transform:uppercase;color:#666666;vertical-align:middle;">Review Request</span>
                                                    </span>
                                                </td>
                                            </tr>
                                        </table>
                                        <div class="email-hero-title"
                                            style="font-weight:500;font-size:22px;line-height:1.17;font-family:Georgia,'Times New Roman',serif;color:#3B2800;letter-spacing:-0.2px;">
                                            How was your experience with <em style="font-style:italic;color:#9A7A3A;">{{ $manufacturerName }}?</em>
                                        </div>
                                        <div style="padding-top:6px;font-weight:400;font-size:13px;line-height:1.78;font-family:Arial,Helvetica,sans-serif;color:#666666;">
                                            Your order has been completed. Share your experience to help other buyers on sourceNest make informed sourcing decisions.
                                        </div>
                                    </td>
                                </tr>
                            </table>
                        </td>
                    </tr>

                    <tr>
                        <td class="email-pad" bgcolor="#FFFFFF" style="padding:28px 30px;background-color:#FFFFFF;border-bottom:1px solid #F0F0F0;">
                            <table role="presentation" cellspacing="0" cellpadding="0" border="0" style="margin-bottom:18px;">
                                <tr>
                                    <td width="3" bgcolor="#E8D5A8" style="width:3px;height:18px;background-color:#E8D5A8;border-radius:2px;font-size:0;line-height:0;">&nbsp;</td>
                                    <td style="padding-left:9px;font-weight:500;font-size:17px;line-height:1;font-family:Georgia,'Times New Roman',serif;color:#3B2800;">Completed order</td>
                                </tr>
                            </table>

                            <table role="presentation" width="100%" cellspacing="0" cellpadding="0" border="0"
                                style="background-color:#FFFFFF;border:1.5px solid #E6E6E6;border-radius:10px;border-collapse:separate;overflow:hidden;">
                                <tr>
                                    <td bgcolor="#F8F8F8" style="padding:11px 16px;background-color:#F8F8F8;border-bottom:1px solid #E6E6E6;">
                                        <table role="presentation" width="100%" cellspacing="0" cellpadding="0" border="0">
                                            <tr>
                                                <td style="font-weight:900;font-size:9px;line-height:1;font-family:Arial,Helvetica,sans-serif;letter-spacing:1.2px;text-transform:uppercase;color:#8A8A8A;">Order details</td>
                                                <td align="right">
                                                    <span style="display:inline-block;padding:2px 10px;border-radius:20px;border:1.5px solid #6ECFA0;background-color:#EAFAF2;font-weight:800;font-size:9px;line-height:1;font-family:Arial,Helvetica,sans-serif;color:#0A5C32;">Completed</span>
                                                </td>
                                            </tr>
                                        </table>
                                    </td>
                                </tr>
                                <tr>
                                    <td style="padding:0;">
                                        <table role="presentation" width="100%" cellspacing="0" cellpadding="0" border="0">
                                            <tr>
                                                <td class="email-evl-lbl" width="110" bgcolor="#F8F8F8" valign="middle"
                                                    style="width:110px;padding:11px 16px;background-color:#F8F8F8;border-right:1px solid #F0F0F0;font-weight:700;font-size:11px;line-height:1;font-family:Arial,Helvetica,sans-serif;color:#8A8A8A;">Order #</td>
                                                <td class="email-evl-val" valign="middle" style="padding:11px 16px;font-weight:500;font-size:12.5px;line-height:1;font-family:Arial,Helvetica,sans-serif;color:#1C1C1C;">{{ $orderNumber }}</td>
                                            </tr>
                                        </table>
                                    </td>
                                </tr>
                                <tr>
                                    <td style="padding:0;">
                                        <table role="presentation" width="100%" cellspacing="0" cellpadding="0" border="0">
                                            <tr>
                                                <td class="email-evl-lbl" width="110" bgcolor="#F8F8F8" valign="middle"
                                                    style="width:110px;padding:11px 16px;background-color:#F8F8F8;border-top:1px solid #F0F0F0;border-right:1px solid #F0F0F0;font-weight:700;font-size:11px;line-height:1;font-family:Arial,Helvetica,sans-serif;color:#8A8A8A;">Manufacturer</td>
                                                <td class="email-evl-val" valign="middle" style="padding:11px 16px;border-top:1px solid #F0F0F0;font-weight:500;font-size:12.5px;line-height:1;font-family:Arial,Helvetica,sans-serif;color:#1C1C1C;">{{ $manufacturerName }}</td>
                                            </tr>
                                        </table>
                                    </td>
                                </tr>
                                <tr>
                                    <td style="padding:0;">
                                        <table role="presentation" width="100%" cellspacing="0" cellpadding="0" border="0">
                                            <tr>
                                                <td class="email-evl-lbl" width="110" bgcolor="#F8F8F8" valign="middle"
                                                    style="width:110px;padding:11px 16px;background-color:#F8F8F8;border-top:1px solid #F0F0F0;border-right:1px solid #F0F0F0;font-weight:700;font-size:11px;line-height:1;font-family:Arial,Helvetica,sans-serif;color:#8A8A8A;">Product</td>
                                                <td class="email-evl-val" valign="middle" style="padding:11px 16px;border-top:1px solid #F0F0F0;font-weight:500;font-size:12.5px;line-height:1;font-family:Arial,Helvetica,sans-serif;color:#1C1C1C;">{{ $productName }}</td>
                                            </tr>
                                        </table>
                                    </td>
                                </tr>
                            </table>
                        </td>
                    </tr>

                    <tr>
                        <td class="email-pad" bgcolor="#F8F8F8" style="padding:28px 30px;background-color:#F8F8F8;border-bottom:1px solid #F0F0F0;">
                            <p style="margin:0 0 13px 0;font-weight:400;font-size:13.5px;line-height:1.88;font-family:Arial,Helvetica,sans-serif;color:#464646;">Hello,</p>
                            <p style="margin:0 0 13px 0;font-weight:400;font-size:13.5px;line-height:1.88;font-family:Arial,Helvetica,sans-serif;color:#464646;">
                                Your order with <strong style="font-weight:700;color:#1C1C1C;">{{ $manufacturerName }}</strong> has been completed. We'd love to hear about your experience.
                            </p>
                            <p style="margin:0 0 13px 0;font-weight:400;font-size:13.5px;line-height:1.88;font-family:Arial,Helvetica,sans-serif;color:#464646;">
                                Your review helps other buyers make more informed decisions when sourcing on sourceNest — and it gives manufacturers valuable feedback to improve their service.
                            </p>

                            <table role="presentation" width="100%" cellspacing="0" cellpadding="0" border="0"
                                style="background-color:#FBF7EE;border:1.5px solid #E8D5A8;border-radius:12px;margin-top:18px;">
                                <tr>
                                    <td align="center" style="padding:20px;">
                                        <div style="font-weight:700;font-size:12px;line-height:1;font-family:Arial,Helvetica,sans-serif;letter-spacing:0.5px;text-transform:uppercase;color:#9A7A3A;margin-bottom:12px;">Rate your experience</div>
                                        <table role="presentation" cellspacing="0" cellpadding="0" border="0" style="margin:0 auto 10px;">
                                            <tr>
                                                @for ($i = 0; $i < 5; $i++)
                                                    <td style="padding:0 2px;">
                                                        @if (! empty($starIconUrl))
                                                            <img src="{{ $starIconUrl }}" width="16" height="16" alt="" style="display:block;border:0;outline:none;">
                                                        @endif
                                                    </td>
                                                @endfor
                                            </tr>
                                        </table>
                                        <div style="font-weight:400;font-size:12px;line-height:1.5;font-family:Arial,Helvetica,sans-serif;color:#9A8A6A;">
                                            Click the button below to leave your full review on the product page.
                                        </div>
                                    </td>
                                </tr>
                            </table>

                            <table role="presentation" width="100%" cellspacing="0" cellpadding="0" border="0"
                                style="margin-top:18px;background-color:#FBF7EE;border:1.5px solid #E8D5A8;border-left:4px solid #9A7A3A;border-radius:8px;">
                                <tr>
                                    <td style="padding:14px 16px;">
                                        <div style="font-weight:900;font-size:8.5px;line-height:1;font-family:Arial,Helvetica,sans-serif;letter-spacing:1.6px;text-transform:uppercase;color:#9A7A3A;margin-bottom:5px;">Only verified buyers can review</div>
                                        <div style="font-weight:400;font-size:13px;line-height:1.65;font-family:Arial,Helvetica,sans-serif;color:#464646;">
                                            Reviews on sourceNest are only accepted from buyers with an active or completed order. Your review will be marked as <strong style="font-weight:700;color:#1C1C1C;">Reviewed Buyer</strong>.
                                        </div>
                                    </td>
                                </tr>
                            </table>
                        </td>
                    </tr>

                    <tr>
                        <td class="email-pad" bgcolor="#FFFFFF" style="padding:26px 30px 30px;background-color:#FFFFFF;">
                            <table role="presentation" cellspacing="0" cellpadding="0" border="0">
                                <tr>
                                    <td bgcolor="#3B2800" style="border-radius:8px;background-color:#3B2800;">
                                        <a class="email-cta" href="{{ $ctaUrl }}"
                                            style="display:inline-block;padding:14px 30px;background-color:#3B2800;color:#FFFFFF;font-weight:900;font-size:12px;line-height:1;font-family:Arial,Helvetica,sans-serif;letter-spacing:0.6px;text-transform:uppercase;text-decoration:none;border-radius:8px;">Write a Review</a>
                                    </td>
                                </tr>
                            </table>
                            <p style="margin:18px 0 0 0;padding-top:18px;border-top:1px solid #F0F0F0;font-weight:400;font-size:12px;line-height:1.75;font-family:Arial,Helvetica,sans-serif;color:#8A8A8A;">
                                You can only leave one review per completed order. Log in to sourceNest to submit your review.
                            </p>
                        </td>
                    </tr>

                    <tr>
                        <td class="email-pad" bgcolor="#F8F8F8" style="padding:18px 30px;background-color:#F8F8F8;border-top:1px solid #E6E6E6;">
                            <table role="presentation" width="100%" cellspacing="0" cellpadding="0" border="0">
                                <tr>
                                    <td style="font-weight:900;font-size:13px;line-height:1;font-family:Arial,Helvetica,sans-serif;color:#3B2800;letter-spacing:-0.4px;">sourcenest</td>
                                    <td class="email-footer-tag" align="right" style="font-weight:700;font-size:8px;line-height:1;font-family:Arial,Helvetica,sans-serif;letter-spacing:0.8px;text-transform:uppercase;color:#B4B4B4;">Global Sourcing Platform</td>
                                </tr>
                            </table>
                            <table role="presentation" width="100%" cellspacing="0" cellpadding="0" border="0" style="margin:10px 0;">
                                <tr>
                                    <td height="1" bgcolor="#E6E6E6" style="height:1px;background-color:#E6E6E6;font-size:0;line-height:0;">&nbsp;</td>
                                </tr>
                            </table>
                            <span style="font-weight:600;font-size:10.5px;line-height:1;font-family:Arial,Helvetica,sans-serif;color:#8A8A8A;">Automated notification — please do not reply to this email.</span>
                            <span style="font-size:9px;color:#D6D6D6;margin:0 5px;">·</span>
                            <span style="font-weight:600;font-size:10.5px;line-height:1;font-family:Arial,Helvetica,sans-serif;color:#8A8A8A;">{{ $supportEmail }}</span>
                        </td>
                    </tr>

                </table>
            </td>
        </tr>
    </table>
</body>

</html>
