@if (!empty($details) && is_array($details))
    <table role="presentation" width="100%" cellspacing="0" cellpadding="0" border="0" style="margin:0 0 24px 0;background-color:#ffffff;border:1px solid #d4c9b0;border-radius:8px;">
        <tr>
            <td style="padding:24px;">
                @if (!empty($detailsHeading))
                    <p style="margin:0 0 12px 0;font-size:9.5px;font-weight:600;letter-spacing:0.18em;text-transform:uppercase;color:#a89880;font-family:'Inter',system-ui,sans-serif;">{{ $detailsHeading }}</p>
                @endif
                @foreach ($details as $label => $value)
                    @if ($value !== null && $value !== '')
                        <p style="margin:0 0 8px 0;font-size:13px;line-height:1.5;color:#7a6e5a;font-family:'Inter',system-ui,sans-serif;">
                            <strong style="color:#2c2517;">{{ $label }}:</strong> {{ $value }}
                        </p>
                    @endif
                @endforeach
            </td>
        </tr>
    </table>
@endif
