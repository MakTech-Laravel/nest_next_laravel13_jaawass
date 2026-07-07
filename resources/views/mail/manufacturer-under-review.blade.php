@extends('mail.layouts.sourcenest')

@section('title', __('mail.manufacturer_under_review.subject'))
@section('preheader', __('mail.manufacturer_under_review.preheader'))
@section('subject_preview', __('mail.manufacturer_under_review.subject'))

@section('header')
    @include('mail.partials.sourcenest.header', ['variant' => 'b', 'badgeLabel' => __('mail.demo.badges.manufacturer')])
@endsection

@section('hero')
    @include('mail.partials.sourcenest.hero', [
        'variant' => 'h1',
        'headline' => __('mail.manufacturer_under_review.hero_headline'),
    ])
@endsection

@section('content')
    <p style="margin:0 0 14px 0;font:600 14px/1.5 'Nunito',sans-serif;color:#1C1C1C;">{{ __('mail.manufacturer_under_review.greeting', ['name' => $name ?? 'there']) }}</p>
    <p style="margin:0 0 16px 0;font:500 13px/1.65 'Nunito',sans-serif;color:#464646;">{!! __('mail.manufacturer_under_review.intro', ['company' => $company ?? config('app.name')]) !!}</p>

    @include('mail.partials.sourcenest.status-pill', [
        'variant' => 'amber',
        'label' => __('mail.manufacturer_under_review.status_label'),
        'date' => $submittedAt ?? now()->format('F j, Y'),
    ])

    @include('mail.partials.sourcenest.steps', [
        'steps' => [
            ['number' => '1', 'title' => __('mail.manufacturer_under_review.timeline_1_title'), 'body' => __('mail.manufacturer_under_review.timeline_1_body'), 'done' => true],
            ['number' => '2', 'title' => __('mail.manufacturer_under_review.timeline_2_title'), 'body' => __('mail.manufacturer_under_review.timeline_2_body')],
            ['number' => '3', 'title' => __('mail.manufacturer_under_review.timeline_3_title'), 'body' => __('mail.manufacturer_under_review.timeline_3_body')],
        ],
    ])
@endsection

@section('cta')
    @include('mail.partials.sourcenest.cta-block', [
        'ctaUrl' => $ctaUrl ?? \App\Support\Mail\MailNotificationHelper::frontendUrl('dashboard/manufacturer'),
        'ctaLabel' => __('mail.manufacturer_under_review.cta'),
    ])
@endsection

@php $footerTag = __('mail.demo.footer_tags.platform'); @endphp
