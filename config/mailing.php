<?php

return [

    'queue' => env('MAILING_QUEUE', 'default'),

    'job_delay_seconds' => (int) env('MAILING_JOB_DELAY_SECONDS', 1),

    'dispatch_sequence_cache_key' => 'mailing:dispatch_sequence',

    'registration_reminder_days' => (int) env('MAILING_REGISTRATION_REMINDER_DAYS', 3),

    'activation_reminder_days' => (int) env('MAILING_ACTIVATION_REMINDER_DAYS', 3),

    'templates' => [
        'welcome' => [
            'view' => 'mail.auth.welcome',
            'subject' => 'mail.welcome.subject',
            'markdown' => false,
        ],
        'password-reset-otp' => [
            'view' => 'mail.auth.otp-security',
            'subject' => 'mail.password_reset_otp.subject',
            'markdown' => false,
        ],
        'account-restore-otp' => [
            'view' => 'mail.auth.account-restore-otp',
            'subject' => 'mail.account_restore_otp.subject',
            'markdown' => false,
        ],
        'email-verification' => [
            'view' => 'mail.auth.otp-verification',
            'subject' => 'mail.email_verification.subject',
            'markdown' => false,
        ],
        'manufacturer-under-review' => [
            'view' => 'mail.manufacturer.manufacturer-under-review',
            'subject' => 'mail.manufacturer_under_review.subject',
            'markdown' => false,
        ],
        'buyer-registration-reminder' => [
            'view' => 'mail.auth.buyer-registration-reminder',
            'subject' => 'mail.buyer_registration_reminder.subject',
            'markdown' => false,
        ],
        'rfq-submitted-buyer' => [
            'view' => 'mail.rfq.rfq-submitted-buyer',
            'subject' => 'mail.rfq_submitted_buyer.subject',
            'markdown' => false,
        ],
        'manufacturer-registration-reminder' => [
            'view' => 'mail.manufacturer.manufacturer-registration-reminder',
            'subject' => 'mail.manufacturer_registration_reminder.subject',
            'markdown' => false,
        ],
        'manufacturer-activation-reminder' => [
            'view' => 'mail.manufacturer.manufacturer-activation-reminder',
            'subject' => 'mail.manufacturer_activation_reminder.subject',
            'markdown' => false,
        ],
        'payment-failed' => [
            'view' => 'mail.subscription.payment-failed',
            'subject' => 'mail.payment_failed.subject',
            'markdown' => false,
        ],
        'password-changed' => [
            'view' => 'mail.auth.password-changed',
            'subject' => 'mail.password_changed.subject',
            'markdown' => false,
        ],
        'admin-new-inquiry' => [
            'view' => 'mail.admin.admin-new-inquiry',
            'subject' => 'mail.admin_new_inquiry.subject',
            'markdown' => false,
        ],
        'manufacturer-additional-information' => [
            'view' => 'mail.manufacturer.manufacturer-additional-information',
            'subject' => 'mail.manufacturer_additional_information.subject',
            'markdown' => false,
        ],
        'admin-manufacturer-additional-information-response' => [
            'view' => 'mail.admin.admin-manufacturer-additional-information-response',
            'subject' => 'mail.admin_manufacturer_additional_information_response.subject',
            'markdown' => false,
        ],
        'manufacturer-admin-message' => [
            'view' => 'mail.manufacturer.manufacturer-admin-message',
            'subject' => 'mail.manufacturer_admin_message.subject',
            'markdown' => false,
        ],
        'supplier-report-received' => [
            'view' => 'mail.supplier-report.supplier-report-received',
            'subject' => 'mail.supplier_report_received.subject',
            'markdown' => false,
        ],
        'supplier-report-status-updated' => [
            'view' => 'mail.supplier-report.supplier-report-status-updated',
            'subject' => 'mail.supplier_report_status_updated.subject',
            'markdown' => false,
        ],
        'supplier-report-received-admin' => [
            'view' => 'mail.admin.supplier-report-received-admin',
            'subject' => 'mail.supplier_report_received_admin.subject',
            'markdown' => false,
        ],
        'subscription-expiry-reminder' => [
            'view' => 'mail.subscription.subscription-expiry-reminder',
            'subject' => 'mail.subscription_expiry_reminder.subject',
            'markdown' => false,
        ],
        'subscription-expired' => [
            'view' => 'mail.subscription.subscription-expired',
            'subject' => 'mail.subscription_expired.subject',
            'markdown' => false,
        ],
        'subscription-created' => [
            'view' => 'mail.subscription.subscription-activated',
            'subject' => 'mail.subscription_created.subject',
            'markdown' => false,
        ],
        'subscription-renewed' => [
            'view' => 'mail.subscription.subscription-renewed',
            'subject' => 'mail.subscription_renewed.subject',
            'markdown' => false,
        ],
        'manufacturer-order-created' => [
            'view' => 'mail.order.manufacturer-order-created',
            'subject' => 'mail.manufacturer_order_created.subject',
            'markdown' => false,
        ],
        'order-created-manufacturer' => [
            'view' => 'mail.order.manufacturer-order-created',
            'subject' => 'mail.order_created_manufacturer.subject',
            'markdown' => false,
        ],
        'order-created-admin' => [
            'view' => 'mail.order.manufacturer-order-created',
            'subject' => 'mail.order_created_admin.subject',
            'markdown' => false,
        ],
        'order-status-updated' => [
            'view' => 'mail.order.order-status-updated',
            'subject' => 'mail.order_status_updated.subject',
            'markdown' => false,
        ],
        'order-in-production-buyer' => [
            'view' => 'mail.order.order-in-production-buyer',
            'subject' => 'mail.order_in_production.subject',
            'markdown' => false,
        ],
        'order-in-production-manufacturer' => [
            'view' => 'mail.order.order-in-production-manufacturer',
            'subject' => 'mail.order_in_production.subject',
            'markdown' => false,
        ],
        'order-ready-for-shipment-buyer' => [
            'view' => 'mail.order.order-ready-for-shipment-buyer',
            'subject' => 'mail.order_ready_for_shipment.subject',
            'markdown' => false,
        ],
        'order-ready-for-shipment-manufacturer' => [
            'view' => 'mail.order.order-ready-for-shipment-manufacturer',
            'subject' => 'mail.order_ready_for_shipment.subject',
            'markdown' => false,
        ],
        'order-shipped-buyer' => [
            'view' => 'mail.order.order-shipped-buyer',
            'subject' => 'mail.order_shipped.subject',
            'markdown' => false,
        ],
        'order-shipped-manufacturer' => [
            'view' => 'mail.order.order-shipped-manufacturer',
            'subject' => 'mail.order_shipped.subject',
            'markdown' => false,
        ],
        'order-completed-buyer' => [
            'view' => 'mail.order.order-completed-buyer',
            'subject' => 'mail.order_completed.subject',
            'markdown' => false,
        ],
        'order-completed-manufacturer' => [
            'view' => 'mail.order.order-completed-manufacturer',
            'subject' => 'mail.order_completed.subject',
            'markdown' => false,
        ],
        'order-completed-admin' => [
            'view' => 'mail.order.order-completed-admin',
            'subject' => 'mail.order_completed.subject',
            'markdown' => false,
        ],
        'order-cancelled-buyer' => [
            'view' => 'mail.order.order-cancelled-buyer',
            'subject' => 'mail.order_cancelled.subject',
            'markdown' => false,
        ],
        'order-cancelled-manufacturer' => [
            'view' => 'mail.order.order-cancelled-manufacturer',
            'subject' => 'mail.order_cancelled.subject',
            'markdown' => false,
        ],
        'order-cancelled-admin' => [
            'view' => 'mail.order.order-cancelled-admin',
            'subject' => 'mail.order_cancelled.subject',
            'markdown' => false,
        ],
        'order-review-invite' => [
            'view' => 'mail.order.order-review-invite',
            'subject' => 'How was your experience with :manufacturerName? Leave a review',
            'markdown' => false,
        ],
        'review-approved' => [
            'view' => 'mail.review.review-approved',
            'subject' => 'Your review was approved — :productName',
            'markdown' => false,
        ],
        'new-product-review' => [
            'view' => 'mail.review.new-product-review',
            'subject' => 'You received a new product review — :productName',
            'markdown' => false,
        ],
        'rfq-created-manufacturer' => [
            'view' => 'mail.rfq.rfq-created-manufacturer',
            'subject' => 'mail.rfq_created_manufacturer.subject',
            'markdown' => false,
        ],
        'rfq-quoted-buyer' => [
            'view' => 'mail.rfq.rfq-quoted-buyer',
            'subject' => 'mail.rfq_quoted_buyer.subject',
            'markdown' => false,
        ],
        'rfq-status-updated' => [
            'view' => 'mail.rfq.rfq-status-updated',
            'subject' => 'mail.rfq_status_updated.subject',
            'markdown' => false,
        ],
        'conversation-message-received' => [
            'view' => 'mail.conversation.conversation-reply-received',
            'subject' => 'mail.conversation_message_received.subject',
            'markdown' => false,
        ],
        'support-ticket-created' => [
            'view' => 'mail.support.support-ticket-created',
            'subject' => 'Your support ticket has been received — :ticketNumber',
            'markdown' => false,
        ],
        'support-ticket-created-admin' => [
            'view' => 'mail.support.support-ticket-created-admin',
            'subject' => 'New support ticket opened — :ticketNumber',
            'markdown' => false,
        ],
        'support-ticket-reply' => [
            'view' => 'mail.support.support-ticket-reply',
            'subject' => 'mail.support_ticket_reply.subject',
            'markdown' => false,
        ],
        'support-ticket-reply-admin' => [
            'view' => 'mail.support.support-ticket-reply-admin',
            'subject' => 'mail.support_ticket_reply_admin.subject',
            'markdown' => false,
        ],
        'support-ticket-reply-received' => [
            'view' => 'mail.support.support-ticket-reply-received',
            'subject' => 'mail.support_ticket_reply_received.subject',
            'markdown' => false,
        ],
        'support-ticket-resolved' => [
            'view' => 'mail.support.support-ticket-resolved',
            'subject' => 'mail.support_ticket_resolved.subject',
            'markdown' => false,
        ],
        'manufacturer-approved' => [
            'view' => 'mail.manufacturer.manufacturer-approved',
            'subject' => 'mail.manufacturer_approved.subject',
            'markdown' => false,
        ],
        'manufacturer-rejected' => [
            'view' => 'mail.manufacturer.manufacturer-rejected',
            'subject' => 'mail.manufacturer_rejected.subject',
            'markdown' => false,
        ],
        'manufacturer-registered-admin' => [
            'view' => 'mail.admin.admin-manufacturer-registered',
            'subject' => 'mail.manufacturer_registered_admin.subject',
            'markdown' => false,
        ],
        'admin-test-email' => [
            'view' => 'mail.admin.admin-test-email',
            'subject' => 'mail.admin_test_email.subject',
            'markdown' => false,
        ],
    ],
    'password-reset-otp' => [
      'view' => 'mail.auth.otp-security',
      'subject' => 'mail.password_reset_otp.subject',
      'markdown' => false,
    ],
    'account-restore-otp' => [
      'view' => 'mail.auth.account-restore-otp',
      'subject' => 'mail.account_restore_otp.subject',
      'markdown' => false,
    ],
    'email-verification' => [
      'view' => 'mail.auth.otp-verification',
      'subject' => 'mail.email_verification.subject',
      'markdown' => false,
    ],
    'manufacturer-under-review' => [
      'view' => 'mail.manufacturer.manufacturer-under-review',
      'subject' => 'mail.manufacturer_under_review.subject',
      'markdown' => false,
    ],
    'buyer-registration-reminder' => [
      'view' => 'mail.auth.buyer-registration-reminder',
      'subject' => 'mail.buyer_registration_reminder.subject',
      'markdown' => false,
    ],
    'rfq-submitted-buyer' => [
      'view' => 'mail.rfq.rfq-submitted-buyer',
      'subject' => 'mail.rfq_submitted_buyer.subject',
      'markdown' => false,
    ],
    'manufacturer-registration-reminder' => [
      'view' => 'mail.manufacturer.manufacturer-registration-reminder',
      'subject' => 'mail.manufacturer_registration_reminder.subject',
      'markdown' => false,
    ],
    'manufacturer-activation-reminder' => [
      'view' => 'mail.manufacturer.manufacturer-activation-reminder',
      'subject' => 'mail.manufacturer_activation_reminder.subject',
      'markdown' => false,
    ],
    'payment-failed' => [
      'view' => 'mail.subscription.payment-failed',
      'subject' => 'mail.payment_failed.subject',
      'markdown' => false,
    ],
    'password-changed' => [
      'view' => 'mail.auth.password-changed',
      'subject' => 'mail.password_changed.subject',
      'markdown' => false,
    ],
    'admin-new-inquiry' => [
      'view' => 'mail.admin.admin-new-inquiry',
      'subject' => 'mail.admin_new_inquiry.subject',
      'markdown' => false,
    ],
    'manufacturer-additional-information' => [
      'view' => 'mail.manufacturer.manufacturer-additional-information',
      'subject' => 'mail.manufacturer_additional_information.subject',
      'markdown' => false,
    ],
    'manufacturer-additional-information-received' => [
      'view' => 'mail.manufacturer.manufacturer-additional-information-received',
      'subject' => 'mail.manufacturer_additional_information_received.subject',
      'markdown' => false,
    ],
    'admin-manufacturer-additional-information-response' => [
      'view' => 'mail.admin.admin-manufacturer-additional-information-response',
      'subject' => 'mail.admin_manufacturer_additional_information_response.subject',
      'markdown' => false,
    ],
    'manufacturer-admin-message' => [
      'view' => 'mail.manufacturer.manufacturer-admin-message',
      'subject' => 'mail.manufacturer_admin_message.subject',
      'markdown' => false,
    ],
    'supplier-report-received' => [
      'view' => 'mail.supplier-report.supplier-report-received',
      'subject' => 'mail.supplier_report_received.subject',
      'markdown' => false,
    ],
    'supplier-report-status-updated' => [
      'view' => 'mail.supplier-report.supplier-report-status-updated',
      'subject' => 'mail.supplier_report_status_updated.subject',
      'markdown' => false,
    ],
    'supplier-report-received-admin' => [
      'view' => 'mail.admin.supplier-report-received-admin',
      'subject' => 'mail.supplier_report_received_admin.subject',
      'markdown' => false,
    ],
    'subscription-expiry-reminder' => [
      'view' => 'mail.subscription.subscription-expiry-reminder',
      'subject' => 'mail.subscription_expiry_reminder.subject',
      'markdown' => false,
    ],
    'subscription-expired' => [
      'view' => 'mail.subscription.subscription-expired',
      'subject' => 'mail.subscription_expired.subject',
      'markdown' => false,
    ],
    'subscription-created' => [
      'view' => 'mail.subscription.subscription-activated',
      'subject' => 'mail.subscription_created.subject',
      'markdown' => false,
    ],
    'subscription-renewed' => [
      'view' => 'mail.subscription.subscription-renewed',
      'subject' => 'mail.subscription_renewed.subject',
      'markdown' => false,
    ],
    'manufacturer-order-created' => [
      'view' => 'mail.order.manufacturer-order-created',
      'subject' => 'mail.manufacturer_order_created.subject',
      'markdown' => false,
    ],
    'order-created-manufacturer' => [
      'view' => 'mail.order.manufacturer-order-created',
      'subject' => 'mail.order_created_manufacturer.subject',
      'markdown' => false,
    ],
    'order-created-admin' => [
      'view' => 'mail.order.manufacturer-order-created',
      'subject' => 'mail.order_created_admin.subject',
      'markdown' => false,
    ],
    'order-status-updated' => [
      'view' => 'mail.order.order-status-updated',
      'subject' => 'mail.order_status_updated.subject',
      'markdown' => false,
    ],
    'order-in-production-buyer' => [
      'view' => 'mail.order.order-in-production-buyer',
      'subject' => 'mail.order_in_production.subject',
      'markdown' => false,
    ],
    'order-in-production-manufacturer' => [
      'view' => 'mail.order.order-in-production-manufacturer',
      'subject' => 'mail.order_in_production.subject',
      'markdown' => false,
    ],
    'order-ready-for-shipment-buyer' => [
      'view' => 'mail.order.order-ready-for-shipment-buyer',
      'subject' => 'mail.order_ready_for_shipment.subject',
      'markdown' => false,
    ],
    'order-ready-for-shipment-manufacturer' => [
      'view' => 'mail.order.order-ready-for-shipment-manufacturer',
      'subject' => 'mail.order_ready_for_shipment.subject',
      'markdown' => false,
    ],
    'order-shipped-buyer' => [
      'view' => 'mail.order.order-shipped-buyer',
      'subject' => 'mail.order_shipped.subject',
      'markdown' => false,
    ],
    'order-shipped-manufacturer' => [
      'view' => 'mail.order.order-shipped-manufacturer',
      'subject' => 'mail.order_shipped.subject',
      'markdown' => false,
    ],
    'order-completed-buyer' => [
      'view' => 'mail.order.order-completed-buyer',
      'subject' => 'mail.order_completed.subject',
      'markdown' => false,
    ],
    'order-completed-manufacturer' => [
      'view' => 'mail.order.order-completed-manufacturer',
      'subject' => 'mail.order_completed.subject',
      'markdown' => false,
    ],
    'order-completed-admin' => [
      'view' => 'mail.order.order-completed-admin',
      'subject' => 'mail.order_completed.subject',
      'markdown' => false,
    ],
    'order-cancelled-buyer' => [
      'view' => 'mail.order.order-cancelled-buyer',
      'subject' => 'mail.order_cancelled.subject',
      'markdown' => false,
    ],
    'order-cancelled-manufacturer' => [
      'view' => 'mail.order.order-cancelled-manufacturer',
      'subject' => 'mail.order_cancelled.subject',
      'markdown' => false,
    ],
    'order-cancelled-admin' => [
      'view' => 'mail.order.order-cancelled-admin',
      'subject' => 'mail.order_cancelled.subject',
      'markdown' => false,
    ],
    'order-review-invite' => [
      'view' => 'mail.order.order-review-invite',
      'subject' => 'How was your experience with :manufacturerName? Leave a review',
      'markdown' => false,
    ],
    'review-approved' => [
      'view' => 'mail.review.review-approved',
      'subject' => 'Your review was approved — :productName',
      'markdown' => false,
    ],
    'new-product-review' => [
      'view' => 'mail.review.new-product-review',
      'subject' => 'You received a new product review — :productName',
      'markdown' => false,
    ],
    'rfq-created-manufacturer' => [
      'view' => 'mail.rfq.rfq-created-manufacturer',
      'subject' => 'mail.rfq_created_manufacturer.subject',
      'markdown' => false,
    ],
    'rfq-quoted-buyer' => [
      'view' => 'mail.rfq.rfq-quoted-buyer',
      'subject' => 'mail.rfq_quoted_buyer.subject',
      'markdown' => false,
    ],
    'rfq-status-updated' => [
      'view' => 'mail.rfq.rfq-status-updated',
      'subject' => 'mail.rfq_status_updated.subject',
      'markdown' => false,
    ],
    'conversation-message-received' => [
      'view' => 'mail.conversation.conversation-reply-received',
      'subject' => 'mail.conversation_message_received.subject',
      'markdown' => false,
    ],
    'support-ticket-created' => [
      'view' => 'mail.support.support-ticket-created',
      'subject' => 'Your support ticket has been received — :ticketNumber',
      'markdown' => false,
    ],
    'support-ticket-created-admin' => [
      'view' => 'mail.support.support-ticket-created-admin',
      'subject' => 'New support ticket opened — :ticketNumber',
      'markdown' => false,
    ],
    'support-ticket-reply' => [
      'view' => 'mail.support.support-ticket-reply',
      'subject' => 'mail.support_ticket_reply.subject',
      'markdown' => false,
    ],
    'support-ticket-reply-admin' => [
      'view' => 'mail.support.support-ticket-reply-admin',
      'subject' => 'mail.support_ticket_reply_admin.subject',
      'markdown' => false,
    ],
    'support-ticket-auto-reply' => [
      'view' => 'mail.support.support-ticket-auto-reply',
      'subject' => 'mail.support_ticket_auto_reply.subject',
      'markdown' => false,
    ],
    'support-ticket-resolved' => [
      'view' => 'mail.support.support-ticket-resolved',
      'subject' => 'mail.support_ticket_resolved.subject',
      'markdown' => false,
    ],
    'manufacturer-approved' => [
      'view' => 'mail.manufacturer.manufacturer-approved',
      'subject' => 'mail.manufacturer_approved.subject',
      'markdown' => false,
    ],
    'manufacturer-rejected' => [
      'view' => 'mail.manufacturer.manufacturer-rejected',
      'subject' => 'mail.manufacturer_rejected.subject',
      'markdown' => false,
    ],
    'manufacturer-registered-admin' => [
      'view' => 'mail.admin.admin-manufacturer-registered',
      'subject' => 'mail.manufacturer_registered_admin.subject',
      'markdown' => false,
    ],
    'admin-test-email' => [
      'view' => 'mail.admin.admin-test-email',
      'subject' => 'mail.admin_test_email.subject',
      'markdown' => false,
    ],
  ];
