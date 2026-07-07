@extends('mail.layouts.sourcenest')

@section('title', __('mail.manufacturer_additional_information.subject'))
@section('preheader', __('mail.manufacturer_additional_information.preheader'))
@section('subject_preview', __('mail.manufacturer_additional_information.subject'))

@section('header')
    @include('mail.partials.sourcenest.header', ['variant' => 'd', 'badgeLabel' => __('mail.demo.badges.manufacturer')])
@endsection

@section('hero')
    @include('mail.partials.sourcenest.hero', [
        'variant' => 'h3',
        'headline' => __('mail.manufacturer_additional_information.hero_headline'),
    ])
@endsection

@section('content')
    <p style="margin:0 0 14px 0;font:600 14px/1.5 'Nunito',sans-serif;color:#1C1C1C;">{{ __('mail.manufacturer_additional_information.greeting', ['name' => $companyName ?? '']) }}</p>
    <p style="margin:0 0 16px 0;font:500 13px/1.65 'Nunito',sans-serif;color:#464646;">{{ __('mail.manufacturer_additional_information.intro', ['company' => $companyName ?? '']) }}</p>

    @include('mail.partials.sourcenest.alert', [
        'type' => 'warn',
        'heading' => __('mail.manufacturer_additional_information.alert_heading'),
        'body' => __('mail.manufacturer_additional_information.alert_meta', [
            'date' => $requestedAt ?? now()->format('F j, Y'),
            'reference' => $referenceId ?? '',
        ]),
    ])

    @if (!empty($adminMessage))
        @include('mail.partials.sourcenest.inquiry-card', [
            'initials' => 'SN',
            'name' => __('mail.manufacturer_additional_information.admin_message_heading'),
            'body' => nl2br(e($adminMessage)),
        ])
    @endif

    @if (!empty($allowedTypes))
        @include('mail.partials.sourcenest.chklist', [
            'items' => collect($allowedTypes)->map(fn ($type) => ['title' => $type])->all(),
        ])
    @endif
@endsection

@section('cta')
    @include('mail.partials.sourcenest.cta-block', [
        'ctaUrl' => $submissionUrl,
        'ctaLabel' => __('mail.manufacturer_additional_information.cta'),
        'ctaNote' => __('mail.manufacturer_additional_information.expires', ['date' => $expiresAt ?? '']),
    ])
@endsection

@php $footerTag = __('mail.demo.footer_tags.platform'); @endphp
