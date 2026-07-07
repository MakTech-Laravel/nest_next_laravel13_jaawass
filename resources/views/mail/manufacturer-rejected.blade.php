@extends('mail.layouts.sourcenest')

@section('title', __('mail.manufacturer_rejected.subject'))
@section('preheader', __('mail.manufacturer_rejected.preheader'))
@section('subject_preview', __('mail.manufacturer_rejected.subject'))

@section('header')
    @include('mail.partials.sourcenest.header', ['variant' => 'b', 'badgeLabel' => __('mail.demo.badges.manufacturer')])
@endsection

@section('hero')
    @include('mail.partials.sourcenest.hero', [
        'variant' => 'h3',
        'headline' => __('mail.manufacturer_rejected.hero_headline'),
    ])
@endsection

@section('content')
    <p style="margin:0 0 16px 0;font:500 13px/1.65 'Nunito',sans-serif;color:#464646;">{{ $intro ?? __('mail.manufacturer_rejected.intro', ['name' => $name ?? 'there', 'company' => $company ?? '']) }}</p>

    @include('mail.partials.sourcenest.status-pill', [
        'variant' => 'red',
        'label' => __('mail.manufacturer_rejected.status_label'),
        'date' => $decisionDate ?? now()->format('F j, Y'),
    ])

    @if (!empty($reason))
        @include('mail.partials.sourcenest.alert', ['type' => 'error', 'heading' => __('mail.manufacturer_rejected.message_heading'), 'body' => nl2br(e($reason))])
    @endif

    @include('mail.partials.sourcenest.steps', [
        'steps' => collect(__('mail.manufacturer_rejected.reasons'))->map(fn ($body, $title) => ['title' => $title, 'body' => $body])->values()->all(),
    ])
@endsection

@section('cta')
    @include('mail.partials.sourcenest.cta-block', [
        'ctaUrl' => $ctaUrl,
        'ctaLabel' => $ctaLabel ?? __('mail.manufacturer_rejected.cta'),
    ])
@endsection

@php $footerTag = __('mail.demo.footer_tags.platform'); @endphp
