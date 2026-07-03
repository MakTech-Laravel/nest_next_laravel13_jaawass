@if (!empty($ctaUrl) && !empty($ctaLabel))
    <table role="presentation" width="100%" cellspacing="0" cellpadding="0" border="0" style="margin:24px 0;">
        <tr>
            <td align="center">
                <table role="presentation" cellspacing="0" cellpadding="0" border="0" style="margin:0 auto;">
                    <tr>
                        <td style="border-radius:8px;background-color:#2c2517;border:1px solid #b89d5e;">
                            <a href="{{ $ctaUrl }}" style="display:inline-block;padding:14px 36px;font-size:13px;font-weight:600;letter-spacing:0.08em;color:#d4bc8a;text-decoration:none;font-family:'Inter',system-ui,sans-serif;">{{ $ctaLabel }}</a>
                        </td>
                    </tr>
                </table>
                @if (!empty($ctaNote))
                    <p style="margin:8px 0 0 0;font-size:11.5px;color:#a89880;font-family:'Inter',system-ui,sans-serif;">{{ $ctaNote }}</p>
                @endif
            </td>
        </tr>
    </table>
@endif
