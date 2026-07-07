@extends('mail.layouts.sourcenest')

@section('title', __('mail.email_verification.subject'))
@section('preheader', $preheader ?? __('mail.email_verification.intro'))
@section('subject_preview', __('mail.email_verification.subject'))

@section('header')
    @include('mail.partials.sourcenest.header', ['variant' => 'c', 'contextLabel' => __('mail.demo.context.account_setup')])
@endsection

@section('hero')
    @include('mail.partials.sourcenest.hero', [
        'variant' => 'h3',
        'headline' => __('mail.email_verification.hero_headline'),
    ])
@endsection

@section('content')
    <p style="margin:0 0 16px 0;font:500 13px/1.65 'Nunito',sans-serif;color:#464646;">{{ $intro ?? __('mail.email_verification.intro') }}</p>

    @include('mail.partials.sourcenest.otp-box', [
        'label' => $otpLabel ?? __('mail.layout.otp_code_label'),
        'otp' => $otp,
        'formattedOtp' => preg_replace('/(\d{3})(?=\d)/', '$1 ', (string) $otp),
        'note' => $expires ?? __('mail.email_verification.expires', ['time' => __('mail.email_verification.expires_fallback')]),
    ])

    @include('mail.partials.sourcenest.alert', [
        'type' => 'info',
        'body' => __('mail.email_verification.ignore_notice'),
    ])
@endsection

@section('cta')
    @include('mail.partials.sourcenest.cta-block', [
        'ctaUrl' => $ctaUrl ?? \App\Support\Mail\MailNotificationHelper::frontendUrl('verify'),
        'ctaLabel' => __('mail.email_verification.cta'),
    ])
@endsection

@php $footerTag = __('mail.demo.footer_tags.security'); @endphp
