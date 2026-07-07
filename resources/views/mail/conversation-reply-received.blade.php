@extends('mail.layouts.sourcenest')

@section('title', __('mail.conversation_message_received.subject', ['senderName' => $senderName ?? '']))
@section('preheader', $preheader ?? __('mail.conversation_message_received.preheader'))
@section('subject_preview', __('mail.conversation_message_received.subject_preview', ['senderName' => $senderName ?? '']))

@section('header')
    @include('mail.partials.sourcenest.header', ['variant' => 'a', 'badgeLabel' => __('mail.demo.badges.buyer')])
@endsection

@section('hero')
    @include('mail.partials.sourcenest.hero', [
        'variant' => 'h5',
        'icon' => '💬',
        'headline' => __('mail.conversation_message_received.hero_headline'),
    ])
@endsection

@section('content')
    <p style="margin:0 0 14px 0;font:500 13px/1.65 'Nunito',sans-serif;color:#464646;">{{ $intro ?? __('mail.conversation_message_received.intro', ['name' => $recipientName ?? 'there', 'sender' => $senderName ?? '', 'senderName' => $senderName ?? '']) }}</p>

    @include('mail.partials.sourcenest.inquiry-card', [
        'initials' => $senderInitials ?? 'SN',
        'name' => $senderDisplayName ?? $senderName ?? '',
        'meta' => $senderMeta ?? '',
        'timestamp' => $messageTimestamp ?? null,
        'body' => $messagePreview ?? $messageBody ?? null,
    ])

    @include('mail.partials.sourcenest.alert', ['type' => 'ok', 'heading' => __('mail.conversation_message_received.alert_heading')])
@endsection

@section('cta')
    @include('mail.partials.sourcenest.cta-block', [
        'ctaUrl' => $ctaUrl,
        'ctaLabel' => $ctaLabel ?? __('mail.conversation_message_received.cta'),
    ])
@endsection

@php $footerTag = __('mail.demo.footer_tags.platform'); @endphp
