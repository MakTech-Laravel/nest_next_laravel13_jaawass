<table role="presentation" width="100%" cellspacing="0" cellpadding="0" border="0" style="margin-top:16px;border-top:1px solid #d4c9b0;padding-top:24px;">
    <tr>
        <td width="33%" style="vertical-align:bottom;padding-right:12px;">
            <table role="presentation" cellspacing="0" cellpadding="0" border="0">
                <tr>
                    <td style="width:130px;height:1px;background-color:#d4c9b0;font-size:0;line-height:0;padding-bottom:5px;">&nbsp;</td>
                </tr>
            </table>
            <p style="margin:0;font-family:'EB Garamond',Georgia,serif;font-style:italic;font-size:15px;color:#2c2517;">
                {{ $signatureName ?? __('mail.layout.signature_name') }}
            </p>
            <p style="margin:3px 0 0 0;font-size:9.5px;font-weight:600;letter-spacing:0.15em;text-transform:uppercase;color:#a89880;font-family:'Inter',system-ui,sans-serif;">
                {{ $signatureRole ?? __('mail.layout.signature_role') }}
            </p>
        </td>
        <td width="34%" align="center" style="vertical-align:bottom;padding:0 12px;">
            <table role="presentation" cellspacing="0" cellpadding="0" border="0" align="center">
                <tr>
                    <td align="center" style="width:60px;height:60px;border-radius:50%;background-color:#2c2517;border:2px solid #b89d5e;text-align:center;vertical-align:middle;">
                        <span style="display:block;font-size:16px;color:#b89d5e;line-height:1;">&#10003;</span>
                        <span style="display:block;font-size:6px;font-weight:700;letter-spacing:0.1em;text-transform:uppercase;color:#d4bc8a;line-height:1.2;margin-top:2px;">Source<br>Nest</span>
                    </td>
                </tr>
            </table>
        </td>
        <td width="33%" align="right" style="vertical-align:bottom;padding-left:12px;">
            @if (!empty($referenceId))
                <p style="margin:0 0 3px 0;font-size:9.5px;letter-spacing:0.12em;text-transform:uppercase;color:#a89880;font-family:'Inter',system-ui,sans-serif;">
                    {{ $referenceLabel ?? __('mail.layout.reference_label') }}
                </p>
                <p style="margin:0;font-size:13px;font-weight:600;color:#2c2517;font-family:'Inter',system-ui,sans-serif;">{{ $referenceId }}</p>
            @endif
            <p style="margin:2px 0 0 0;font-size:11px;color:#a89880;font-family:'Inter',system-ui,sans-serif;">{{ __('mail.layout.footer_site') }}</p>
        </td>
    </tr>
</table>
