@if (!empty($rows) && is_array($rows))
    <table role="presentation" width="100%" cellspacing="0" cellpadding="0" border="0" style="margin:20px 0;border:1.5px solid #E6E6E6;border-radius:10px;overflow:hidden;">
        @foreach ($rows as $label => $value)
            @if ($value !== null && $value !== '')
                <tr>
                    <td style="padding:10px 16px;background-color:{{ $loop->even ? '#F8F8F8' : '#FFFFFF' }};border-bottom:1px solid #F0F0F0;font:700 10px/1.4 'Nunito',sans-serif;letter-spacing:0.5px;text-transform:uppercase;color:#8A8A8A;width:38%;">{{ $label }}</td>
                    <td style="padding:10px 16px;background-color:{{ $loop->even ? '#F8F8F8' : '#FFFFFF' }};border-bottom:1px solid #F0F0F0;font:700 12px/1.4 'Nunito',sans-serif;color:#1C1C1C;">{{ $value }}</td>
                </tr>
            @endif
        @endforeach
    </table>
@endif
