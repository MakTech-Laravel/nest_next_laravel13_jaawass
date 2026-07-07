@extends('mail.layouts.sourcenest')

@section('title', __('mail.rfq_submitted_buyer.subject'))
@section('preheader', __('mail.rfq_submitted_buyer.preheader'))
@section('subject_preview', __('mail.rfq_submitted_buyer.subject'))

@section('header')
    @include('mail.partials.sourcenest.header', ['variant' => 'a', 'badgeLabel' => __('mail.demo.badges.buyer')])
@endsection

@section('hero')
    @include('mail.partials.sourcenest.hero', [
        'variant' => 'h2',
        'headline' => __('mail.rfq_submitted_buyer.hero_headline'),
    ])
@endsection

@section('content')
    <p style="margin:0 0 16px 0;font:500 13px/1.65 'Nunito',sans-serif;color:#464646;">{{ __('mail.rfq_submitted_buyer.intro', ['name' => $buyerName ?? 'there', 'rfq' => $rfqNumber ?? '', 'product' => $productName ?? '']) }}</p>

    @include('mail.partials.sourcenest.alert', ['type' => 'ok', 'heading' => __('mail.rfq_submitted_buyer.banner_heading')])

    @include('mail.partials.sourcenest.steps', [
        'steps' => [
            ['title' => __('mail.rfq_submitted_buyer.step_1_title'), 'body' => __('mail.rfq_submitted_buyer.step_1_body')],
            ['title' => __('mail.rfq_submitted_buyer.step_2_title'), 'body' => __('mail.rfq_submitted_buyer.step_2_body')],
            ['title' => __('mail.rfq_submitted_buyer.step_3_title'), 'body' => __('mail.rfq_submitted_buyer.step_3_body')],
        ],
    ])
@endsection

@section('cta')
    @include('mail.partials.sourcenest.cta-block', [
        'ctaUrl' => $ctaUrl,
        'ctaLabel' => __('mail.rfq_submitted_buyer.cta'),
    ])
@endsection

@php $footerTag = __('mail.demo.footer_tags.platform'); @endphp
