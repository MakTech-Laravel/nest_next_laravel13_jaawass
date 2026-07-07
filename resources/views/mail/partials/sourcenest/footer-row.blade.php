@php
    $frontend = rtrim((string) config('app.frontend_url', config('app.url')), '/');
    $footerTag = $footerTag ?? __('mail.demo.footer_tag_default');
    $variant = $variant ?? 'default';
@endphp
<tr>
    <td style="padding:18px 30px;background-color:#F8F8F8;border-top:1px solid #E6E6E6;">
        <table role="presentation" width="100%" cellspacing="0" cellpadding="0" border="0">
            <tr>
                <td style="font:900 13px/1 'Nunito',sans-serif;color:#3B2800;letter-spacing:-0.4px;">sourcenest</td>
                <td align="right" style="font:700 8px/1 'Nunito',sans-serif;letter-spacing:0.8px;text-transform:uppercase;color:#B4B4B4;">{{ $footerTag }}</td>
            </tr>
        </table>
        <div style="height:1px;background-color:#E6E6E6;margin:10px 0;"></div>
        <table role="presentation" cellspacing="0" cellpadding="0" border="0">
            <tr>
                <td style="font:600 10.5px/1 'Nunito',sans-serif;color:#B4B4B4;">
                    @if ($variant === 'marketing')
                        <a href="{{ $frontend }}/unsubscribe" style="color:#B4B4B4;text-decoration:none;">Unsubscribe</a>
                        <span style="margin:0 5px;font-size:9px;color:#E6E6E6;">·</span>
                        <a href="{{ $frontend }}/preferences" style="color:#B4B4B4;text-decoration:none;">Preferences</a>
                        <span style="margin:0 5px;font-size:9px;color:#E6E6E6;">·</span>
                    @endif
                    <a href="{{ $frontend }}/privacy" style="color:#B4B4B4;text-decoration:none;">Privacy</a>
                    <span style="margin:0 5px;font-size:9px;color:#E6E6E6;">·</span>
                    <a href="{{ $frontend }}/terms" style="color:#B4B4B4;text-decoration:none;">Terms</a>
                    @if ($variant === 'default')
                        <span style="margin:0 5px;font-size:9px;color:#E6E6E6;">·</span>
                        <a href="mailto:support@sourcenest.com" style="color:#B4B4B4;text-decoration:none;">Support</a>
                    @endif
                </td>
            </tr>
        </table>
    </td>
</tr>
