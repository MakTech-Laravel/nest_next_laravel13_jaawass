<table role="presentation" width="100%" cellspacing="0" cellpadding="0" border="0" style="border-top:1.5px solid #F0F0F0;padding-top:18px;">
    <tr>
        <td>
            <table role="presentation" width="100%" cellspacing="0" cellpadding="0" border="0">
                <tr>
                    <td style="font:900 13px/1 'Nunito',sans-serif;color:#3B2800;letter-spacing:-0.3px;">sourcenest</td>
                    <td align="right" style="font:700 8px/1 'Nunito',sans-serif;letter-spacing:0.8px;text-transform:uppercase;color:#9A7A3A;">{{ $footerTag }}</td>
                </tr>
            </table>
            <table role="presentation" width="100%" cellspacing="0" cellpadding="0" border="0" style="margin-top:12px;">
                <tr>
                    <td style="font:500 11px/1.6 'Nunito',sans-serif;color:#8A8A8A;">
                        <a href="{{ rtrim((string) config('app.frontend_url', config('app.url')), '/') }}/privacy" style="color:#8A8A8A;text-decoration:none;">Privacy</a>
                        <span style="margin:0 6px;">·</span>
                        <a href="{{ rtrim((string) config('app.frontend_url', config('app.url')), '/') }}/terms" style="color:#8A8A8A;text-decoration:none;">Terms</a>
                        <span style="margin:0 6px;">·</span>
                        <a href="mailto:support@sourcenest.com" style="color:#8A8A8A;text-decoration:none;">Support</a>
                    </td>
                </tr>
            </table>
        </td>
    </tr>
</table>
