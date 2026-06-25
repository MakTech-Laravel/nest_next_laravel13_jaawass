<?php

return [

  /*
  |--------------------------------------------------------------------------
  | Mail Queue
  |--------------------------------------------------------------------------
  */

  'queue' => env('MAILING_QUEUE', 'default'),

  /*
  |--------------------------------------------------------------------------
  | Per-job delay (seconds)
  |--------------------------------------------------------------------------
  |
  | Each dispatched mail job is delayed by (sequence - 1) * interval seconds
  | so bulk sends are spaced out by one second per job.
  |
  */

  'job_delay_seconds' => (int) env('MAILING_JOB_DELAY_SECONDS', 1),

  'dispatch_sequence_cache_key' => 'mailing:dispatch_sequence',

  /*
  |--------------------------------------------------------------------------
  | Templates
  |--------------------------------------------------------------------------
  |
  | Blade views used by the mailing service. "subject" is a translation key.
  | Set "markdown" to true for Laravel markdown mail views.
  |
  */

  'templates' => [
    'welcome' => [
      'view' => 'mail.welcome',
      'subject' => 'mail.welcome.subject',
      'markdown' => false,
    ],
    'password-reset-otp' => [
      'view' => 'mail.password-reset-otp',
      'subject' => 'mail.password_reset_otp.subject',
      'markdown' => true,
    ],
    'account-restore-otp' => [
      'view' => 'mail.account-restore-otp',
      'subject' => 'mail.account_restore_otp.subject',
      'markdown' => true,
    ],
    'manufacturer-additional-information' => [
      'view' => 'mail.manufacturer-additional-information',
      'subject' => 'mail.manufacturer_additional_information.subject',
      'markdown' => false,
    ],
    'manufacturer-admin-message' => [
      'view' => 'mail.manufacturer-admin-message',
      'subject' => 'mail.manufacturer_admin_message.subject',
      'markdown' => false,
    ],
    'supplier-report-received' => [
      'view' => 'mail.supplier-report-received',
      'subject' => 'mail.supplier_report_received.subject',
      'markdown' => false,
    ],
    'supplier-report-status-updated' => [
      'view' => 'mail.supplier-report-status-updated',
      'subject' => 'mail.supplier_report_status_updated.subject',
      'markdown' => false,
    ],
    'subscription-expiry-reminder' => [
      'view' => 'mail.subscription-expiry-reminder',
      'subject' => 'mail.subscription_expiry_reminder.subject',
      'markdown' => false,
    ],
    'subscription-expired' => [
      'view' => 'mail.subscription-expired',
      'subject' => 'mail.subscription_expired.subject',
      'markdown' => false,
    ],
    'subscription-created' => [
      'view' => 'mail.subscription-created',
      'subject' => 'mail.subscription_created.subject',
      'markdown' => false,
    ],
    'subscription-renewed' => [
      'view' => 'mail.subscription-renewed',
      'subject' => 'mail.subscription_renewed.subject',
      'markdown' => false,
    ],
    'admin-test-email' => [
      'view' => 'mail.admin-test-email',
      'subject' => 'mail.admin_test_email.subject',
      'markdown' => false,
    ],
  ],

];
