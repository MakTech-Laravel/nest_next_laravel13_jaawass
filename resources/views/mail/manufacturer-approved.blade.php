@extends('mail.layouts.sourcenest')

@section('title', __('mail.manufacturer_approved.subject'))
@section('preheader', __('mail.manufacturer_approved.preheader'))
@section('subject_preview', __('mail.manufacturer_approved.subject'))

@section('header')
    @include('mail.partials.sourcenest.header', ['variant' => 'b', 'badgeLabel' => __('mail.demo.badges.manufacturer')])
@endsection

@section('hero')
    @include('mail.partials.sourcenest.hero', [
        'variant' => 'h1',
        'headline' => __('mail.manufacturer_approved.hero_headline'),
    ])
@endsection

@section('content')
    <p style="margin:0 0 14px 0;font:500 13px/1.65 'Nunito',sans-serif;color:#464646;">{{ $intro ?? __('mail.manufacturer_approved.intro', ['name' => $name ?? 'there', 'company' => $company ?? '']) }}</p>

    @include('mail.partials.sourcenest.alert', [
        'type' => 'ok',
        'heading' => __('mail.manufacturer_approved.plan_heading'),
        'body' => $planDescription ?? __('mail.manufacturer_approved.plan_body'),
    ])

    @include('mail.partials.sourcenest.alert', [
        'type' => 'warn',
        'heading' => __('mail.manufacturer_approved.warn_heading'),
        'body' => __('mail.manufacturer_approved.warn_body'),
    ])
@endsection

@section('cta')
    @include('mail.partials.sourcenest.cta-block', [
        'ctaUrl' => $ctaUrl,
        'ctaLabel' => $ctaLabel ?? __('mail.manufacturer_approved.cta'),
    ])
@endsection

@php $footerTag = __('mail.demo.footer_tags.platform'); @endphp
