<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <title>@yield('title', config('app.name'))</title>
    <link href="https://fonts.googleapis.com/css2?family=Nunito:wght@400;600;700;800;900&family=Lora:ital,wght@0,400;0,600;1,400;1,600&display=swap" rel="stylesheet">
</head>
<body style="margin:0;padding:0;background-color:#C8C2B6;font-family:'Nunito',system-ui,-apple-system,BlinkMacSystemFont,'Segoe UI',sans-serif;-webkit-font-smoothing:antialiased;">
    @hasSection('preheader')
        <span style="display:none !important;visibility:hidden;mso-hide:all;font-size:1px;color:#C8C2B6;line-height:1px;max-height:0;max-width:0;opacity:0;overflow:hidden;">
            @yield('preheader')
        </span>
    @endif

    <table role="presentation" width="100%" cellspacing="0" cellpadding="0" border="0" style="background-color:#C8C2B6;">
        <tr>
            <td align="center" style="padding:32px 16px;">
                <table role="presentation" width="600" cellspacing="0" cellpadding="0" border="0" style="max-width:600px;width:100%;background-color:#FFFFFF;border-radius:14px;overflow:hidden;box-shadow:0 4px 16px rgba(0,0,0,0.06),0 20px 56px rgba(0,0,0,0.09);">
                    @hasSection('subject_preview')
                        <tr>
                            <td style="background-color:#F8F8F8;border-bottom:1px solid #E6E6E6;padding:9px 30px;">
                                <span style="font:900 7.5px/1 'Nunito',sans-serif;letter-spacing:1.5px;text-transform:uppercase;color:#B4B4B4;">Subject</span>
                                <span style="font:500 12.5px/1 'Nunito',sans-serif;color:#2E2E2E;margin-left:10px;">@yield('subject_preview')</span>
                            </td>
                        </tr>
                    @endif

                    @yield('header')
                    @yield('hero')

                    @hasSection('body_rows')
                        @yield('body_rows')
                    @else
                        <tr>
                            <td style="padding:28px 30px 8px 30px;background-color:#FFFFFF;">
                                @yield('content')
                            </td>
                        </tr>

                        @hasSection('cta')
                            <tr>
                                <td style="padding:8px 30px 28px 30px;background-color:#FFFFFF;">
                                    @yield('cta')
                                </td>
                            </tr>
                        @endif

                        <tr>
                            <td style="padding:0 30px 24px 30px;background-color:#FFFFFF;">
                                @include('mail.partials.sourcenest.footer-inline', ['footerTag' => $footerTag ?? __('mail.demo.footer_tag_default')])
                            </td>
                        </tr>
                    @endif

                    @hasSection('custom_footer')
                        @yield('custom_footer')
                    @endif
                </table>
            </td>
        </tr>
    </table>
</body>
</html>
