@php
    $variants = [
        'info' => ['bg' => '#EEF3FF', 'border' => '#90B4F0', 'text' => '#0C3C70'],
        'warn' => ['bg' => '#FFF8E4', 'border' => '#F0C040', 'text' => '#7A4D00'],
        'error' => ['bg' => '#FEF2F2', 'border' => '#EEAAAA', 'text' => '#7A1818'],
        'ok' => ['bg' => '#EAFAF2', 'border' => '#6ECFA0', 'text' => '#0A5C32'],
        'brand' => ['bg' => '#FBF7EE', 'border' => '#E8D5A8', 'text' => '#3B2800'],
    ];
    $v = $variants[$type ?? 'info'] ?? $variants['info'];
@endphp
<table role="presentation" width="100%" cellspacing="0" cellpadding="0" border="0" style="margin:16px 0;background-color:{{ $v['bg'] }};border:1.5px solid {{ $v['border'] }};border-radius:10px;">
    <tr>
        <td style="padding:14px 16px;">
            @if (!empty($heading))
                <div style="font:800 12px/1.3 'Nunito',sans-serif;color:{{ $v['text'] }};margin-bottom:4px;">{{ $heading }}</div>
            @endif
            @if (!empty($body))
                <div style="font:500 12px/1.55 'Nunito',sans-serif;color:#464646;">{!! $body !!}</div>
            @endif
        </td>
    </tr>
</table>
