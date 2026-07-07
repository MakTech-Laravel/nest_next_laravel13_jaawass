@extends('mail.layouts.sourcenest')

@section('title', __('mail.password_changed.subject'))
@section('preheader', __('mail.password_changed.preheader'))
@section('subject_preview', __('mail.password_changed.subject'))

@section('header')
    @include('mail.partials.sourcenest.header', ['variant' => 'c', 'contextLabel' => __('mail.demo.context.security')])
@endsection

@section('hero')
    @include('mail.partials.sourcenest.hero', [
        'variant' => 'h5',
        'icon' => '🔒',
        'headline' => __('mail.password_changed.hero_headline'),
    ])
@endsection

@section('content')
    <p style="margin:0 0 16px 0;font:500 13px/1.65 'Nunito',sans-serif;color:#464646;">{{ __('mail.password_changed.intro', ['name' => $name ?? 'there']) }}</p>

    @include('mail.partials.sourcenest.evlog-table', [
        'rows' => array_filter([
            __('mail.password_changed.event') => __('mail.password_changed.event_value'),
            __('mail.password_changed.account') => $accountEmail ?? null,
            __('mail.password_changed.changed_at') => $changedAt ?? null,
            __('mail.password_changed.device') => $device ?? null,
            __('mail.password_changed.location') => $location ?? null,
        ]),
    ])

    @include('mail.partials.sourcenest.alert', ['type' => 'warn', 'body' => __('mail.password_changed.warning')])
@endsection

@section('cta')
    @include('mail.partials.sourcenest.cta-block', [
        'ctaUrl' => $ctaUrl ?? \App\Support\Mail\MailNotificationHelper::frontendUrl('dashboard'),
        'ctaLabel' => __('mail.password_changed.cta'),
    ])
@endsection

@php $footerTag = __('mail.demo.footer_tags.security'); @endphp
