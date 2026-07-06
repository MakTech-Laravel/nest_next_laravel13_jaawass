<?php

return [
    'manufacturer' => '制造商',
    'product' => '产品',

    'notifications' => [
        'created' => [
            'buyer_title' => '收到新订单',
            'buyer_body' => ':manufacturerName 为您创建了订单 :orderNumber。',
            'admin_title' => '新订单已创建',
            'admin_body' => ':manufacturerName 为 :buyerName 创建了订单 :orderNumber。',
            'manufacturer_title' => '订单已创建',
            'manufacturer_body' => '您为 :buyerName 创建了订单 :orderNumber。',
        ],
        'status' => [
            'buyer_title' => '订单 :orderNumber 已更新',
            'admin_title' => '订单 :orderNumber 状态已更新',
            'manufacturer_title' => '订单 :orderNumber 已更新',
            'manufacturer_body' => ':buyerName 的订单 :orderNumber 现为 :status。',
            'buyer_body' => [
                'order_created' => ':manufacturerName 创建了订单 :orderNumber。',
                'in_production' => ':manufacturerName 的订单 :orderNumber 已进入生产。',
                'ready_for_shipment' => ':manufacturerName 的订单 :orderNumber 已准备发货。',
                'shipped' => ':manufacturerName 的订单 :orderNumber 已发货。',
                'completed' => ':manufacturerName 的订单 :orderNumber 已完成。',
                'cancelled' => ':manufacturerName 的订单 :orderNumber 已取消。',
            ],
            'admin_body' => [
                'order_created' => ':manufacturerName 将订单 :orderNumber 设为已创建。',
                'in_production' => ':manufacturerName 将订单 :orderNumber 移至生产中。',
                'ready_for_shipment' => ':manufacturerName 将订单 :orderNumber 标记为待发货。',
                'shipped' => ':manufacturerName 已发货订单 :orderNumber。',
                'completed' => ':manufacturerName 已完成订单 :orderNumber。',
                'cancelled' => ':manufacturerName 已取消订单 :orderNumber。',
            ],
        ],
    ],
];
