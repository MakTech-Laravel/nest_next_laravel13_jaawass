@extends('mail.layouts.classic')

@section('title', __('mail.manufacturer_order_created.subject', ['orderNumber' => $orderNumber, 'manufacturerName' => $manufacturerName]))
@section('preheader', __('mail.manufacturer_order_created.preheader', ['manufacturerName' => $manufacturerName]))
@section('header_eyebrow', __('mail.layout.default_eyebrow'))
@section('header_title', __('mail.manufacturer_order_created.heading'))
@section('header_subtitle', $orderNumber)

@section('content')
    <p style="margin:0 0 16px 0;font-size:15px;line-height:1.6;color:#2c2517;font-family:'Inter',system-ui,sans-serif;">
        {{ __('mail.manufacturer_order_created.greeting', ['name' => $buyerName]) }}
    </p>
    <p style="margin:0 0 24px 0;font-size:14px;line-height:1.7;color:#7a6e5a;font-family:'Inter',system-ui,sans-serif;">
        {{ __('mail.manufacturer_order_created.intro', ['manufacturerName' => $manufacturerName, 'orderNumber' => $orderNumber]) }}
    </p>

    @include('mail.partials.details-table', [
        'detailsHeading' => __('mail.manufacturer_order_created.order_heading'),
        'details' => array_filter([
            __('mail.manufacturer_order_created.order_title') => $orderTitle,
            __('mail.manufacturer_order_created.estimated_delivery') => $estimatedDeliveryAt,
            __('mail.manufacturer_order_created.production_lead') => $productionLead ?? null,
            __('mail.manufacturer_order_created.payment_terms') => $paymentTerms ?? null,
            __('mail.manufacturer_order_created.shipping_terms') => $shippingTerms ?? null,
            __('mail.manufacturer_order_created.destination') => $destination ?? null,
        ]),
    ])

    <p style="margin:0 0 12px 0;font-size:9.5px;font-weight:600;letter-spacing:0.18em;text-transform:uppercase;color:#a89880;font-family:'Inter',system-ui,sans-serif;">
        {{ __('mail.manufacturer_order_created.products_heading') }}
    </p>
    <table role="presentation" width="100%" cellspacing="0" cellpadding="0" border="0" style="border:1px solid #d4c9b0;border-radius:8px;margin:0 0 24px 0;overflow:hidden;background:#ffffff;">
        <tr style="background-color:#f5f0e8;">
            <td style="padding:10px 12px;font-size:12px;font-weight:700;color:#2c2517;">{{ __('mail.manufacturer_order_created.product') }}</td>
            <td style="padding:10px 12px;font-size:12px;font-weight:700;color:#2c2517;">{{ __('mail.manufacturer_order_created.qty') }}</td>
            <td style="padding:10px 12px;font-size:12px;font-weight:700;color:#2c2517;text-align:right;">{{ __('mail.manufacturer_order_created.unit_price') }}</td>
            <td style="padding:10px 12px;font-size:12px;font-weight:700;color:#2c2517;text-align:right;">{{ __('mail.manufacturer_order_created.line_total') }}</td>
        </tr>
        @foreach ($items as $item)
            <tr>
                <td style="padding:12px;border-top:1px solid #d4c9b0;font-size:13px;color:#7a6e5a;">
                    {{ $item['productName'] }}
                    @if (! empty($item['notes']))
                        <br><span style="font-size:12px;color:#a89880;">{{ $item['notes'] }}</span>
                    @endif
                </td>
                <td style="padding:12px;border-top:1px solid #d4c9b0;font-size:13px;color:#7a6e5a;">{{ $item['quantity'] }} {{ $item['quantityUnit'] }}</td>
                <td style="padding:12px;border-top:1px solid #d4c9b0;font-size:13px;color:#7a6e5a;text-align:right;">{{ $currencyCode }} {{ $item['unitPrice'] }}</td>
                <td style="padding:12px;border-top:1px solid #d4c9b0;font-size:13px;color:#7a6e5a;text-align:right;">{{ $currencyCode }} {{ $item['lineTotal'] }}</td>
            </tr>
        @endforeach
        <tr style="background-color:#f5f0e8;">
            <td colspan="3" style="padding:12px;border-top:1px solid #d4c9b0;font-size:14px;font-weight:700;color:#2c2517;text-align:right;">{{ __('mail.manufacturer_order_created.total_amount') }}</td>
            <td style="padding:12px;border-top:1px solid #d4c9b0;font-size:14px;font-weight:700;color:#2c2517;text-align:right;">{{ $currencyCode }} {{ $totalAmount }}</td>
        </tr>
    </table>

    @if ($notes)
        <p style="margin:0 0 24px 0;font-size:14px;line-height:1.6;color:#7a6e5a;">
            <strong style="color:#2c2517;">{{ __('mail.manufacturer_order_created.notes') }}:</strong> {{ $notes }}
        </p>
    @endif

    @include('mail.partials.cta-button', [
        'ctaUrl' => $ordersUrl,
        'ctaLabel' => __('mail.manufacturer_order_created.cta'),
    ])
@endsection
