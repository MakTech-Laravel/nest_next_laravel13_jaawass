@extends('mail.layouts.sourcenest')

@section('title', __('mail.rfq_created_manufacturer.subject', ['rfqNumber' => $rfqNumber ?? '', 'buyerName' => $buyerName ?? '']))
@section('preheader', $preheader ?? __('mail.rfq_created_manufacturer.preheader'))
@section('subject_preview', __('mail.rfq_created_manufacturer.subject_preview'))

@section('header')
    @include('mail.partials.sourcenest.header', ['variant' => 'b', 'badgeLabel' => __('mail.demo.badges.manufacturer')])
@endsection

@section('hero')
    @include('mail.partials.sourcenest.hero', [
        'variant' => 'h5',
        'icon' => '📩',
        'headline' => __('mail.rfq_created_manufacturer.hero_headline'),
    ])
@endsection

@section('content')
    <p style="margin:0 0 14px 0;font:500 13px/1.65 'Nunito',sans-serif;color:#464646;">{{ $intro ?? __('mail.rfq_created_manufacturer.intro', ['name' => $recipientName ?? 'there', 'buyer' => $buyerName ?? '', 'rfq' => $rfqNumber ?? '', 'product' => $productName ?? '']) }}</p>

    @include('mail.partials.sourcenest.inquiry-card', [
        'initials' => $buyerInitials ?? 'BY',
        'name' => $buyerDisplayName ?? $buyerName ?? '',
        'meta' => $buyerMeta ?? '',
        'timestamp' => $inquiryTimestamp ?? null,
        'body' => $messagePreview ?? null,
        'tags' => $inquiryTags ?? [],
    ])

    @include('mail.partials.sourcenest.evlog-table', ['rows' => $details ?? []])
@endsection

@section('cta')
    @include('mail.partials.sourcenest.cta-block', [
        'ctaUrl' => $ctaUrl,
        'ctaLabel' => $ctaLabel ?? __('mail.rfq_created_manufacturer.cta'),
    ])
@endsection

@php $footerTag = __('mail.demo.footer_tags.platform'); @endphp
