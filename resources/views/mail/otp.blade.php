@extends('mail.layouts.sourcenest')

@section('title', $subjectLine ?? __('mail.layout.otp_title'))
@section('preheader', $preheader ?? '')
@section('header_eyebrow', $headerEyebrow ?? __('mail.layout.otp_eyebrow'))
@section('header_title', $headerTitle ?? __('mail.layout.otp_title'))
@section('header_subtitle', $headerSubtitle ?? '')

@section('content')
    @if (!empty($intro))
        <p style="margin:0 0 16px 0;font-size:14px;line-height:1.7;color:#7a6e5a;font-family:'Inter',system-ui,sans-serif;">{{ $intro }}</p>
    @endif

    <table role="presentation" width="100%" cellspacing="0" cellpadding="0" border="0" style="margin:0 0 24px 0;background-color:#ffffff;border:1px solid #d4c9b0;border-radius:8px;">
        <tr>
            <td align="center" style="padding:32px 24px;">
                <p style="margin:0 0 8px 0;font-size:9.5px;font-weight:600;letter-spacing:0.18em;text-transform:uppercase;color:#a89880;font-family:'Inter',system-ui,sans-serif;">{{ $otpLabel ?? __('mail.layout.otp_code_label') }}</p>
                <p style="margin:0;font-size:36px;font-weight:700;letter-spacing:0.35em;color:#2c2517;font-family:'Inter',system-ui,sans-serif;">{{ $otp }}</p>
            </td>
        </tr>
    </table>

    @if (!empty($expires))
        <p style="margin:0;font-size:12px;color:#a89880;font-family:'Inter',system-ui,sans-serif;">{{ $expires }}</p>
    @endif
@endsection
