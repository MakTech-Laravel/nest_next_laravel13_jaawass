<table role="presentation" width="100%" cellspacing="0" cellpadding="0" border="0" style="margin:0 0 24px 0;background-color:#ffffff;border:1px solid #d4c9b0;border-radius:8px;">
    <tr>
        <td style="padding:24px;">
            @if (!empty($heading))
                <p style="margin:0 0 8px 0;font-size:9.5px;font-weight:600;letter-spacing:0.18em;text-transform:uppercase;color:#a89880;font-family:'Inter',system-ui,sans-serif;">{{ $heading }}</p>
            @endif
            <div style="margin:0;font-size:14px;line-height:1.7;color:#7a6e5a;font-family:'Inter',system-ui,sans-serif;">
                {!! $body ?? '' !!}
            </div>
        </td>
    </tr>
</table>
