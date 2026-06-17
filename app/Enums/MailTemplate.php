<?php

namespace App\Enums;

enum MailTemplate: string
{
    case Welcome = 'welcome';
    case PasswordResetOtp = 'password-reset-otp';
    case AccountRestoreOtp = 'account-restore-otp';

    public static function tryFromName(string $template): ?self
    {
        return self::tryFrom($template);
    }
}
