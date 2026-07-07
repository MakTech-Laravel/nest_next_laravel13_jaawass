<tr>
    <td style="padding:34px 30px 40px;background-color:#FBF7EE;border-bottom:1.5px solid #E8D5A8;position:relative;overflow:hidden;">
        <div style="position:absolute;right:-24px;top:-24px;width:210px;height:210px;opacity:0.06;pointer-events:none;z-index:0;">
            <svg width="210" height="210" viewBox="0 0 200 200" fill="none" style="display:block;">
                <circle cx="100" cy="100" r="92" stroke="#3B2800" stroke-width="1.5"/>
                <ellipse cx="100" cy="100" rx="46" ry="92" stroke="#3B2800" stroke-width="1.2"/>
                <line x1="8" y1="100" x2="192" y2="100" stroke="#3B2800" stroke-width="1"/>
                <line x1="100" y1="8" x2="100" y2="192" stroke="#3B2800" stroke-width="1"/>
                <line x1="44" y1="14" x2="44" y2="186" stroke="#3B2800" stroke-width="0.8"/>
                <line x1="156" y1="14" x2="156" y2="186" stroke="#3B2800" stroke-width="0.8"/>
                <path d="M8 65Q100 54 192 65" stroke="#3B2800" stroke-width="0.8" fill="none"/>
                <path d="M8 135Q100 146 192 135" stroke="#3B2800" stroke-width="0.8" fill="none"/>
            </svg>
        </div>

        <table role="presentation" width="100%" cellspacing="0" cellpadding="0" border="0" style="position:relative;z-index:1;">
            <tr>
                <td>
                    {{-- pill-row --}}
                    <table role="presentation" cellspacing="0" cellpadding="0" border="0" style="margin:0 0 14px 0;">
                        <tr>
                            <td style="padding:4px 11px;border-radius:20px;border:1.5px solid #A8C0F0;background-color:#EDF2FF;">
                                <table role="presentation" cellspacing="0" cellpadding="0" border="0">
                                    <tr>
                                        <td width="5" style="vertical-align:middle;line-height:0;">
                                            <span style="display:block;width:5px;height:5px;border-radius:50%;background-color:#1258B8;"></span>
                                        </td>
                                        <td style="padding-left:5px;vertical-align:middle;">
                                            <span style="font:800 8.5px/1 'Nunito',sans-serif;letter-spacing:1.2px;text-transform:uppercase;color:#666666;">{{ __('mail.welcome.badge') }}</span>
                                        </td>
                                    </tr>
                                </table>
                            </td>
                        </tr>
                    </table>

                    {{-- eyebrow --}}
                    <table role="presentation" cellspacing="0" cellpadding="0" border="0" style="margin:0 0 13px 0;">
                        <tr>
                            <td width="20" style="vertical-align:middle;line-height:0;">
                                <span style="display:block;width:20px;height:2px;border-radius:1px;background-color:#E8D5A8;"></span>
                            </td>
                            <td style="padding-left:8px;vertical-align:middle;">
                                <span style="font:800 8.5px/1 'Nunito',sans-serif;letter-spacing:2px;text-transform:uppercase;color:#9A7A3A;">{{ __('mail.welcome.eyebrow') }}</span>
                            </td>
                        </tr>
                    </table>

                    {{-- ht headline --}}
                    <h1 style="margin:0;padding:0;font:500 31px/1.17 'Lora',Georgia,serif;color:#3B2800;letter-spacing:-0.2px;">
                        {{ __('mail.welcome.hero_headline_line1') }}<br>
                        <em style="font-style:italic;font-weight:500;color:#9A7A3A;">{{ __('mail.welcome.hero_headline_line2') }}</em>
                    </h1>

                    {{-- hs subheadline --}}
                    <p style="margin:12px 0 0 0;padding:0;font:400 13.5px/1.78 'Nunito',sans-serif;color:#666666;">{{ __('mail.welcome.hero_subheadline') }}</p>
                </td>
            </tr>
        </table>
    </td>
</tr>
