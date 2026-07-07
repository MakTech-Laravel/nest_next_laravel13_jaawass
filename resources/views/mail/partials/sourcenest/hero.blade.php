@php
    $variant = $variant ?? 'h1';
    $headline = $headline ?? '';
    $subheadline = $subheadline ?? '';
@endphp

@if ($variant === 'h1')
    <tr>
        <td style="padding:34px 30px 40px;background-color:#FBF7EE;border-bottom:1.5px solid #E8D5A8;">
            <h1 style="margin:0;font:600 28px/1.15 'Lora',Georgia,serif;color:#3B2800;">{!! $headline !!}</h1>
            @if ($subheadline !== '')
                <p style="margin:10px 0 0 0;font:500 13px/1.6 'Nunito',sans-serif;color:#666;">{{ $subheadline }}</p>
            @endif
        </td>
    </tr>
@elseif ($variant === 'h2')
    <tr>
        <td style="padding:34px 30px 32px;background-color:#FFFFFF;border-bottom:2px solid #F0F0F0;">
            <h1 style="margin:0;font:600 28px/1.15 'Lora',Georgia,serif;color:#3B2800;">{!! $headline !!}</h1>
            @if ($subheadline !== '')
                <p style="margin:10px 0 0 0;font:500 13px/1.6 'Nunito',sans-serif;color:#666;">{{ $subheadline }}</p>
            @endif
        </td>
    </tr>
@elseif ($variant === 'h3')
    <tr>
        <td style="padding:26px 30px 24px;background-color:#F8F8F8;border-bottom:1.5px solid #E6E6E6;">
            <h1 style="margin:0;font:600 24px/1.2 'Lora',Georgia,serif;color:#3B2800;">{!! $headline !!}</h1>
            @if ($subheadline !== '')
                <p style="margin:8px 0 0 0;font:500 12px/1.6 'Nunito',sans-serif;color:#666;">{{ $subheadline }}</p>
            @endif
        </td>
    </tr>
@elseif ($variant === 'h4')
    <tr>
        <td align="center" style="padding:40px 30px 42px;background-color:#3B2800;">
            <h1 style="margin:0;font:600 26px/1.2 'Lora',Georgia,serif;color:#FFFFFF;">{!! $headline !!}</h1>
            @if ($subheadline !== '')
                <p style="margin:10px 0 0 0;font:500 12px/1.6 'Nunito',sans-serif;color:rgba(232,213,168,0.85);">{{ $subheadline }}</p>
            @endif
        </td>
    </tr>
@elseif ($variant === 'h5')
    <tr>
        <td style="padding:26px 30px;background:linear-gradient(135deg,#FBF7EE 0%,#FFFFFF 55%);border-bottom:1.5px solid #E8D5A8;">
            <table role="presentation" cellspacing="0" cellpadding="0" border="0">
                <tr>
                    <td width="58" style="vertical-align:middle;">
                        <div style="width:52px;height:52px;background-color:#FFFFFF;border:1.5px solid #E8D5A8;border-radius:14px;text-align:center;font-size:22px;line-height:52px;">{{ $icon ?? '✉' }}</div>
                    </td>
                    <td style="vertical-align:middle;padding-left:16px;">
                        <h1 style="margin:0;font:600 22px/1.2 'Lora',Georgia,serif;color:#3B2800;">{!! $headline !!}</h1>
                    </td>
                </tr>
            </table>
        </td>
    </tr>
@elseif ($variant === 'h6')
    <tr>
        <td style="padding:22px 30px;background-color:#F8F8F8;border-bottom:1.5px solid #E6E6E6;">
            <h1 style="margin:0;font:600 20px/1.25 'Lora',Georgia,serif;color:#1C1C1C;">{!! $headline !!}</h1>
            @if ($subheadline !== '')
                <p style="margin:6px 0 0 0;font:500 11px/1.5 'Nunito',sans-serif;color:#8A8A8A;">{{ $subheadline }}</p>
            @endif
        </td>
    </tr>
@endif
