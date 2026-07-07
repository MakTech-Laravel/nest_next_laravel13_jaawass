@if (!empty($steps) && is_array($steps))
    <table role="presentation" width="100%" cellspacing="0" cellpadding="0" border="0" style="margin:20px 0;">
        @foreach ($steps as $index => $step)
            <tr>
                <td width="36" style="vertical-align:top;padding-bottom:14px;">
                    <div style="width:28px;height:28px;border-radius:50%;background-color:{{ ($step['done'] ?? false) ? '#0E8A4A' : '#3B2800' }};color:#FFFFFF;font:800 12px/28px 'Nunito',sans-serif;text-align:center;">{{ $step['number'] ?? ($index + 1) }}</div>
                </td>
                <td style="vertical-align:top;padding-bottom:14px;">
                    <div style="font:800 12px/1.3 'Nunito',sans-serif;color:#1C1C1C;">{{ $step['title'] ?? '' }}</div>
                    @if (!empty($step['body']))
                        <div style="font:500 12px/1.5 'Nunito',sans-serif;color:#666;margin-top:4px;">{{ $step['body'] }}</div>
                    @endif
                </td>
            </tr>
        @endforeach
    </table>
@endif
