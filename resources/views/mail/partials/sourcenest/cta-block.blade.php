@if (!empty($ctaUrl) && !empty($ctaLabel))
    <table role="presentation" width="100%" cellspacing="0" cellpadding="0" border="0" style="margin-top:8px;">
        <tr>
            <td align="center">
                <a href="{{ $ctaUrl }}" style="display:inline-block;background-color:{{ $btnBg ?? '#3B2800' }};color:#FFFFFF;font:800 12px/1 'Nunito',sans-serif;letter-spacing:0.3px;text-transform:uppercase;text-decoration:none;padding:14px 28px;border-radius:8px;">{{ $ctaLabel }}</a>
            </td>
        </tr>
        @if (!empty($ctaNote))
            <tr>
                <td align="center" style="padding-top:10px;font:500 11px/1.5 'Nunito',sans-serif;color:#8A8A8A;">{{ $ctaNote }}</td>
            </tr>
        @endif
        @if (!empty($ghostUrl) && !empty($ghostLabel))
            <tr>
                <td align="center" style="padding-top:14px;">
                    <a href="{{ $ghostUrl }}" style="font:700 11px/1 'Nunito',sans-serif;color:#9A7A3A;text-decoration:underline;">{{ $ghostLabel }}</a>
                </td>
            </tr>
        @endif
    </table>
@endif
