<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <title>{{ __('mail.supplier_report_received.subject') }}</title>
</head>
<body style="margin:0;padding:24px;font-family:system-ui,sans-serif;background:#f4f4f5;color:#111827;">
    <div style="max-width:600px;margin:0 auto;background:#fff;border:1px solid #e5e7eb;border-radius:12px;padding:28px;">
        <h1 style="margin:0 0 16px;font-size:22px;">{{ __('mail.supplier_report_received.subject') }}</h1>
        <p>{{ __('mail.supplier_report_received.greeting', ['name' => $buyerName]) }}</p>
        <p>{{ __('mail.supplier_report_received.intro', ['supplier' => $supplierName, 'id' => $reportId]) }}</p>
        <p><strong>{{ __('mail.supplier_report_received.reason_heading') }}:</strong> {{ $reasonLabel }}</p>
        @if (!empty($details))
            <p><strong>{{ __('mail.supplier_report_received.details_heading') }}:</strong></p>
            <p style="white-space:pre-wrap;">{{ $details }}</p>
        @endif
        <p style="margin-top:24px;"><a href="{{ $reportsUrl }}" style="color:#1d4ed8;">{{ __('mail.supplier_report_received.cta') }}</a></p>
        <p style="font-size:12px;color:#6b7280;">{{ __('mail.supplier_report_received.footer') }}</p>
    </div>
</body>
</html>
