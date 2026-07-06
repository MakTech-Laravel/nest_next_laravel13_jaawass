<?php

return [

    'admin_cannot_modify' => '管理员账户无法停用或删除。',

    'deactivated' => '账户已停用。',

    'activated' => '账户已激活。',

    'no_scheduled_deletion' => '该账户没有计划删除。',

    'deletion_scheduled' => '您的账户将在 :days 天后被永久删除。永久删除后您将无法再访问此账户。在此 :days 天宽限期内，您可以恢复或取消删除。',

    'deletion_restore_login' => '您的账户已计划删除。您可以在宽限期结束前恢复账户。',

    'deletion_processing' => '您的账户删除正在处理中。您已无法恢复此账户。',

    'permanently_deleted' => '您的账户已被永久删除。',

    'suspended' => '您的账户已被封禁。请联系客服。',

    'restore_otp_sent' => '验证码已发送至您的邮箱。',

    'restore_otp_resend_wait' => '请稍后再请求新的验证码。',

    'restore_success' => '您的账户删除已取消。',

    'restore_invalid_otp' => '验证码无效或已过期。',

    'password_reset_otp_resend_wait' => '请稍后再请求新的密码重置验证码。',

    'password_reset_invalid_otp' => '密码重置验证码无效或已过期。',

    'email_verification_sent' => '验证码已发送至您的邮箱。',

    'email_verification_resend_wait' => '请稍后再请求新的验证码。',

    'email_verification_invalid_otp' => '验证码无效或已过期。',

    'email_verification_token_invalid' => '验证会话无效或已过期。',

    'email_verification_already_verified' => '此邮箱地址已验证。',

    'password_changed' => '您的密码已成功修改。',

    'two_factor' => [
        'enabled' => '双因素认证设置已开始。请扫描二维码并使用有效验证码确认。',
        'confirmed' => '双因素认证已启用。',
        'disabled' => '双因素认证已禁用。',
        'recovery_codes_regenerated' => '已生成新的恢复码。',
        'invalid_challenge' => '双因素认证会话无效或已过期。',
        'invalid_code' => '双因素认证验证码无效。',
        'already_enabled' => '双因素认证已启用。',
        'not_started' => '双因素认证尚未开始设置。',
        'not_enabled' => '双因素认证未启用。',
        'required_when_login' => '需要双因素认证。',
    ],

];
