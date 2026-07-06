<?php

return [

  'queue' => env('MAILING_QUEUE', 'default'),

  'job_delay_seconds' => (int) env('MAILING_JOB_DELAY_SECONDS', 1),

  'dispatch_sequence_cache_key' => 'mailing:dispatch_sequence',

  'templates' => [
    'welcome' => [
      'view' => 'mail.welcome',
      'subject' => 'mail.welcome.subject',
      'markdown' => false,
    ],
    'password-reset-otp' => [
      'view' => 'mail.otp',
      'subject' => 'mail.password_reset_otp.subject',
      'markdown' => false,
    ],
    'account-restore-otp' => [
      'view' => 'mail.otp',
      'subject' => 'mail.account_restore_otp.subject',
      'markdown' => false,
    ],
    'email-verification' => [
      'view' => 'mail.otp',
      'subject' => 'mail.email_verification.subject',
      'markdown' => false,
    ],
    'manufacturer-additional-information' => [
      'view' => 'mail.manufacturer-additional-information',
      'subject' => 'mail.manufacturer_additional_information.subject',
      'markdown' => false,
    ],
    'admin-manufacturer-additional-information-response' => [
      'view' => 'mail.admin-manufacturer-additional-information-response',
      'subject' => 'mail.admin_manufacturer_additional_information_response.subject',
      'markdown' => false,
    ],
    'manufacturer-admin-message' => [
      'view' => 'mail.transactional',
      'subject' => 'mail.manufacturer_admin_message.subject',
      'markdown' => false,
    ],
    'supplier-report-received' => [
      'view' => 'mail.transactional',
      'subject' => 'mail.supplier_report_received.subject',
      'markdown' => false,
    ],
    'supplier-report-status-updated' => [
      'view' => 'mail.transactional',
      'subject' => 'mail.supplier_report_status_updated.subject',
      'markdown' => false,
    ],
    'supplier-report-received-admin' => [
      'view' => 'mail.transactional',
      'subject' => 'mail.supplier_report_received_admin.subject',
      'markdown' => false,
    ],
    'subscription-expiry-reminder' => [
      'view' => 'mail.transactional',
      'subject' => 'mail.subscription_expiry_reminder.subject',
      'markdown' => false,
    ],
    'subscription-expired' => [
      'view' => 'mail.transactional',
      'subject' => 'mail.subscription_expired.subject',
      'markdown' => false,
    ],
    'subscription-created' => [
      'view' => 'mail.transactional',
      'subject' => 'mail.subscription_created.subject',
      'markdown' => false,
    ],
    'subscription-renewed' => [
      'view' => 'mail.transactional',
      'subject' => 'mail.subscription_renewed.subject',
      'markdown' => false,
    ],
    'manufacturer-order-created' => [
      'view' => 'mail.manufacturer-order-created',
      'subject' => 'mail.manufacturer_order_created.subject',
      'markdown' => false,
    ],
    'order-created-manufacturer' => [
      'view' => 'mail.transactional',
      'subject' => 'mail.order_created_manufacturer.subject',
      'markdown' => false,
    ],
    'order-created-admin' => [
      'view' => 'mail.transactional',
      'subject' => 'mail.order_created_admin.subject',
      'markdown' => false,
    ],
    'order-status-updated' => [
      'view' => 'mail.transactional',
      'subject' => 'mail.order_status_updated.subject',
      'markdown' => false,
    ],
    'rfq-created-manufacturer' => [
      'view' => 'mail.transactional',
      'subject' => 'mail.rfq_created_manufacturer.subject',
      'markdown' => false,
    ],
    'rfq-quoted-buyer' => [
      'view' => 'mail.transactional',
      'subject' => 'mail.rfq_quoted_buyer.subject',
      'markdown' => false,
    ],
    'rfq-status-updated' => [
      'view' => 'mail.transactional',
      'subject' => 'mail.rfq_status_updated.subject',
      'markdown' => false,
    ],
    'conversation-message-received' => [
      'view' => 'mail.transactional',
      'subject' => 'mail.conversation_message_received.subject',
      'markdown' => false,
    ],
    'support-ticket-created' => [
      'view' => 'mail.transactional',
      'subject' => 'mail.support_ticket_created.subject',
      'markdown' => false,
    ],
    'support-ticket-created-admin' => [
      'view' => 'mail.transactional',
      'subject' => 'mail.support_ticket_created_admin.subject',
      'markdown' => false,
    ],
    'support-ticket-reply' => [
      'view' => 'mail.transactional',
      'subject' => 'mail.support_ticket_reply.subject',
      'markdown' => false,
    ],
    'support-ticket-resolved' => [
      'view' => 'mail.transactional',
      'subject' => 'mail.support_ticket_resolved.subject',
      'markdown' => false,
    ],
    'manufacturer-approved' => [
      'view' => 'mail.transactional',
      'subject' => 'mail.manufacturer_approved.subject',
      'markdown' => false,
    ],
    'manufacturer-rejected' => [
      'view' => 'mail.transactional',
      'subject' => 'mail.manufacturer_rejected.subject',
      'markdown' => false,
    ],
    'manufacturer-registered-admin' => [
      'view' => 'mail.transactional',
      'subject' => 'mail.manufacturer_registered_admin.subject',
      'markdown' => false,
    ],
    'admin-test-email' => [
      'view' => 'mail.admin-test-email',
      'subject' => 'mail.admin_test_email.subject',
      'markdown' => false,
    ],
  ],

];
