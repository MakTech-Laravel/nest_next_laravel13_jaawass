<?php

namespace App\Enums;

enum MailTemplate: string
{
    case Welcome = 'welcome';
    case PasswordResetOtp = 'password-reset-otp';
    case AccountRestoreOtp = 'account-restore-otp';
    case ManufacturerAdditionalInformation = 'manufacturer-additional-information';
    case AdminManufacturerAdditionalInformationResponse = 'admin-manufacturer-additional-information-response';
    case ManufacturerAdminMessage = 'manufacturer-admin-message';
    case SupplierReportReceived = 'supplier-report-received';
    case SupplierReportStatusUpdated = 'supplier-report-status-updated';
    case SubscriptionExpiryReminder = 'subscription-expiry-reminder';
    case SubscriptionExpired = 'subscription-expired';
    case SubscriptionCreated = 'subscription-created';
    case SubscriptionRenewed = 'subscription-renewed';
    case ManufacturerOrderCreated = 'manufacturer-order-created';
    case EmailVerification = 'email-verification';

    public static function tryFromName(string $template): ?self
    {
        return self::tryFrom($template);
    }
}
