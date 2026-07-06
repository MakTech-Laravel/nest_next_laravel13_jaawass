@extends('mail.layouts.sourcenest')

@section('title', $subjectLine ?? config('app.name'))
@section('preheader', $preheader ?? '')
@section('header_eyebrow', $headerEyebrow ?? __('mail.layout.default_eyebrow'))
@section('header_title', $headerTitle ?? '')
@section('header_subtitle', $headerSubtitle ?? '')

@section('content')
    @include('mail.partials.alert-banner', [
        'alertTag' => $alertTag ?? null,
        'alertHeading' => $alertHeading ?? null,
        'alertMeta' => $alertMeta ?? null,
    ])

    @if (!empty($greeting))
        <p style="margin:0 0 16px 0;font-size:15px;line-height:1.6;color:#2c2517;font-family:'Inter',system-ui,sans-serif;">{{ $greeting }}</p>
    @endif

    @if (!empty($intro))
        <p style="margin:0 0 16px 0;font-size:14px;line-height:1.7;color:#7a6e5a;font-family:'Inter',system-ui,sans-serif;">{{ $intro }}</p>
    @endif

    @include('mail.partials.content-card', [
        'heading' => $messageHeading ?? null,
        'body' => $messageBody ?? null,
    ])

    @include('mail.partials.details-table', [
        'detailsHeading' => $detailsHeading ?? null,
        'details' => $details ?? [],
    ])

    @if (!empty($extraBody))
        <p style="margin:0 0 16px 0;font-size:13px;line-height:1.6;color:#7a6e5a;font-family:'Inter',system-ui,sans-serif;">{{ $extraBody }}</p>
    @endif

    @include('mail.partials.cta-button', [
        'ctaUrl' => $ctaUrl ?? null,
        'ctaLabel' => $ctaLabel ?? null,
        'ctaNote' => $ctaNote ?? null,
    ])
@endsection
