@extends('mail.layouts.sourcenest')

@section('title', __('mail.welcome.subject'))
@section('preheader', __('mail.welcome.preheader'))
@section('header_eyebrow', __('mail.welcome.header_eyebrow'))
@section('header_title', __('mail.welcome.header_title'))
@section('header_subtitle', __('mail.welcome.header_subtitle'))

@section('content')
    <p style="margin:0 0 16px 0;font-size:15px;line-height:1.6;color:#2c2517;font-family:'Inter',system-ui,sans-serif;">
        {{ __('mail.welcome.greeting', ['name' => $firstName]) }}
    </p>
    <p style="margin:0 0 16px 0;font-size:14px;line-height:1.7;color:#7a6e5a;font-family:'Inter',system-ui,sans-serif;">
        {{ __('mail.welcome.intro') }}
    </p>
    <p style="margin:0 0 16px 0;font-size:14px;line-height:1.7;color:#7a6e5a;font-family:'Inter',system-ui,sans-serif;">
        {{ __('mail.welcome.body') }}
    </p>
    <p style="margin:0 0 24px 0;font-size:14px;line-height:1.7;color:#7a6e5a;font-family:'Inter',system-ui,sans-serif;">
        {{ __('mail.welcome.body_secondary') }}
    </p>

    <table role="presentation" width="100%" cellspacing="0" cellpadding="0" border="0" style="margin:0 0 24px 0;border:1px solid #d4c9b0;border-radius:8px;overflow:hidden;background:#ffffff;">
        <tr>
            <td width="33%" style="padding:20px 12px;text-align:center;vertical-align:top;border-right:1px solid #d4c9b0;">
                <p style="margin:0 0 6px 0;font-size:28px;font-weight:700;color:#2c2517;font-family:'Inter',system-ui,sans-serif;">{{ __('mail.welcome.stats_suppliers') }}</p>
                <p style="margin:0;font-size:12px;line-height:1.45;color:#7a6e5a;font-family:'Inter',system-ui,sans-serif;">{{ __('mail.welcome.stats_suppliers_label') }}</p>
            </td>
            <td width="33%" style="padding:20px 12px;text-align:center;vertical-align:top;border-right:1px solid #d4c9b0;">
                <p style="margin:0 0 6px 0;font-size:28px;font-weight:700;color:#2c2517;font-family:'Inter',system-ui,sans-serif;">{{ __('mail.welcome.stats_countries') }}</p>
                <p style="margin:0;font-size:12px;line-height:1.45;color:#7a6e5a;font-family:'Inter',system-ui,sans-serif;">{{ __('mail.welcome.stats_countries_label') }}</p>
            </td>
            <td width="33%" style="padding:20px 12px;text-align:center;vertical-align:top;">
                <p style="margin:0 0 6px 0;font-size:14px;font-weight:700;letter-spacing:0.12em;color:#2c2517;font-family:'Inter',system-ui,sans-serif;text-transform:uppercase;">{{ __('mail.welcome.stats_direct') }}</p>
                <p style="margin:0;font-size:12px;line-height:1.45;color:#7a6e5a;font-family:'Inter',system-ui,sans-serif;">{{ __('mail.welcome.stats_direct_label') }}</p>
            </td>
        </tr>
    </table>

    <p style="margin:0 0 8px 0;font-size:9.5px;font-weight:600;letter-spacing:0.18em;text-transform:uppercase;color:#a89880;font-family:'Inter',system-ui,sans-serif;">
        {{ __('mail.welcome.features_heading') }}
    </p>
    <p style="margin:0 0 16px 0;font-size:17px;font-weight:600;color:#2c2517;font-family:'Inter',system-ui,sans-serif;">
        {{ __('mail.welcome.features_subheading') }}
    </p>

    <table role="presentation" width="100%" cellspacing="0" cellpadding="0" border="0" style="margin:0 0 24px 0;">
        <tr>
            <td width="33%" style="padding:0 8px 16px 0;vertical-align:top;">
                <table role="presentation" width="100%" cellspacing="0" cellpadding="0" border="0" style="background-color:#ffffff;border:1px solid #d4c9b0;border-radius:8px;">
                    <tr>
                        <td style="padding:18px 14px;">
                            <p style="margin:0 0 8px 0;font-size:14px;font-weight:600;color:#2c2517;font-family:'Inter',system-ui,sans-serif;">{{ __('mail.welcome.feature_discover_title') }}</p>
                            <p style="margin:0;font-size:12px;line-height:1.55;color:#7a6e5a;font-family:'Inter',system-ui,sans-serif;">{{ __('mail.welcome.feature_discover_body') }}</p>
                        </td>
                    </tr>
                </table>
            </td>
            <td width="33%" style="padding:0 8px 16px 8px;vertical-align:top;">
                <table role="presentation" width="100%" cellspacing="0" cellpadding="0" border="0" style="background-color:#ffffff;border:1px solid #d4c9b0;border-radius:8px;">
                    <tr>
                        <td style="padding:18px 14px;">
                            <p style="margin:0 0 8px 0;font-size:14px;font-weight:600;color:#2c2517;font-family:'Inter',system-ui,sans-serif;">{{ __('mail.welcome.feature_compare_title') }}</p>
                            <p style="margin:0;font-size:12px;line-height:1.55;color:#7a6e5a;font-family:'Inter',system-ui,sans-serif;">{{ __('mail.welcome.feature_compare_body') }}</p>
                        </td>
                    </tr>
                </table>
            </td>
            <td width="33%" style="padding:0 0 16px 8px;vertical-align:top;">
                <table role="presentation" width="100%" cellspacing="0" cellpadding="0" border="0" style="background-color:#ffffff;border:1px solid #d4c9b0;border-radius:8px;">
                    <tr>
                        <td style="padding:18px 14px;">
                            <p style="margin:0 0 8px 0;font-size:14px;font-weight:600;color:#2c2517;font-family:'Inter',system-ui,sans-serif;">{{ __('mail.welcome.feature_connect_title') }}</p>
                            <p style="margin:0;font-size:12px;line-height:1.55;color:#7a6e5a;font-family:'Inter',system-ui,sans-serif;">{{ __('mail.welcome.feature_connect_body') }}</p>
                        </td>
                    </tr>
                </table>
            </td>
        </tr>
    </table>

    <p style="margin:0 0 8px 0;font-size:9.5px;font-weight:600;letter-spacing:0.18em;text-transform:uppercase;color:#a89880;font-family:'Inter',system-ui,sans-serif;">
        {{ __('mail.welcome.next_heading') }}
    </p>
    <p style="margin:0 0 8px 0;font-size:17px;font-weight:600;color:#2c2517;font-family:'Inter',system-ui,sans-serif;">
        {{ __('mail.welcome.next_title') }}
    </p>
    <p style="margin:0 0 18px 0;font-size:14px;line-height:1.65;color:#7a6e5a;font-family:'Inter',system-ui,sans-serif;">
        {{ __('mail.welcome.next_body') }}
    </p>

    <table role="presentation" width="100%" cellspacing="0" cellpadding="0" border="0" style="margin:0 0 24px 0;background-color:#fff8e8;border-left:4px solid #b89d5e;border-radius:0 8px 8px 0;">
        <tr>
            <td style="padding:14px 18px;">
                <p style="margin:0 0 6px 0;font-size:11px;font-weight:700;letter-spacing:0.2em;color:#8a6d2f;text-transform:uppercase;font-family:'Inter',system-ui,sans-serif;">
                    {{ __('mail.welcome.tip_label') }}
                </p>
                <p style="margin:0;font-size:13px;line-height:1.6;color:#5c4a24;font-family:'Inter',system-ui,sans-serif;">
                    {{ __('mail.welcome.tip_body') }}
                </p>
            </td>
        </tr>
    </table>

    @include('mail.partials.cta-button', [
        'ctaUrl' => url('/'),
        'ctaLabel' => __('mail.welcome.cta'),
    ])

    <p style="margin:16px 0 0 0;font-size:13px;line-height:1.6;color:#7a6e5a;font-family:'Inter',system-ui,sans-serif;">
        {{ __('mail.welcome.support') }}
    </p>
@endsection
