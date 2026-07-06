<?php

return [
    'manufacturer' => 'Manufacturer',
    'product' => 'Product',

    'notifications' => [
        'created' => [
            'buyer_title' => 'New order received',
            'buyer_body' => ':manufacturerName created order :orderNumber for you.',
            'admin_title' => 'New order created',
            'admin_body' => ':manufacturerName created order :orderNumber for :buyerName.',
            'manufacturer_title' => 'Order created',
            'manufacturer_body' => 'You created order :orderNumber for :buyerName.',
        ],
        'status' => [
            'buyer_title' => 'Order :orderNumber updated',
            'admin_title' => 'Order :orderNumber status updated',
            'manufacturer_title' => 'Order :orderNumber updated',
            'manufacturer_body' => 'Order :orderNumber for :buyerName is now :status.',
            'buyer_body' => [
                'order_created' => ':manufacturerName created order :orderNumber.',
                'in_production' => 'Order :orderNumber from :manufacturerName is now in production.',
                'ready_for_shipment' => 'Order :orderNumber from :manufacturerName is ready for shipment.',
                'shipped' => 'Order :orderNumber from :manufacturerName has been shipped.',
                'completed' => 'Order :orderNumber from :manufacturerName has been completed.',
                'cancelled' => 'Order :orderNumber from :manufacturerName has been cancelled.',
            ],
            'admin_body' => [
                'order_created' => ':manufacturerName set order :orderNumber to created.',
                'in_production' => ':manufacturerName moved order :orderNumber to in production.',
                'ready_for_shipment' => ':manufacturerName marked order :orderNumber ready for shipment.',
                'shipped' => ':manufacturerName shipped order :orderNumber.',
                'completed' => ':manufacturerName completed order :orderNumber.',
                'cancelled' => ':manufacturerName cancelled order :orderNumber.',
            ],
        ],
    ],
];
