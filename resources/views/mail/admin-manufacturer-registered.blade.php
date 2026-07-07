@extends('mail.layouts.sourcenest')

@section('title', __('mail.manufacturer_registered_admin.subject', ['company' => $company ?? '']))
@section('preheader', $preheader ?? __('mail.manufacturer_registered_admin.preheader'))
@section('subject_preview', __('mail.manufacturer_registered_admin.subject_preview'))

@section('header')
    @include('mail.partials.sourcenest.header', ['variant' => 'd', 'badgeLabel' => __('mail.demo.badges.admin')])
@endsection

@section('hero')
    @include('mail.partials.sourcenest.hero', [
        'variant' => 'h6',
        'headline' => __('mail.manufacturer_registered_admin.hero_headline'),
    ])
@endsection

@section('content')
    <p style="margin:0 0 16px 0;font:500 13px/1.65 'Nunito',sans-serif;color:#464646;">{{ $intro ?? __('mail.manufacturer_registered_admin.intro', ['name' => $name ?? '', 'company' => $company ?? '', 'email' => $email ?? '']) }}</p>

    @include('mail.partials.sourcenest.evlog-table', ['rows' => $details ?? []])

    @if (!empty($messageBody))
        @include('mail.partials.sourcenest.alert', ['type' => 'info', 'heading' => $messageHeading ?? null, 'body' => $messageBody])
    @endif

    @include('mail.partials.sourcenest.chklist', [
        'items' => [
            ['title' => __('mail.manufacturer_registered_admin.check_1')],
            ['title' => __('mail.manufacturer_registered_admin.check_2')],
            ['title' => __('mail.manufacturer_registered_admin.check_3')],
        ],
    ])
@endsection

@section('cta')
    @include('mail.partials.sourcenest.cta-block', [
        'ctaUrl' => $ctaUrl,
        'ctaLabel' => $ctaLabel ?? __('mail.manufacturer_registered_admin.cta'),
        'ghostUrl' => $allRegistrationsUrl ?? \App\Support\Mail\MailNotificationHelper::frontendUrl('admin/manufacturer-registrations'),
        'ghostLabel' => __('mail.manufacturer_registered_admin.cta_secondary'),
    ])
@endsection

@php $footerTag = __('mail.demo.footer_tags.admin'); @endphp
