<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <title>{{ __('mail.supplier_report_status_updated.subject') }}</title>
</head>
<body style="margin:0;padding:24px;font-family:system-ui,sans-serif;background:#f4f4f5;color:#111827;">
    <div style="max-width:600px;margin:0 auto;background:#fff;border:1px solid #e5e7eb;border-radius:12px;padding:28px;">
        <h1 style="margin:0 0 16px;font-size:22px;">{{ __('mail.supplier_report_status_updated.subject') }}</h1>
        <p>{{ __('mail.supplier_report_status_updated.greeting', ['name' => $buyerName]) }}</p>
        <p>{{ __('mail.supplier_report_status_updated.intro', ['supplier' => $supplierName, 'status' => $statusLabel]) }}</p>
        @if (!empty($adminMessage))
            <p><strong>{{ __('mail.supplier_report_status_updated.message_heading') }}:</strong></p>
            <p style="white-space:pre-wrap;">{{ $adminMessage }}</p>
        @endif
        <p style="margin-top:24px;"><a href="{{ $reportsUrl }}" style="color:#1d4ed8;">{{ __('mail.supplier_report_status_updated.cta') }}</a></p>
        <p style="font-size:12px;color:#6b7280;">{{ __('mail.supplier_report_status_updated.footer') }}</p>
    </div>
</body>
</html>
