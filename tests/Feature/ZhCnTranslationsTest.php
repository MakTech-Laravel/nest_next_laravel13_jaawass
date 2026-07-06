<?php

test('zh_CN mail translations are available', function () {
    expect(__('mail.password_reset_otp.subject', [], 'zh_CN'))
        ->toBe('您的密码重置验证码');

    expect(__('mail.welcome.subject', [], 'zh_CN'))
        ->toBe('欢迎使用 SourceNest');
});

test('zh_CN api translations are available', function () {
    expect(__('api.unauthenticated', [], 'zh_CN'))
        ->toBe('未认证。');
});

test('zh_CN auth translations are available', function () {
    expect(__('auth.invalid_credentials', [], 'zh_CN'))
        ->toBe('凭据无效。');
});

test('zh_CN validation translations use attribute placeholder', function () {
    $message = __('validation.required', ['attribute' => 'email'], 'zh_CN');

    expect($message)->toContain('email');
    expect($message)->toContain('不能为空');
});
