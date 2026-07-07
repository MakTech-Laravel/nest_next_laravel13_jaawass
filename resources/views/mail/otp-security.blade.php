@extends('mail.layouts.sourcenest')

@php
    $variant = $variant ?? 'password-reset';
    $prefix = $variant === 'account-restore' ? 'mail.account_restore_otp' : 'mail.password_reset_otp';
@endphp

@section('title', __($prefix.'.subject'))
@section('preheader', $preheader ?? __($prefix.'.intro'))
@section('subject_preview', __($prefix.'.subject'))

@section('header')
    @include('mail.partials.sourcenest.header', ['variant' => 'c', 'contextLabel' => __('mail.demo.context.security')])
@endsection

@section('hero')
    @include('mail.partials.sourcenest.hero', [
        'variant' => 'h4',
        'headline' => __($prefix.'.hero_headline'),
    ])
@endsection

@section('content')
    <p style="margin:0 0 16px 0;font:500 13px/1.65 'Nunito',sans-serif;color:#464646;">{{ $intro ?? __($prefix.'.intro') }}</p>

    @include('mail.partials.sourcenest.otp-box', [
        'label' => $otpLabel ?? __('mail.layout.otp_code_label'),
        'otp' => $otp,
        'formattedOtp' => preg_replace('/(\d{3})(?=\d)/', '$1 ', (string) $otp),
        'note' => $expires ?? __($prefix.'.expires', ['minutes' => config('auth.passwords.users.expire', 10)]),
    ])

    @include('mail.partials.sourcenest.alert', [
        'type' => 'warn',
        'body' => __($prefix.'.ignore_notice'),
    ])
@endsection

@section('cta')
    @include('mail.partials.sourcenest.cta-block', [
        'ctaUrl' => $ctaUrl ?? \App\Support\Mail\MailNotificationHelper::frontendUrl($variant === 'account-restore' ? 'recover-account' : 'reset-password'),
        'ctaLabel' => __($prefix.'.cta'),
    ])
@endsection

@php $footerTag = __('mail.demo.footer_tags.security'); @endphp
