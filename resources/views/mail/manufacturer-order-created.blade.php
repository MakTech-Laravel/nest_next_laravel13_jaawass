<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>{{ __('mail.manufacturer_order_created.subject', ['orderNumber' => $orderNumber]) }}</title>
</head>

<body style="margin:0;padding:0;background-color:#f4f4f5;font-family:system-ui,-apple-system,BlinkMacSystemFont,'Segoe UI',sans-serif;">
    <span style="display:none !important;visibility:hidden;font-size:1px;color:#f4f4f5;line-height:1px;max-height:0;max-width:0;opacity:0;overflow:hidden;">
        {{ __('mail.manufacturer_order_created.preheader', ['manufacturerName' => $manufacturerName]) }}
    </span>
    <table role="presentation" width="100%" cellspacing="0" cellpadding="0" border="0" style="background-color:#f4f4f5;">
        <tr>
            <td align="center" style="padding:32px 16px;">
                <table role="presentation" width="600" cellspacing="0" cellpadding="0" border="0" style="max-width:600px;width:100%;background-color:#ffffff;border:1px solid #e5e7eb;border-radius:12px;overflow:hidden;">
                    <tr>
                        <td style="padding:28px 28px 12px 28px;background-color:#111827;">
                            <p style="margin:0 0 8px 0;font-size:11px;font-weight:700;letter-spacing:0.25em;color:#94a3b8;text-transform:uppercase;">SourceNest</p>
                            <h1 style="margin:0;font-size:24px;line-height:1.3;font-weight:600;color:#f8fafc;">
                                {{ __('mail.manufacturer_order_created.heading') }}
                            </h1>
                        </td>
                    </tr>
                    <tr>
                        <td style="padding:28px;">
                            <p style="margin:0 0 16px 0;font-size:15px;line-height:1.6;color:#111827;">
                                {{ __('mail.manufacturer_order_created.greeting', ['name' => $buyerName]) }}
                            </p>
                            <p style="margin:0 0 24px 0;font-size:15px;line-height:1.6;color:#374151;">
                                {{ __('mail.manufacturer_order_created.intro', ['manufacturerName' => $manufacturerName, 'orderNumber' => $orderNumber]) }}
                            </p>

                            <table role="presentation" width="100%" cellspacing="0" cellpadding="0" border="0" style="background-color:#f9fafb;border:1px solid #e5e7eb;border-radius:8px;margin:0 0 24px 0;">
                                <tr>
                                    <td style="padding:18px 20px;">
                                        <p style="margin:0 0 12px 0;font-size:12px;font-weight:700;letter-spacing:0.08em;color:#6b7280;text-transform:uppercase;">
                                            {{ __('mail.manufacturer_order_created.order_heading') }}
                                        </p>
                                        <p style="margin:0 0 8px 0;font-size:14px;color:#374151;"><strong>{{ __('mail.manufacturer_order_created.order_title') }}:</strong> {{ $orderTitle }}</p>
                                        <p style="margin:0 0 8px 0;font-size:14px;color:#374151;"><strong>{{ __('mail.manufacturer_order_created.estimated_delivery') }}:</strong> {{ $estimatedDeliveryAt }}</p>
                                        @if ($productionLead)
                                            <p style="margin:0 0 8px 0;font-size:14px;color:#374151;"><strong>{{ __('mail.manufacturer_order_created.production_lead') }}:</strong> {{ $productionLead }}</p>
                                        @endif
                                        @if ($paymentTerms)
                                            <p style="margin:0 0 8px 0;font-size:14px;color:#374151;"><strong>{{ __('mail.manufacturer_order_created.payment_terms') }}:</strong> {{ $paymentTerms }}</p>
                                        @endif
                                        @if ($shippingTerms)
                                            <p style="margin:0 0 8px 0;font-size:14px;color:#374151;"><strong>{{ __('mail.manufacturer_order_created.shipping_terms') }}:</strong> {{ $shippingTerms }}</p>
                                        @endif
                                        @if ($destination)
                                            <p style="margin:0;font-size:14px;color:#374151;"><strong>{{ __('mail.manufacturer_order_created.destination') }}:</strong> {{ $destination }}</p>
                                        @endif
                                    </td>
                                </tr>
                            </table>

                            <p style="margin:0 0 12px 0;font-size:12px;font-weight:700;letter-spacing:0.08em;color:#6b7280;text-transform:uppercase;">
                                {{ __('mail.manufacturer_order_created.products_heading') }}
                            </p>
                            <table role="presentation" width="100%" cellspacing="0" cellpadding="0" border="0" style="border:1px solid #e5e7eb;border-radius:8px;margin:0 0 24px 0;overflow:hidden;">
                                <tr style="background-color:#f3f4f6;">
                                    <td style="padding:10px 12px;font-size:12px;font-weight:700;color:#374151;">{{ __('mail.manufacturer_order_created.product') }}</td>
                                    <td style="padding:10px 12px;font-size:12px;font-weight:700;color:#374151;">{{ __('mail.manufacturer_order_created.qty') }}</td>
                                    <td style="padding:10px 12px;font-size:12px;font-weight:700;color:#374151;text-align:right;">{{ __('mail.manufacturer_order_created.unit_price') }}</td>
                                    <td style="padding:10px 12px;font-size:12px;font-weight:700;color:#374151;text-align:right;">{{ __('mail.manufacturer_order_created.line_total') }}</td>
                                </tr>
                                @foreach ($items as $item)
                                    <tr>
                                        <td style="padding:12px;border-top:1px solid #e5e7eb;font-size:13px;color:#374151;">
                                            {{ $item['productName'] }}
                                            @if (! empty($item['notes']))
                                                <br><span style="font-size:12px;color:#6b7280;">{{ $item['notes'] }}</span>
                                            @endif
                                        </td>
                                        <td style="padding:12px;border-top:1px solid #e5e7eb;font-size:13px;color:#374151;">{{ $item['quantity'] }} {{ $item['quantityUnit'] }}</td>
                                        <td style="padding:12px;border-top:1px solid #e5e7eb;font-size:13px;color:#374151;text-align:right;">{{ $currencyCode }} {{ $item['unitPrice'] }}</td>
                                        <td style="padding:12px;border-top:1px solid #e5e7eb;font-size:13px;color:#374151;text-align:right;">{{ $currencyCode }} {{ $item['lineTotal'] }}</td>
                                    </tr>
                                @endforeach
                                <tr style="background-color:#f9fafb;">
                                    <td colspan="3" style="padding:12px;border-top:1px solid #e5e7eb;font-size:14px;font-weight:700;color:#111827;text-align:right;">{{ __('mail.manufacturer_order_created.total_amount') }}</td>
                                    <td style="padding:12px;border-top:1px solid #e5e7eb;font-size:14px;font-weight:700;color:#111827;text-align:right;">{{ $currencyCode }} {{ $totalAmount }}</td>
                                </tr>
                            </table>

                            @if ($notes)
                                <p style="margin:0 0 24px 0;font-size:14px;line-height:1.6;color:#374151;">
                                    <strong>{{ __('mail.manufacturer_order_created.notes') }}:</strong> {{ $notes }}
                                </p>
                            @endif

                            <table role="presentation" cellspacing="0" cellpadding="0" border="0">
                                <tr>
                                    <td style="border-radius:8px;background-color:#1d4ed8;">
                                        <a href="{{ $ordersUrl }}" style="display:inline-block;padding:14px 24px;font-size:15px;font-weight:600;color:#ffffff;text-decoration:none;">
                                            {{ __('mail.manufacturer_order_created.cta') }}
                                        </a>
                                    </td>
                                </tr>
                            </table>
                        </td>
                    </tr>
                    <tr>
                        <td style="padding:20px 28px;background-color:#f9fafb;border-top:1px solid #e5e7eb;">
                            <p style="margin:0;font-size:12px;line-height:1.6;color:#6b7280;text-align:center;">
                                {{ __('mail.manufacturer_order_created.footer') }}
                            </p>
                        </td>
                    </tr>
                </table>
            </td>
        </tr>
    </table>
</body>

</html>
