@php
    $variants = [
        'amber' => ['bg' => '#FFF8E4', 'border' => '#F0C040', 'dot' => '#C07800', 'text' => '#7A4D00'],
        'red' => ['bg' => '#FEF2F2', 'border' => '#EEAAAA', 'dot' => '#C42828', 'text' => '#7A1818'],
        'green' => ['bg' => '#EAFAF2', 'border' => '#6ECFA0', 'dot' => '#0E8A4A', 'text' => '#0A5C32'],
        'gray' => ['bg' => '#F8F8F8', 'border' => '#E6E6E6', 'dot' => '#8A8A8A', 'text' => '#464646'],
    ];
    $v = $variants[$variant ?? 'amber'] ?? $variants['amber'];
@endphp
<table role="presentation" width="100%" cellspacing="0" cellpadding="0" border="0" style="margin:16px 0;background-color:{{ $v['bg'] }};border:1.5px solid {{ $v['border'] }};border-radius:10px;">
    <tr>
        <td style="padding:12px 16px;">
            <table role="presentation" width="100%" cellspacing="0" cellpadding="0" border="0">
                <tr>
                    <td>
                        <span style="display:inline-block;width:8px;height:8px;border-radius:50%;background-color:{{ $v['dot'] }};margin-right:8px;vertical-align:middle;"></span>
                        <span style="font:800 11px/1 'Nunito',sans-serif;color:{{ $v['text'] }};vertical-align:middle;">{{ $label }}</span>
                    </td>
                    @if (!empty($date))
                        <td align="right" style="font:600 10px/1 'Nunito',sans-serif;color:#8A8A8A;">{{ $date }}</td>
                    @endif
                </tr>
            </table>
        </td>
    </tr>
</table>
