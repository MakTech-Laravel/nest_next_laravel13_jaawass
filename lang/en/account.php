<?php

return [

    'admin_cannot_modify' => 'Admin accounts cannot be deactivated or deleted.',

    'deactivated' => 'Account deactivated.',

    'activated' => 'Account activated.',

    'no_scheduled_deletion' => 'No scheduled deletion for this account.',

    'deletion_scheduled' => 'Your account will be deleted permanently in :days days. After permanent deletion you will not be able to access this account anymore. You can restore or cancel deletion during this :days-day period.',

    'deletion_restore_login' => 'Your account is scheduled for deletion. You can restore your account before the grace period ends.',

    'deletion_processing' => 'Your account deletion is being finalized. You can no longer restore this account.',

    'permanently_deleted' => 'Your account was permanently deleted.',

    'suspended' => 'Your account has been suspended. Please contact support.',

    'restore_otp_sent' => 'A verification code has been sent to your email address.',

    'restore_otp_resend_wait' => 'Please wait before requesting a new verification code.',

    'restore_success' => 'Your account deletion has been cancelled.',

    'restore_invalid_otp' => 'The verification code is invalid or has expired.',

    'password_reset_otp_resend_wait' => 'Please wait before requesting a new password reset code.',

    'password_reset_invalid_otp' => 'The password reset code is invalid or has expired.',

    'password_changed' => 'Your password has been changed successfully.',

    'two_factor' => [
        'enabled' => 'Two-factor authentication setup started. Scan the QR code and confirm with a valid code.',
        'confirmed' => 'Two-factor authentication is now enabled.',
        'disabled' => 'Two-factor authentication has been disabled.',
        'recovery_codes_regenerated' => 'New recovery codes have been generated.',
        'invalid_challenge' => 'Invalid or expired two-factor session.',
        'invalid_code' => 'The two-factor authentication code was invalid.',
        'already_enabled' => 'Two-factor authentication is already enabled.',
        'not_started' => 'Two-factor authentication has not been started.',
        'not_enabled' => 'Two-factor authentication is not enabled.',
        'required_when_login' => 'Two-factor authentication required.',
    ],

];
