<table role="presentation" width="100%" cellspacing="0" cellpadding="0" border="0" style="margin:20px 0;border:1.5px solid #E6E6E6;border-radius:12px;overflow:hidden;">
    <tr>
        <td style="padding:16px 18px;background-color:#F8F8F8;border-bottom:1px solid #E6E6E6;">
            <table role="presentation" width="100%" cellspacing="0" cellpadding="0" border="0">
                <tr>
                    <td width="44" style="vertical-align:top;">
                        <div style="width:40px;height:40px;border-radius:10px;background-color:#3B2800;color:#FFFFFF;font:800 13px/40px 'Nunito',sans-serif;text-align:center;">{{ $initials ?? 'SN' }}</div>
                    </td>
                    <td style="vertical-align:top;padding-left:12px;">
                        <div style="font:800 13px/1.3 'Nunito',sans-serif;color:#1C1C1C;">{{ $name }}</div>
                        @if (!empty($meta))
                            <div style="font:600 11px/1.4 'Nunito',sans-serif;color:#8A8A8A;margin-top:3px;">{{ $meta }}</div>
                        @endif
                    </td>
                    @if (!empty($timestamp))
                        <td align="right" style="vertical-align:top;font:600 10px/1.3 'Nunito',sans-serif;color:#B4B4B4;white-space:nowrap;">{{ $timestamp }}</td>
                    @endif
                </tr>
            </table>
        </td>
    </tr>
    @if (!empty($body))
        <tr>
            <td style="padding:16px 18px;">
                <div style="font:500 13px/1.65 'Nunito',sans-serif;color:#464646;">{!! $body !!}</div>
            </td>
        </tr>
    @endif
    @if (!empty($tags) && is_array($tags))
        <tr>
            <td style="padding:0 18px 16px 18px;">
                @foreach ($tags as $tag)
                    <span style="display:inline-block;font:600 10px/1 'Nunito',sans-serif;color:#666;background-color:#F8F8F8;border:1px solid #E6E6E6;border-radius:6px;padding:4px 8px;margin:0 6px 6px 0;">{{ $tag['label'] ?? '' }} <strong style="color:#1C1C1C;">{{ $tag['value'] ?? '' }}</strong></span>
                @endforeach
            </td>
        </tr>
    @endif
</table>
