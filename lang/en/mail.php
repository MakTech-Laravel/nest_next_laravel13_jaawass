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

];
