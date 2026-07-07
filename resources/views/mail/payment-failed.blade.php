@extends('mail.layouts.sourcenest')

@section('title', __('mail.payment_failed.subject'))
@section('preheader', __('mail.payment_failed.preheader'))
@section('subject_preview', __('mail.payment_failed.subject'))

@section('header')
    @include('mail.partials.sourcenest.header', ['variant' => 'b', 'badgeLabel' => __('mail.demo.badges.payment_failed')])
@endsection

@section('hero')
    @include('mail.partials.sourcenest.hero', [
        'variant' => 'h3',
        'headline' => __('mail.payment_failed.hero_headline'),
    ])
@endsection

@section('content')
    <p style="margin:0 0 16px 0;font:500 13px/1.65 'Nunito',sans-serif;color:#464646;">{{ __('mail.payment_failed.intro', ['name' => $name ?? 'there', 'plan' => $planName ?? '']) }}</p>

    @include('mail.partials.sourcenest.status-pill', [
        'variant' => 'red',
        'label' => __('mail.payment_failed.status_label'),
        'date' => $failedAt ?? now()->format('F j, Y'),
    ])

    @include('mail.partials.sourcenest.alert', ['type' => 'error', 'body' => __('mail.payment_failed.risk_body')])

    @include('mail.partials.sourcenest.steps', [
        'steps' => collect(__('mail.payment_failed.reasons'))->map(fn ($body, $title) => ['title' => $title, 'body' => $body])->values()->all(),
    ])
@endsection

@section('cta')
    @include('mail.partials.sourcenest.cta-block', [
        'ctaUrl' => $ctaUrl ?? \App\Support\Mail\MailNotificationHelper::frontendUrl('settings/billing'),
        'ctaLabel' => __('mail.payment_failed.cta'),
        'ghostUrl' => 'mailto:billing@sourcenest.com',
        'ghostLabel' => __('mail.payment_failed.cta_secondary'),
    ])
@endsection

@php $footerTag = __('mail.demo.footer_tags.platform'); @endphp
