@extends('mail.layouts.sourcenest')

@section('title', __('mail.manufacturer_registration_reminder.subject'))
@section('preheader', __('mail.manufacturer_registration_reminder.preheader'))
@section('subject_preview', __('mail.manufacturer_registration_reminder.subject'))

@section('header')
    @include('mail.partials.sourcenest.header', ['variant' => 'b', 'badgeLabel' => __('mail.demo.badges.manufacturer')])
@endsection

@section('hero')
    @include('mail.partials.sourcenest.hero', [
        'variant' => 'h1',
        'headline' => __('mail.manufacturer_registration_reminder.hero_headline'),
    ])
@endsection

@section('content')
    <p style="margin:0 0 16px 0;font:500 13px/1.65 'Nunito',sans-serif;color:#464646;">{{ __('mail.manufacturer_registration_reminder.intro', ['name' => $name ?? 'there']) }}</p>

    @include('mail.partials.sourcenest.chklist', [
        'items' => [
            ['title' => __('mail.manufacturer_registration_reminder.check_1')],
            ['title' => __('mail.manufacturer_registration_reminder.check_2')],
            ['title' => __('mail.manufacturer_registration_reminder.check_3')],
        ],
    ])
@endsection

@section('cta')
    @include('mail.partials.sourcenest.cta-block', [
        'ctaUrl' => $ctaUrl ?? \App\Support\Mail\MailNotificationHelper::frontendUrl('register'),
        'ctaLabel' => __('mail.manufacturer_registration_reminder.cta'),
    ])
@endsection

@php $footerTag = __('mail.demo.footer_tags.platform'); @endphp
