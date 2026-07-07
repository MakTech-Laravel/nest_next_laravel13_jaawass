@if (!empty($items) && is_array($items))
    <table role="presentation" width="100%" cellspacing="0" cellpadding="0" border="0" style="margin:16px 0;">
        @foreach ($items as $item)
            <tr>
                <td width="28" style="vertical-align:top;padding:8px 0;">
                    <div style="width:20px;height:20px;border-radius:6px;border:1.5px solid #E8D5A8;background-color:#FBF7EE;font:800 11px/18px 'Nunito',sans-serif;text-align:center;color:#9A7A3A;">{{ $loop->iteration }}</div>
                </td>
                <td style="vertical-align:top;padding:8px 0 8px 10px;">
                    <div style="font:800 12px/1.3 'Nunito',sans-serif;color:#1C1C1C;">{{ $item['title'] ?? $item }}</div>
                    @if (!empty($item['body']))
                        <div style="font:500 12px/1.5 'Nunito',sans-serif;color:#666;margin-top:3px;">{{ $item['body'] }}</div>
                    @endif
                </td>
            </tr>
        @endforeach
    </table>
@endif
