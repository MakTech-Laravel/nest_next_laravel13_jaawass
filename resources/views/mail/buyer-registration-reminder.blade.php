@extends('mail.layouts.sourcenest')

@section('title', __('mail.buyer_registration_reminder.subject'))
@section('preheader', __('mail.buyer_registration_reminder.preheader'))
@section('subject_preview', __('mail.buyer_registration_reminder.subject'))

@section('header')
    @include('mail.partials.sourcenest.header', ['variant' => 'a', 'badgeLabel' => __('mail.demo.badges.buyer')])
@endsection

@section('hero')
    @include('mail.partials.sourcenest.hero', [
        'variant' => 'h3',
        'headline' => __('mail.buyer_registration_reminder.hero_headline'),
    ])
@endsection

@section('content')
    <p style="margin:0 0 16px 0;font:500 13px/1.65 'Nunito',sans-serif;color:#464646;">{{ __('mail.buyer_registration_reminder.intro', ['name' => $name ?? 'there']) }}</p>

    @include('mail.partials.sourcenest.status-pill', [
        'variant' => 'gray',
        'label' => __('mail.buyer_registration_reminder.status_label'),
    ])

    @include('mail.partials.sourcenest.alert', ['type' => 'warn', 'body' => __('mail.buyer_registration_reminder.warn_body')])

    <table role="presentation" width="100%" cellspacing="0" cellpadding="0" border="0" style="margin:16px 0;">
        <tr>
            @foreach (__('mail.buyer_registration_reminder.features') as $feature)
                <td width="50%" style="padding:10px;vertical-align:top;">
                    <div style="background-color:#FBF7EE;border:1px solid #E8D5A8;border-radius:8px;padding:12px;font:600 11px/1.4 'Nunito',sans-serif;color:#3B2800;">{{ $feature }}</div>
                </td>
                @if ($loop->iteration % 2 === 0)
                    </tr><tr>
                @endif
            @endforeach
        </tr>
    </table>
@endsection

@section('cta')
    @include('mail.partials.sourcenest.cta-block', [
        'ctaUrl' => $ctaUrl ?? \App\Support\Mail\MailNotificationHelper::frontendUrl('register'),
        'ctaLabel' => __('mail.buyer_registration_reminder.cta'),
    ])
@endsection

@php $footerTag = __('mail.demo.footer_tags.platform'); @endphp
