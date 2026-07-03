@extends('mail.layouts.sourcenest')

@section('title', __('mail.admin_test_email.subject'))
@section('preheader', __('mail.admin_test_email.preheader'))
@section('header_eyebrow', __('mail.layout.default_eyebrow'))
@section('header_title', __('mail.admin_test_email.header_title'))
@section('header_subtitle', __('mail.admin_test_email.header_subtitle'))

@section('content')
    <p style="margin:0 0 16px 0;font-size:15px;line-height:1.6;color:#2c2517;font-family:'Inter',system-ui,sans-serif;">
        {{ __('mail.admin_test_email.greeting', ['name' => $recipient_name ?? 'Admin']) }}
    </p>
    <p style="margin:0 0 16px 0;font-size:14px;line-height:1.7;color:#7a6e5a;font-family:'Inter',system-ui,sans-serif;">
        {{ __('mail.admin_test_email.intro', ['platform' => $platform_name ?? config('app.name')]) }}
    </p>
    <p style="margin:0;font-size:14px;line-height:1.7;color:#7a6e5a;font-family:'Inter',system-ui,sans-serif;">
        {{ __('mail.admin_test_email.body', [
            'from_name' => $from_name ?? config('mail.from.name'),
            'from_email' => $from_email ?? config('mail.from.address'),
        ]) }}
    </p>
@endsection
