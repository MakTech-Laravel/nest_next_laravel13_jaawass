<?php

return [

    'password_reset_otp' => [
        'subject' => 'Your password reset code',
        'title' => 'Password reset',
        'intro' => 'Use the code below to reset your password. If you did not request this, you can ignore this email.',
        'expires' => 'This code expires in :minutes minutes.',
    ],

    'account_restore_otp' => [
        'subject' => 'Account deletion cancellation code',
        'title' => 'Cancel account deletion',
        'intro' => 'Use the code below to cancel the scheduled deletion of your account.',
        'expires' => 'This code expires in :minutes minutes.',
    ],

    'welcome' => [
        'subject' => 'Welcome to SourceNest',
        'preheader' => 'Your SourceNest account is ready. Start exploring suppliers today.',
    ],

    'manufacturer_additional_information' => [
        'subject' => 'Additional information requested for your application',
        'preheader' => 'SourceNest needs more details to continue reviewing your manufacturer application.',
        'greeting' => 'Hello :name,',
        'intro' => 'Our review team needs additional information to continue processing your manufacturer application for :company.',
        'admin_message_heading' => 'What we need from you',
        'allowed_types_heading' => 'You can submit the following',
        'cta' => 'Submit information',
        'expires' => 'This link expires on :date.',
        'footer' => 'If you did not apply to SourceNest, you can safely ignore this email.',
    ],

    'subscription_expiry_reminder' => [
        'subject' => 'Your subscription ends in :days days',
        'preheader' => 'Renew your plan before access is paused.',
        'greeting' => 'Hello :name,',
        'intro' => 'Your :plan subscription will end on :date (:days days from now).',
        'body' => 'Renew now to keep your manufacturer features, product visibility, and analytics without interruption.',
        'cta' => 'Renew subscription',
        'footer' => 'If you already renewed, you can ignore this email.',
        'notification_title' => 'Subscription ending soon',
        'notification_body' => 'Your :plan plan ends in :days days. Renew to avoid interruption.',
    ],

    'subscription_expired' => [
        'subject' => 'Your subscription has ended',
        'preheader' => 'Your plan is paused until you renew.',
        'greeting' => 'Hello :name,',
        'intro' => 'Your :plan subscription ended on :date.',
        'body' => 'Your account is paused until you complete payment. Once you pay, your subscription will automatically continue and your plan benefits will be restored.',
        'cta' => 'Renew now',
        'footer' => 'Need help? Contact our support team.',
        'notification_title' => 'Subscription paused',
        'notification_body' => 'Your :plan subscription has ended. Renew to restore access.',
    ],

    'subscription_created' => [
        'subject' => 'Welcome — your plan is active',
        'preheader' => 'Your subscription is now active on SourceNest.',
        'greeting' => 'Hello :name,',
        'intro' => 'You are now enrolled in the :plan plan.',
        'details_heading' => 'Subscription details',
        'billing_interval' => 'Billing interval',
        'starts_at' => 'Starts',
        'ends_at' => 'Renews / ends',
        'paid_amount' => 'Amount paid',
        'cta' => 'View your plan',
        'footer' => 'Thank you for subscribing to SourceNest.',
        'notification_title' => 'Subscription active',
        'notification_body' => 'You are now subscribed to :plan.',
    ],

    'subscription_renewed' => [
        'subject' => 'Payment received — subscription continued',
        'preheader' => 'Your subscription has been renewed successfully.',
        'greeting' => 'Hello :name,',
        'intro' => 'We received your payment and your :plan subscription is active again.',
        'details_heading' => 'Renewal details',
        'billing_interval' => 'Billing interval',
        'starts_at' => 'Starts',
        'ends_at' => 'Renews / ends',
        'paid_amount' => 'Amount paid',
        'cta' => 'View your plan',
        'footer' => 'Thank you for continuing with SourceNest.',
        'notification_title' => 'Subscription renewed',
        'notification_body' => 'Your :plan subscription has been renewed.',
    ],

    'manufacturer_admin_message' => [
        'subject' => 'New message about your manufacturer application',
        'preheader' => 'SourceNest review team sent you a message.',
        'greeting' => 'Hello :name,',
        'intro' => ':admin from the SourceNest team sent you a message regarding your application for :company.',
        'message_heading' => 'Message',
        'ticket_heading' => 'Support ticket',
        'ticket_body' => 'We also opened support ticket ":subject" so you can follow up in your dashboard.',
        'cta' => 'View support ticket',
        'footer' => 'If you did not apply to SourceNest, you can safely ignore this email.',
    ],

    'supplier_report_received' => [
        'subject' => 'We received your supplier report',
        'preheader' => 'Our Trust & Safety team is reviewing your report.',
        'greeting' => 'Hello :name,',
        'intro' => 'Thank you for reporting :supplier. We received report #:id and our team will review it within 24-48 hours.',
        'reason_heading' => 'Report reason',
        'details_heading' => 'Your details',
        'cta' => 'View your reports',
        'footer' => 'False reports may result in account restrictions.',
    ],

    'supplier_report_status_updated' => [
        'subject' => 'Update on your supplier report',
        'preheader' => 'The status of your supplier report has changed.',
        'greeting' => 'Hello :name,',
        'intro' => 'Your report about :supplier is now marked as :status.',
        'message_heading' => 'Message from our team',
        'cta' => 'View your reports',
        'footer' => 'Thank you for helping keep SourceNest safe.',
    ],

    'manufacturer_order_created' => [
        'subject' => 'New order :orderNumber from :manufacturerName',
        'preheader' => ':manufacturerName created a new order for you.',
        'heading' => 'New order received',
        'greeting' => 'Hello :name,',
        'intro' => ':manufacturerName created order :orderNumber for you on SourceNest.',
        'order_heading' => 'Order details',
        'order_title' => 'Order title',
        'estimated_delivery' => 'Estimated delivery',
        'production_lead' => 'Production time',
        'payment_terms' => 'Payment terms',
        'shipping_terms' => 'Shipping terms',
        'destination' => 'Destination',
        'products_heading' => 'Products',
        'product' => 'Product',
        'qty' => 'Qty',
        'unit_price' => 'Unit price',
        'line_total' => 'Line total',
        'total_amount' => 'Total amount',
        'notes' => 'Notes',
        'cta' => 'View order',
        'footer' => 'You received this email because a manufacturer created an order linked to your account.',
    ],

    'admin_test_email' => [
        'subject' => 'Test email from :platform_name',
    ],

];
