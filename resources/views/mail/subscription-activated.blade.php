@extends('mail.layouts.sourcenest')

@section('title', __('mail.subscription_created.subject'))
@section('preheader', __('mail.subscription_created.preheader'))
@section('subject_preview', __('mail.subscription_created.subject'))

@section('header')
    @include('mail.partials.sourcenest.header', ['variant' => 'b', 'badgeLabel' => __('mail.demo.badges.manufacturer')])
@endsection

@section('hero')
    @include('mail.partials.sourcenest.hero', [
        'variant' => 'h1',
        'headline' => __('mail.subscription_created.hero_headline'),
    ])
@endsection

@section('content')
    <p style="margin:0 0 16px 0;font:500 13px/1.65 'Nunito',sans-serif;color:#464646;">{{ $intro ?? __('mail.subscription_created.intro', ['name' => $manufacturerName ?? 'there', 'plan' => $planName ?? '']) }}</p>

    @include('mail.partials.sourcenest.status-pill', [
        'variant' => 'green',
        'label' => __('mail.subscription_created.status_label'),
        'date' => $activatedAt ?? now()->format('F j, Y'),
    ])

    @include('mail.partials.sourcenest.evlog-table', [
        'rows' => array_filter([
            __('mail.subscription_created.billing_interval') => $billingInterval ?? null,
            __('mail.subscription_created.paid_amount') => $paidAmount ?? null,
            __('mail.subscription_created.starts_at') => $startsAt ?? null,
            __('mail.subscription_created.ends_at') => $endsAt ?? null,
        ]),
    ])

    @include('mail.partials.sourcenest.steps', [
        'steps' => [
            ['title' => __('mail.subscription_created.onboard_1'), 'done' => true],
            ['title' => __('mail.subscription_created.onboard_2')],
            ['title' => __('mail.subscription_created.onboard_3')],
        ],
    ])
@endsection

@section('cta')
    @include('mail.partials.sourcenest.cta-block', [
        'ctaUrl' => $ctaUrl ?? $plansUrl ?? \App\Support\Mail\MailNotificationHelper::frontendUrl('dashboard/manufacturer'),
        'ctaLabel' => $ctaLabel ?? __('mail.subscription_created.cta'),
    ])
@endsection

@php $footerTag = __('mail.demo.footer_tags.platform'); @endphp
