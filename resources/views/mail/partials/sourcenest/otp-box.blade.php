<table role="presentation" width="100%" cellspacing="0" cellpadding="0" border="0" style="margin:20px 0;background-color:#FBF7EE;border:2px dashed #E8D5A8;border-radius:12px;">
    <tr>
        <td align="center" style="padding:24px 20px;">
            @if (!empty($label))
                <div style="font:800 8px/1 'Nunito',sans-serif;letter-spacing:1.8px;text-transform:uppercase;color:#9A7A3A;margin-bottom:12px;">{{ $label }}</div>
            @endif
            <div style="font:900 36px/1 'Nunito',sans-serif;letter-spacing:8px;color:#3B2800;">{{ $formattedOtp ?? chunk_split(trim($otp), 3, ' ') }}</div>
            @if (!empty($note))
                <div style="font:500 12px/1.5 'Nunito',sans-serif;color:#8A8A8A;margin-top:12px;">{{ $note }}</div>
            @endif
        </td>
    </tr>
</table>
