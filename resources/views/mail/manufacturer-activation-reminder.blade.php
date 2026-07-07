@extends('mail.layouts.sourcenest')

@section('title', __('mail.manufacturer_activation_reminder.subject'))
@section('preheader', __('mail.manufacturer_activation_reminder.preheader'))
@section('subject_preview', __('mail.manufacturer_activation_reminder.subject'))

@section('header')
    @include('mail.partials.sourcenest.header', ['variant' => 'b', 'badgeLabel' => __('mail.demo.badges.manufacturer')])
@endsection

@section('hero')
    @include('mail.partials.sourcenest.hero', [
        'variant' => 'h3',
        'headline' => __('mail.manufacturer_activation_reminder.hero_headline'),
    ])
@endsection

@section('content')
    <p style="margin:0 0 16px 0;font:500 13px/1.65 'Nunito',sans-serif;color:#464646;">{{ __('mail.manufacturer_activation_reminder.intro', ['name' => $name ?? 'there', 'company' => $company ?? '']) }}</p>

    @include('mail.partials.sourcenest.status-pill', [
        'variant' => 'amber',
        'label' => __('mail.manufacturer_activation_reminder.status_label'),
        'date' => $approvedAt ?? null,
    ])

    @include('mail.partials.sourcenest.alert', ['type' => 'warn', 'body' => __('mail.manufacturer_activation_reminder.urgency_body')])
@endsection

@section('cta')
    @include('mail.partials.sourcenest.cta-block', [
        'ctaUrl' => $ctaUrl ?? \App\Support\Mail\MailNotificationHelper::frontendUrl('subscription'),
        'ctaLabel' => __('mail.manufacturer_activation_reminder.cta'),
    ])
@endsection

@php $footerTag = __('mail.demo.footer_tags.platform'); @endphp
