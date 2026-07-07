@php
    $logoUrl = $logoUrl ?? public_url($logoPath ?? 'images/mail/sourcenest-logo.png');
@endphp

@if (!empty($logoUrl))
    <img src="{{ $logoUrl }}" alt="SourceNest" width="140" style="display:block;height:auto;max-height:36px;width:auto;border:0;outline:none;text-decoration:none;">
@else
    <table role="presentation" cellspacing="0" cellpadding="0" border="0">
        <tr>
            <td style="vertical-align:middle;padding-right:10px;">
                <table role="presentation" cellspacing="0" cellpadding="0" border="0">
                    <tr>
                        <td align="center" style="width:28px;height:28px;border:1.5px solid {{ $accentColor ?? '#9A7A3A' }};border-radius:50%;">
                            <span style="display:inline-block;width:10px;height:10px;border-radius:50%;background-color:{{ $accentColor ?? '#9A7A3A' }};"></span>
                        </td>
                    </tr>
                </table>
            </td>
            <td style="vertical-align:middle;">
                <div style="font:900 21px/1 'Nunito',sans-serif;color:{{ $wordmarkColor ?? '#3B2800' }};letter-spacing:-0.6px;">sourcenest</div>
                @if (!empty($subtitle))
                    <div style="font:700 8px/1 'Nunito',sans-serif;letter-spacing:0.9px;text-transform:uppercase;color:{{ $subtitleColor ?? '#9A7A3A' }};margin-top:2px;">{{ $subtitle }}</div>
                @endif
            </td>
        </tr>
    </table>
@endif
