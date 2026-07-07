@php $variant = $variant ?? 'a'; @endphp

@if ($variant === 'a')
    <tr>
        <td style="padding:20px 30px;background-color:#FFFFFF;border-bottom:1.5px solid #F0F0F0;">
            <table role="presentation" width="100%" cellspacing="0" cellpadding="0" border="0">
                <tr>
                    <td>@include('mail.partials.sourcenest.logo')</td>
                    <td align="right">@if (!empty($badgeLabel))@include('mail.partials.sourcenest.badge', ['label' => $badgeLabel])@endif</td>
                </tr>
            </table>
        </td>
    </tr>
@elseif ($variant === 'b')
    <tr>
        <td style="padding:20px 30px;background-color:#3B2800;">
            <table role="presentation" width="100%" cellspacing="0" cellpadding="0" border="0">
                <tr>
                    <td>@include('mail.partials.sourcenest.logo', ['wordmarkColor' => '#FFFFFF', 'subtitleColor' => '#C8A96A', 'accentColor' => '#C8A96A'])</td>
                    <td align="right">@if (!empty($badgeLabel))@include('mail.partials.sourcenest.badge', ['label' => $badgeLabel, 'color' => 'rgba(200,169,106,0.7)', 'bg' => 'transparent', 'border' => 'rgba(200,169,106,0.25)'])@endif</td>
                </tr>
            </table>
        </td>
    </tr>
@elseif ($variant === 'c')
    <tr>
        <td align="center" style="padding:22px 30px 18px;background-color:#FBF7EE;border-bottom:1.5px solid #E8D5A8;">
            @include('mail.partials.sourcenest.logo')
            @if (!empty($contextLabel))
                <div style="font:800 8.5px/1 'Nunito',sans-serif;letter-spacing:1.8px;text-transform:uppercase;color:#9A7A3A;margin-top:10px;">{{ $contextLabel }}</div>
            @endif
        </td>
    </tr>
@elseif ($variant === 'd')
    <tr>
        <td style="padding:0;background-color:#FFFFFF;border-bottom:1.5px solid #F0F0F0;">
            <table role="presentation" width="100%" cellspacing="0" cellpadding="0" border="0">
                <tr>
                    <td width="4" style="background-color:#9A7A3A;">&nbsp;</td>
                    <td style="padding:20px 26px;">
                        <table role="presentation" width="100%" cellspacing="0" cellpadding="0" border="0">
                            <tr>
                                <td>@include('mail.partials.sourcenest.logo')</td>
                                <td align="right">@if (!empty($badgeLabel))@include('mail.partials.sourcenest.badge', ['label' => $badgeLabel])@endif</td>
                            </tr>
                        </table>
                    </td>
                </tr>
            </table>
        </td>
    </tr>
@endif
