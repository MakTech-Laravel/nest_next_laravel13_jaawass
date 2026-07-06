@if (!empty($alertTag) || !empty($alertHeading))
    <table role="presentation" width="100%" cellspacing="0" cellpadding="0" border="0" style="margin:0 0 24px 0;background-color:#fdf3e3;border:1px solid #e8c07a;border-left:4px solid #c47a2a;border-radius:8px;">
        <tr>
            <td style="padding:16px 24px;">
                <table role="presentation" cellspacing="0" cellpadding="0" border="0" width="100%">
                    <tr>
                        <td width="36" style="vertical-align:top;padding-right:16px;">
                            <table role="presentation" cellspacing="0" cellpadding="0" border="0">
                                <tr>
                                    <td align="center" style="width:36px;height:36px;border-radius:50%;background-color:#c47a2a;font-size:18px;font-weight:700;color:#ffffff;line-height:36px;">!</td>
                                </tr>
                            </table>
                        </td>
                        <td style="vertical-align:top;">
                            @if (!empty($alertTag))
                                <span style="display:inline-block;font-size:9px;font-weight:700;letter-spacing:0.18em;text-transform:uppercase;color:#c47a2a;background-color:rgba(196,122,42,0.12);border-radius:3px;padding:2px 7px;margin-bottom:4px;">
                                    {{ $alertTag }}
                                </span>
                            @endif
                            @if (!empty($alertHeading))
                                <p style="margin:0 0 3px 0;font-size:14px;font-weight:600;color:#2c2517;font-family:'Inter',system-ui,sans-serif;">{{ $alertHeading }}</p>
                            @endif
                            @if (!empty($alertMeta))
                                <p style="margin:0;font-size:12px;color:#7a6e5a;font-family:'Inter',system-ui,sans-serif;">{{ $alertMeta }}</p>
                            @endif
                        </td>
                    </tr>
                </table>
            </td>
        </tr>
    </table>
@endif
