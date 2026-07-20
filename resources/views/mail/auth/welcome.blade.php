@php
    $recipientName = trim($firstName ?? '') !== '' ? trim($firstName) : 'there';
    $platformName = config('app.name', 'SourceNest');
    $logoUrl = public_url('images/mail/sourcenest-logo.png');
    $mailIconStyle = 'display:block;border:0;outline:none;text-decoration:none;';
    $searchIconUrl = public_url('images/mail/icons/search.png');
    $caseIconUrl = public_url('images/mail/icons/case.png');
    $globeIconUrl = public_url('images/mail/icons/globe.png');
    $carriculamIconUrl = public_url('images/mail/icons/carriculam.png');
    $platformUrl = \App\Support\Mail\MailNotificationHelper::frontendUrl('dashboard/buyer');
    $suppliersUrl = \App\Support\Mail\MailNotificationHelper::frontendUrl('suppliers');
    $frontendUrl = rtrim((string) config('app.frontend_url', config('app.url')), '/');
    $supportEmail = config('mail.from.address', 'support@sourcenest.com');
@endphp
<!DOCTYPE html>
<html lang="en" xmlns="http://www.w3.org/1999/xhtml" xmlns:v="urn:schemas-microsoft-com:vml" xmlns:o="urn:schemas-microsoft-com:office:office">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <meta name="x-apple-disable-message-reformatting">
    <meta name="format-detection" content="telephone=no,address=no,email=no,date=no,url=no">
    <meta name="color-scheme" content="light only">
    <meta name="supported-color-schemes" content="light">
    <title>Welcome to {{ $platformName }}</title>
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
<body style="margin:0;padding:0;background-color:#C8C2B6;font-family:Arial,Helvetica,sans-serif;-webkit-text-size-adjust:100%;-ms-text-size-adjust:100%;">
    <span style="display:none !important;visibility:hidden;mso-hide:all;font-size:1px;color:#C8C2B6;line-height:1px;max-height:0;max-width:0;opacity:0;overflow:hidden;">Your {{ $platformName }} account is active. Search manufacturers, send sourcing requests, and manage your supply chain.</span>

    <table role="presentation" width="100%" cellspacing="0" cellpadding="0" border="0" style="background-color:#C8C2B6;">
        <tr>
            <td align="center" style="padding:32px 16px;">
                <table role="presentation" width="600" cellspacing="0" cellpadding="0" border="0" style="max-width:600px;width:100%;background-color:#FFFFFF;border-radius:14px;overflow:hidden;">

                    <tr>
                        <td bgcolor="#FFFFFF" style="padding:20px 30px;background-color:#FFFFFF;border-bottom:1.5px solid #F0F0F0;">
                            <table role="presentation" width="100%" cellspacing="0" cellpadding="0" border="0">
                                <tr>
                                    <td align="left" valign="middle">
                                        <img src="{{ $logoUrl }}" alt="{{ $platformName }}" width="140" style="display:block;height:auto;max-height:40px;width:auto;border:0;">
                                    </td>
                                    <td align="right" valign="middle">
                                        <span style="display:inline-block;padding:4px 12px;border-radius:20px;border:1.5px solid #E6E6E6;background-color:#F8F8F8;font-weight:700;font-size:9px;line-height:1;font-family:Arial,Helvetica,sans-serif;letter-spacing:0.4px;text-transform:uppercase;color:#8A8A8A;">Buyer Account</span>
                                    </td>
                                </tr>
                            </table>
                        </td>
                    </tr>

                    <tr>
                        <td bgcolor="#FBF7EE" style="padding:34px 30px 40px;background-color:#FBF7EE;border-bottom:1.5px solid #E8D5A8;">
                            <table role="presentation" width="100%" cellspacing="0" cellpadding="0" border="0">
                                <tr>
                                    <td style="padding:0 0 14px 0;">
                                        <table role="presentation" cellspacing="0" cellpadding="0" border="0">
                                            <tr>
                                                <td style="padding:4px 11px;border-radius:20px;border:1.5px solid #A8C0F0;background-color:#EDF2FF;">
                                                    <table role="presentation" cellspacing="0" cellpadding="0" border="0">
                                                        <tr>
                                                            <td width="5" valign="middle" style="line-height:0;font-size:0;">
                                                                <span style="display:block;width:5px;height:5px;border-radius:50%;background-color:#1258B8;">&nbsp;</span>
                                                            </td>
                                                            <td valign="middle" style="padding-left:5px;font-weight:800;font-size:8.5px;line-height:1;font-family:Arial,Helvetica,sans-serif;letter-spacing:1.2px;text-transform:uppercase;color:#666666;">Buyer Account</td>
                                                        </tr>
                                                    </table>
                                                </td>
                                            </tr>
                                        </table>
                                    </td>
                                </tr>
                                <tr>
                                    <td style="padding:0 0 13px 0;">
                                        <table role="presentation" cellspacing="0" cellpadding="0" border="0">
                                            <tr>
                                                <td width="20" valign="middle" style="line-height:0;font-size:0;">
                                                    <span style="display:block;width:20px;height:2px;border-radius:1px;background-color:#E8D5A8;">&nbsp;</span>
                                                </td>
                                                <td valign="middle" style="padding-left:8px;font-weight:800;font-size:8.5px;line-height:1;font-family:Arial,Helvetica,sans-serif;letter-spacing:2px;text-transform:uppercase;color:#9A7A3A;">Welcome</td>
                                            </tr>
                                        </table>
                                    </td>
                                </tr>
                                <tr>
                                    <td style="font-weight:500;font-size:31px;line-height:1.17;font-family:Georgia,'Times New Roman',serif;color:#3B2800;letter-spacing:-0.2px;">
                                        Find the right manufacturers.<br>
                                        <em style="font-style:italic;color:#9A7A3A;">Connect directly.</em>
                                    </td>
                                </tr>
                                <tr>
                                    <td style="padding-top:12px;font-weight:400;font-size:13.5px;line-height:1.78;font-family:Arial,Helvetica,sans-serif;color:#666666;">Your {{ $platformName }} account is active. Search manufacturers, send sourcing requests, and manage your supply chain — all in one place, with zero middlemen.</td>
                                </tr>
                            </table>
                        </td>
                    </tr>

                    <tr>
                        <td bgcolor="#FFFFFF" style="padding:28px 30px;background-color:#FFFFFF;border-bottom:1px solid #F0F0F0;">
                            <div style="font-weight:500;font-size:17px;line-height:1;font-family:Georgia,'Times New Roman',serif;color:#3B2800;margin-bottom:13px;">Dear {{ $recipientName }},</div>
                            <p style="margin:0 0 13px 0;font-weight:600;font-size:14.5px;line-height:1.88;font-family:Arial,Helvetica,sans-serif;color:#2E2E2E;">Welcome to {{ $platformName }} — a professional B2B sourcing platform built for buyers who need direct, reliable access to manufacturers worldwide.</p>
                            <p style="margin:0;font-weight:400;font-size:13.5px;line-height:1.88;font-family:Arial,Helvetica,sans-serif;color:#464646;">You now have full access to search suppliers, send sourcing requests, compare manufacturers, and manage all communication from your dashboard. No agents. No commission fees. Just direct connections.</p>

                            <table role="presentation" width="100%" cellspacing="0" cellpadding="0" border="0" style="margin-top:18px;border:1.5px solid #E6E6E6;border-radius:10px;border-collapse:separate;">
                                <tr>
                                    <td width="33%" valign="top" bgcolor="#FFFFFF" style="padding:18px 14px;background-color:#FFFFFF;border-right:1.5px solid #E6E6E6;">
                                        <table role="presentation" cellspacing="0" cellpadding="0" border="0" style="margin-bottom:10px;">
                                            <tr>
                                                <td width="28" height="28" align="center" valign="middle" bgcolor="#FBF7EE" style="width:28px;height:28px;background-color:#FBF7EE;border:1.5px solid #E8D5A8;border-radius:7px;">
                                                    <img src="{{ $searchIconUrl }}" width="13" height="13" alt="" style="{{ $mailIconStyle }}">
                                                </td>
                                            </tr>
                                        </table>
                                        <div style="font-weight:700;font-size:13px;line-height:1.3;font-family:Arial,Helvetica,sans-serif;color:#3B2800;margin-bottom:4px;">Direct Access</div>
                                        <div style="font-weight:400;font-size:11.5px;line-height:1.5;font-family:Arial,Helvetica,sans-serif;color:#8A8A8A;">Contact manufacturers directly — no agents or commissions</div>
                                    </td>
                                    <td width="33%" valign="top" bgcolor="#FFFFFF" style="padding:18px 14px;background-color:#FFFFFF;border-right:1.5px solid #E6E6E6;">
                                        <table role="presentation" cellspacing="0" cellpadding="0" border="0" style="margin-bottom:10px;">
                                            <tr>
                                                <td width="28" height="28" align="center" valign="middle" bgcolor="#FBF7EE" style="width:28px;height:28px;background-color:#FBF7EE;border:1.5px solid #E8D5A8;border-radius:7px;">
                                                    <img src="{{ $caseIconUrl }}" width="13" height="13" alt="" style="{{ $mailIconStyle }}">
                                                </td>
                                            </tr>
                                        </table>
                                        <div style="font-weight:700;font-size:13px;line-height:1.3;font-family:Arial,Helvetica,sans-serif;color:#3B2800;margin-bottom:4px;">Simple Requests</div>
                                        <div style="font-weight:400;font-size:11.5px;line-height:1.5;font-family:Arial,Helvetica,sans-serif;color:#8A8A8A;">Send sourcing requests to multiple manufacturers in seconds</div>
                                    </td>
                                    <td width="33%" valign="top" bgcolor="#FFFFFF" style="padding:18px 14px;background-color:#FFFFFF;">
                                        <table role="presentation" cellspacing="0" cellpadding="0" border="0" style="margin-bottom:10px;">
                                            <tr>
                                                <td width="28" height="28" align="center" valign="middle" bgcolor="#FBF7EE" style="width:28px;height:28px;background-color:#FBF7EE;border:1.5px solid #E8D5A8;border-radius:7px;">
                                                    <img src="{{ $globeIconUrl }}" width="13" height="13" alt="" style="{{ $mailIconStyle }}">
                                                </td>
                                            </tr>
                                        </table>
                                        <div style="font-weight:700;font-size:13px;line-height:1.3;font-family:Arial,Helvetica,sans-serif;color:#3B2800;margin-bottom:4px;">Global Reach</div>
                                        <div style="font-weight:400;font-size:11.5px;line-height:1.5;font-family:Arial,Helvetica,sans-serif;color:#8A8A8A;">Discover manufacturers across industries and regions worldwide</div>
                                    </td>
                                </tr>
                            </table>
                        </td>
                    </tr>

                    <tr>
                        <td bgcolor="#F8F8F8" style="padding:28px 30px;background-color:#F8F8F8;border-bottom:1px solid #F0F0F0;">
                            <table role="presentation" cellspacing="0" cellpadding="0" border="0" style="margin-bottom:18px;">
                                <tr>
                                    <td width="3" bgcolor="#E8D5A8" style="background-color:#E8D5A8;">&nbsp;</td>
                                    <td style="padding-left:9px;font-weight:500;font-size:17px;line-height:1;font-family:Georgia,'Times New Roman',serif;color:#3B2800;">How {{ $platformName }} <em style="font-style:italic;color:#9A7A3A;">works</em></td>
                                </tr>
                            </table>
                            <table role="presentation" width="100%" cellspacing="0" cellpadding="0" border="0" style="margin-bottom:10px;">
                                <tr>
                                    <td width="50%" valign="top" style="padding-right:5px;">
                                        <table role="presentation" width="100%" cellspacing="0" cellpadding="0" border="0" style="border:1.5px solid #E6E6E6;border-radius:10px;border-collapse:separate;">
                                            <tr>
                                                <td bgcolor="#FFFFFF" style="padding:18px 15px;background-color:#FFFFFF;">
                                                    <div style="font-weight:800;font-size:13px;line-height:1;font-family:Arial,Helvetica,sans-serif;color:#3B2800;margin-bottom:4px;">Search manufacturers</div>
                                                    <div style="font-weight:400;font-size:12px;line-height:1.55;font-family:Arial,Helvetica,sans-serif;color:#8A8A8A;">Find suppliers by product, region, or industry using our search tools.</div>
                                                </td>
                                            </tr>
                                        </table>
                                    </td>
                                    <td width="50%" valign="top" style="padding-left:5px;">
                                        <table role="presentation" width="100%" cellspacing="0" cellpadding="0" border="0" style="border:1.5px solid #E6E6E6;border-radius:10px;border-collapse:separate;">
                                            <tr>
                                                <td bgcolor="#FFFFFF" style="padding:18px 15px;background-color:#FFFFFF;">
                                                    <div style="font-weight:800;font-size:13px;line-height:1;font-family:Arial,Helvetica,sans-serif;color:#3B2800;margin-bottom:4px;">Review &amp; compare profiles</div>
                                                    <div style="font-weight:400;font-size:12px;line-height:1.55;font-family:Arial,Helvetica,sans-serif;color:#8A8A8A;">Examine product listings, capabilities, and lead times before deciding.</div>
                                                </td>
                                            </tr>
                                        </table>
                                    </td>
                                </tr>
                            </table>
                            <table role="presentation" width="100%" cellspacing="0" cellpadding="0" border="0">
                                <tr>
                                    <td width="50%" valign="top" style="padding-right:5px;">
                                        <table role="presentation" width="100%" cellspacing="0" cellpadding="0" border="0" style="border:1.5px solid #E6E6E6;border-radius:10px;border-collapse:separate;">
                                            <tr>
                                                <td bgcolor="#FFFFFF" style="padding:18px 15px;background-color:#FFFFFF;">
                                                    <div style="font-weight:800;font-size:13px;line-height:1;font-family:Arial,Helvetica,sans-serif;color:#3B2800;margin-bottom:4px;">Send a direct inquiry</div>
                                                    <div style="font-weight:400;font-size:12px;line-height:1.55;font-family:Arial,Helvetica,sans-serif;color:#8A8A8A;">Contact manufacturers through {{ $platformName }} — no cold emails or agents.</div>
                                                </td>
                                            </tr>
                                        </table>
                                    </td>
                                    <td width="50%" valign="top" style="padding-left:5px;">
                                        <table role="presentation" width="100%" cellspacing="0" cellpadding="0" border="0" style="border:1.5px solid #E6E6E6;border-radius:10px;border-collapse:separate;">
                                            <tr>
                                                <td bgcolor="#FFFFFF" style="padding:18px 15px;background-color:#FFFFFF;">
                                                    <div style="font-weight:800;font-size:13px;line-height:1;font-family:Arial,Helvetica,sans-serif;color:#3B2800;margin-bottom:4px;">Build your supply chain</div>
                                                    <div style="font-weight:400;font-size:12px;line-height:1.55;font-family:Arial,Helvetica,sans-serif;color:#8A8A8A;">Compare responses, negotiate terms, and finalize your sourcing decisions.</div>
                                                </td>
                                            </tr>
                                        </table>
                                    </td>
                                </tr>
                            </table>
                        </td>
                    </tr>

                    <tr>
                        <td bgcolor="#FFFFFF" style="padding:28px 30px;background-color:#FFFFFF;border-bottom:1px solid #F0F0F0;">
                            <table role="presentation" cellspacing="0" cellpadding="0" border="0" style="margin-bottom:18px;">
                                <tr>
                                    <td width="3" bgcolor="#E8D5A8" style="background-color:#E8D5A8;">&nbsp;</td>
                                    <td style="padding-left:9px;font-weight:500;font-size:17px;line-height:1;font-family:Georgia,'Times New Roman',serif;color:#3B2800;">What you can do <em style="font-style:italic;color:#9A7A3A;">today</em></td>
                                </tr>
                            </table>
                            <table role="presentation" width="100%" cellspacing="0" cellpadding="0" border="0" style="border:1.5px solid #E6E6E6;border-radius:10px;border-collapse:separate;">
                                <tr>
                                    <td bgcolor="#FFFFFF" style="padding:15px 17px;background-color:#FFFFFF;border-bottom:1px solid #F0F0F0;">
                                        <table role="presentation" width="100%" cellspacing="0" cellpadding="0" border="0">
                                            <tr>
                                                <td width="28" valign="top" style="padding-right:14px;">
                                                    <table role="presentation" cellspacing="0" cellpadding="0" border="0">
                                                        <tr>
                                                            <td width="28" height="28" align="center" valign="middle" bgcolor="#FBF7EE" style="width:28px;height:28px;background-color:#FBF7EE;border:1.5px solid #E8D5A8;border-radius:7px;">
                                                                <img src="{{ $searchIconUrl }}" width="13" height="13" alt="" style="{{ $mailIconStyle }}">
                                                            </td>
                                                        </tr>
                                                    </table>
                                                </td>
                                                <td valign="top">
                                                    <div style="font-weight:800;font-size:13px;line-height:1;font-family:Arial,Helvetica,sans-serif;color:#1C1C1C;margin-bottom:4px;">Discover and compare manufacturers</div>
                                                    <div style="font-weight:400;font-size:12px;line-height:1.55;font-family:Arial,Helvetica,sans-serif;color:#8A8A8A;">Browse manufacturer profiles filtered by product, category, or region. Build shortlists instantly.</div>
                                                </td>
                                            </tr>
                                        </table>
                                    </td>
                                </tr>
                                <tr>
                                    <td bgcolor="#FFFFFF" style="padding:15px 17px;background-color:#FFFFFF;border-bottom:1px solid #F0F0F0;">
                                        <table role="presentation" width="100%" cellspacing="0" cellpadding="0" border="0">
                                            <tr>
                                                <td width="28" valign="top" style="padding-right:14px;">
                                                    <table role="presentation" cellspacing="0" cellpadding="0" border="0">
                                                        <tr>
                                                            <td width="28" height="28" align="center" valign="middle" bgcolor="#FBF7EE" style="width:28px;height:28px;background-color:#FBF7EE;border:1.5px solid #E8D5A8;border-radius:7px;">
                                                                <img src="{{ $caseIconUrl }}" width="13" height="13" alt="" style="{{ $mailIconStyle }}">
                                                            </td>
                                                        </tr>
                                                    </table>
                                                </td>
                                                <td valign="top">
                                                    <div style="font-weight:800;font-size:13px;line-height:1;font-family:Arial,Helvetica,sans-serif;color:#1C1C1C;margin-bottom:4px;">Send sourcing requests with ease</div>
                                                    <div style="font-weight:400;font-size:12px;line-height:1.55;font-family:Arial,Helvetica,sans-serif;color:#8A8A8A;">Reach manufacturers directly with your requirements, volume, and timeline.</div>
                                                </td>
                                            </tr>
                                        </table>
                                    </td>
                                </tr>
                                <tr>
                                    <td bgcolor="#FFFFFF" style="padding:15px 17px;background-color:#FFFFFF;">
                                        <table role="presentation" width="100%" cellspacing="0" cellpadding="0" border="0">
                                            <tr>
                                                <td width="28" valign="top" style="padding-right:14px;">
                                                    <table role="presentation" cellspacing="0" cellpadding="0" border="0">
                                                        <tr>
                                                            <td width="28" height="28" align="center" valign="middle" bgcolor="#FBF7EE" style="width:28px;height:28px;background-color:#FBF7EE;border:1.5px solid #E8D5A8;border-radius:7px;">
                                                                <img src="{{ $carriculamIconUrl }}" width="13" height="13" alt="" style="{{ $mailIconStyle }}">
                                                            </td>
                                                        </tr>
                                                    </table>
                                                </td>
                                                <td valign="top">
                                                    <div style="font-weight:800;font-size:13px;line-height:1;font-family:Arial,Helvetica,sans-serif;color:#1C1C1C;margin-bottom:4px;">Manage all communication in one place</div>
                                                    <div style="font-weight:400;font-size:12px;line-height:1.55;font-family:Arial,Helvetica,sans-serif;color:#8A8A8A;">Every conversation and supplier interaction organized in your {{ $platformName }} dashboard.</div>
                                                </td>
                                            </tr>
                                        </table>
                                    </td>
                                </tr>
                            </table>
                        </td>
                    </tr>

                    <tr>
                        <td bgcolor="#F8F8F8" style="padding:28px 30px;background-color:#F8F8F8;border-bottom:1px solid #F0F0F0;">
                            <table role="presentation" cellspacing="0" cellpadding="0" border="0" style="margin-bottom:18px;">
                                <tr>
                                    <td width="3" bgcolor="#E8D5A8" style="background-color:#E8D5A8;">&nbsp;</td>
                                    <td style="padding-left:9px;font-weight:500;font-size:17px;line-height:1;font-family:Georgia,'Times New Roman',serif;color:#3B2800;">Your first <em style="font-style:italic;color:#9A7A3A;">three steps</em></td>
                                </tr>
                            </table>
                            <table role="presentation" width="100%" cellspacing="0" cellpadding="0" border="0" style="margin-top:18px;">
                                <tr>
                                    <td width="26" valign="top" style="padding:15px 0;border-bottom:1px solid #F0F0F0;">
                                        <table role="presentation" cellspacing="0" cellpadding="0" border="0">
                                            <tr>
                                                <td align="center" width="26" height="26" bgcolor="#FBF7EE" style="width:26px;height:26px;background-color:#FBF7EE;border:1.5px solid #E8D5A8;border-radius:50%;font-weight:900;font-size:11px;line-height:26px;font-family:Arial,Helvetica,sans-serif;color:#9A7A3A;text-align:center;">1</td>
                                            </tr>
                                            <tr>
                                                <td align="center" style="padding-top:4px;font-size:0;line-height:0;">
                                                    <div style="width:1px;height:24px;background-color:#E6E6E6;margin:0 auto;">&nbsp;</div>
                                                </td>
                                            </tr>
                                        </table>
                                    </td>
                                    <td valign="top" style="padding:15px 0 15px 14px;border-bottom:1px solid #F0F0F0;">
                                        <div style="font-weight:700;font-size:13.5px;line-height:1;font-family:Arial,Helvetica,sans-serif;color:#1C1C1C;margin-bottom:3px;">Run your first search</div>
                                        <div style="font-weight:400;font-size:12.5px;line-height:1.55;font-family:Arial,Helvetica,sans-serif;color:#8A8A8A;">Go to {{ $platformName }} and search by product name, material, or industry. Start specific — you can always broaden your search.</div>
                                    </td>
                                </tr>
                                <tr>
                                    <td width="26" valign="top" style="padding:15px 0;border-bottom:1px solid #F0F0F0;">
                                        <table role="presentation" cellspacing="0" cellpadding="0" border="0">
                                            <tr>
                                                <td align="center" width="26" height="26" bgcolor="#FBF7EE" style="width:26px;height:26px;background-color:#FBF7EE;border:1.5px solid #E8D5A8;border-radius:50%;font-weight:900;font-size:11px;line-height:26px;font-family:Arial,Helvetica,sans-serif;color:#9A7A3A;text-align:center;">2</td>
                                            </tr>
                                            <tr>
                                                <td align="center" style="padding-top:4px;font-size:0;line-height:0;">
                                                    <div style="width:1px;height:24px;background-color:#E6E6E6;margin:0 auto;">&nbsp;</div>
                                                </td>
                                            </tr>
                                        </table>
                                    </td>
                                    <td valign="top" style="padding:15px 0 15px 14px;border-bottom:1px solid #F0F0F0;">
                                        <div style="font-weight:700;font-size:13.5px;line-height:1;font-family:Arial,Helvetica,sans-serif;color:#1C1C1C;margin-bottom:3px;">Review and shortlist manufacturers</div>
                                        <div style="font-weight:400;font-size:12.5px;line-height:1.55;font-family:Arial,Helvetica,sans-serif;color:#8A8A8A;">Open profiles, review production capacity and listings, and shortlist the suppliers that match your requirements.</div>
                                    </td>
                                </tr>
                                <tr>
                                    <td width="26" valign="top" style="padding:15px 0;">
                                        <table role="presentation" cellspacing="0" cellpadding="0" border="0">
                                            <tr>
                                                <td align="center" width="26" height="26" bgcolor="#FBF7EE" style="width:26px;height:26px;background-color:#FBF7EE;border:1.5px solid #E8D5A8;border-radius:50%;font-weight:900;font-size:11px;line-height:26px;font-family:Arial,Helvetica,sans-serif;color:#9A7A3A;text-align:center;">3</td>
                                            </tr>
                                        </table>
                                    </td>
                                    <td valign="top" style="padding:15px 0 15px 14px;">
                                        <div style="font-weight:700;font-size:13.5px;line-height:1;font-family:Arial,Helvetica,sans-serif;color:#1C1C1C;margin-bottom:3px;">Send your first direct inquiry</div>
                                        <div style="font-weight:400;font-size:12.5px;line-height:1.55;font-family:Arial,Helvetica,sans-serif;color:#8A8A8A;">Contact shortlisted suppliers through the platform. State your requirements, volume, and timeline clearly.</div>
                                    </td>
                                </tr>
                            </table>
                            <table role="presentation" width="100%" cellspacing="0" cellpadding="0" border="0" style="margin-top:16px;background-color:#FBF7EE;border:1.5px solid #E8D5A8;border-left:4px solid #9A7A3A;border-radius:8px;border-collapse:separate;">
                                <tr>
                                    <td style="padding:14px 16px;">
                                        <div style="font-weight:900;font-size:8.5px;line-height:1;font-family:Arial,Helvetica,sans-serif;letter-spacing:1.6px;text-transform:uppercase;color:#9A7A3A;margin-bottom:5px;">Pro Tip</div>
                                        <div style="font-weight:400;font-size:13px;line-height:1.65;font-family:Arial,Helvetica,sans-serif;color:#464646;">Contact multiple suppliers for any sourcing requirement. This gives you options to compare pricing, quality, and lead times before committing to a manufacturing partner.</div>
                                    </td>
                                </tr>
                            </table>
                        </td>
                    </tr>

                    <tr>
                        <td bgcolor="#FFFFFF" style="padding:26px 30px 30px;background-color:#FFFFFF;border-top:1px solid #F0F0F0;">
                            <table role="presentation" cellspacing="0" cellpadding="0" border="0">
                                <tr>
                                    <td>
                                        <a href="{{ $platformUrl }}" style="display:inline-block;padding:14px 30px;background-color:#9A7A3A;color:#FFFFFF;font-weight:900;font-size:12px;line-height:1;font-family:Arial,Helvetica,sans-serif;letter-spacing:0.6px;text-transform:uppercase;text-decoration:none;border-radius:8px;">Explore the Platform</a>
                                    </td>
                                </tr>
                                <tr>
                                    <td style="padding-top:10px;">
                                        <a href="{{ $suppliersUrl }}" style="font-weight:600;font-size:12.5px;line-height:1;font-family:Arial,Helvetica,sans-serif;color:#B4B4B4;text-decoration:none;">Browse the supplier directory →</a>
                                    </td>
                                </tr>
                                <tr>
                                    <td style="padding-top:18px;border-top:1px solid #F0F0F0;">
                                        <span style="font-weight:400;font-size:12px;line-height:1.75;font-family:Arial,Helvetica,sans-serif;color:#8A8A8A;">
                                            Questions?
                                            <a href="mailto:{{ $supportEmail }}" style="color:#9A7A3A;text-decoration:underline;">{{ $supportEmail }}</a>
                                        </span>
                                    </td>
                                </tr>
                            </table>
                        </td>
                    </tr>

                    <tr>
                        <td bgcolor="#F8F8F8" style="padding:18px 30px;background-color:#F8F8F8;border-top:1px solid #E6E6E6;">
                            <table role="presentation" width="100%" cellspacing="0" cellpadding="0" border="0">
                                <tr>
                                    <td style="font-weight:900;font-size:13px;line-height:1;font-family:Arial,Helvetica,sans-serif;color:#3B2800;letter-spacing:-0.4px;">{{ $platformName }}</td>
                                    <td align="right" style="font-weight:700;font-size:8px;line-height:1;font-family:Arial,Helvetica,sans-serif;letter-spacing:0.8px;text-transform:uppercase;color:#B4B4B4;">Global Sourcing Platform</td>
                                </tr>
                            </table>
                            <table role="presentation" width="100%" cellspacing="0" cellpadding="0" border="0" style="margin:10px 0;">
                                <tr>
                                    <td height="1" bgcolor="#E6E6E6" style="height:1px;background-color:#E6E6E6;font-size:0;line-height:0;">&nbsp;</td>
                                </tr>
                            </table>
                            <span style="font-weight:600;font-size:10.5px;line-height:1;font-family:Arial,Helvetica,sans-serif;color:#B4B4B4;">
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
