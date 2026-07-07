@extends('mail.layouts.sourcenest')

@section('title', __('mail.admin_new_inquiry.subject'))
@section('preheader', __('mail.admin_new_inquiry.preheader'))
@section('subject_preview', __('mail.admin_new_inquiry.subject'))

@section('header')
    @include('mail.partials.sourcenest.header', ['variant' => 'd', 'badgeLabel' => __('mail.demo.badges.admin')])
@endsection

@section('hero')
    @include('mail.partials.sourcenest.hero', [
        'variant' => 'h6',
        'headline' => __('mail.admin_new_inquiry.hero_headline'),
    ])
@endsection

@section('content')
    @include('mail.partials.sourcenest.inquiry-card', [
        'initials' => $initials ?? 'IN',
        'name' => $contactName ?? '',
        'meta' => $contactMeta ?? '',
        'body' => !empty($message) ? nl2br(e($message)) : null,
    ])

    @include('mail.partials.sourcenest.evlog-table', ['rows' => $details ?? []])
@endsection

@section('cta')
    @include('mail.partials.sourcenest.cta-block', [
        'ctaUrl' => $adminPanelUrl,
        'ctaLabel' => __('mail.admin_new_inquiry.cta'),
        'ghostUrl' => $buyerProfileUrl ?? null,
        'ghostLabel' => !empty($buyerProfileUrl) ? __('mail.admin_new_inquiry.cta_secondary') : null,
    ])
@endsection

@php $footerTag = __('mail.demo.footer_tags.admin'); @endphp
