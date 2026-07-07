<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <title>@yield('title', config('app.name'))</title>
    <link href="https://fonts.googleapis.com/css2?family=EB+Garamond:ital,wght@0,400;0,500;1,400&family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">
</head>
<body style="margin:0;padding:0;background-color:#ede7d9;font-family:'Inter',system-ui,-apple-system,BlinkMacSystemFont,'Segoe UI',sans-serif;-webkit-font-smoothing:antialiased;">
    @hasSection('preheader')
        <span style="display:none !important;visibility:hidden;mso-hide:all;font-size:1px;color:#ede7d9;line-height:1px;max-height:0;max-width:0;opacity:0;overflow:hidden;">
            @yield('preheader')
        </span>
    @endif

    <table role="presentation" width="100%" cellspacing="0" cellpadding="0" border="0" style="background-color:#ede7d9;">
        <tr>
            <td align="center" style="padding:40px 16px;">
                <table role="presentation" width="680" cellspacing="0" cellpadding="0" border="0" style="max-width:680px;width:100%;background-color:#ffffff;border:1px solid #d4c9b0;border-radius:12px;overflow:hidden;box-shadow:0 6px 32px rgba(44,37,23,0.13);">
                    <tr>
                        <td align="center" style="padding:40px 40px 24px 40px;background-color:#2c2517;">
                            @include('mail.partials.brand-header')
                            @hasSection('header_eyebrow')
                                <p style="margin:0 0 8px 0;font-size:9.5px;font-weight:500;letter-spacing:0.22em;text-transform:uppercase;color:#d4bc8a;opacity:0.65;font-family:'Inter',system-ui,sans-serif;">
                                    @yield('header_eyebrow')
                                </p>
                            @endif
                            @hasSection('header_title')
                                <h1 style="margin:0 0 8px 0;font-family:'EB Garamond',Georgia,serif;font-size:34px;font-weight:400;color:#f5f0e8;line-height:1.2;">
                                    @yield('header_title')
                                </h1>
                            @endif
                            @hasSection('header_subtitle')
                                <p style="margin:0;font-family:'EB Garamond',Georgia,serif;font-style:italic;font-size:14px;color:#d4bc8a;opacity:0.8;">
                                    @yield('header_subtitle')
                                </p>
                            @endif
                        </td>
                    </tr>
                    <tr>
                        <td style="padding:24px 40px 40px 40px;background-color:#f5f0e8;">
                            @yield('content')
                            @include('mail.partials.signature-row')
                        </td>
                    </tr>
                    <tr>
                        <td style="padding:16px 40px;background-color:#3d3220;">
                            @include('mail.partials.footer')
                        </td>
                    </tr>
                </table>
            </td>
        </tr>
    </table>
</body>
</html>
