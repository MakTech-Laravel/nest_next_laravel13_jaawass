<?php

namespace App\Enums;

enum MailTemplate: string
{
    case Welcome = 'welcome';
    case PasswordResetOtp = 'password-reset-otp';
    case AccountRestoreOtp = 'account-restore-otp';
    case EmailVerification = 'email-verification';
    case ManufacturerUnderReview = 'manufacturer-under-review';
    case BuyerRegistrationReminder = 'buyer-registration-reminder';
    case RfqSubmittedBuyer = 'rfq-submitted-buyer';
    case ManufacturerRegistrationReminder = 'manufacturer-registration-reminder';
    case ManufacturerActivationReminder = 'manufacturer-activation-reminder';
    case PaymentFailed = 'payment-failed';
    case PasswordChanged = 'password-changed';
    case AdminNewInquiry = 'admin-new-inquiry';
    case ManufacturerAdditionalInformation = 'manufacturer-additional-information';
    case AdminManufacturerAdditionalInformationResponse = 'admin-manufacturer-additional-information-response';
    case ManufacturerAdminMessage = 'manufacturer-admin-message';
    case SupplierReportReceived = 'supplier-report-received';
    case SupplierReportStatusUpdated = 'supplier-report-status-updated';
    case SupplierReportReceivedAdmin = 'supplier-report-received-admin';
    case SubscriptionExpiryReminder = 'subscription-expiry-reminder';
    case SubscriptionExpired = 'subscription-expired';
    case SubscriptionCreated = 'subscription-created';
    case SubscriptionRenewed = 'subscription-renewed';
    case ManufacturerOrderCreated = 'manufacturer-order-created';
    case OrderCreatedManufacturer = 'order-created-manufacturer';
    case OrderCreatedAdmin = 'order-created-admin';
    case OrderStatusUpdated = 'order-status-updated';
    case OrderInProductionBuyer = 'order-in-production-buyer';
    case OrderInProductionManufacturer = 'order-in-production-manufacturer';
    case OrderReadyForShipmentBuyer = 'order-ready-for-shipment-buyer';
    case OrderReadyForShipmentManufacturer = 'order-ready-for-shipment-manufacturer';
    case OrderShippedBuyer = 'order-shipped-buyer';
    case OrderShippedManufacturer = 'order-shipped-manufacturer';
    case OrderCompletedBuyer = 'order-completed-buyer';
    case OrderCompletedManufacturer = 'order-completed-manufacturer';
    case OrderCompletedAdmin = 'order-completed-admin';
    case OrderCancelledBuyer = 'order-cancelled-buyer';
    case OrderCancelledManufacturer = 'order-cancelled-manufacturer';
    case OrderCancelledAdmin = 'order-cancelled-admin';
    case OrderReviewInvite = 'order-review-invite';
    case ReviewApproved = 'review-approved';
    case NewProductReview = 'new-product-review';
    case RfqCreatedManufacturer = 'rfq-created-manufacturer';
    case RfqQuotedBuyer = 'rfq-quoted-buyer';
    case RfqStatusUpdated = 'rfq-status-updated';
    case ConversationMessageReceived = 'conversation-message-received';
    case SupportTicketCreated = 'support-ticket-created';
    case SupportTicketCreatedAdmin = 'support-ticket-created-admin';
    case SupportTicketReply = 'support-ticket-reply';
    case SupportTicketReplyAdmin = 'support-ticket-reply-admin';
    case SupportTicketResolved = 'support-ticket-resolved';
    case ManufacturerApproved = 'manufacturer-approved';
    case ManufacturerRejected = 'manufacturer-rejected';
    case ManufacturerRegisteredAdmin = 'manufacturer-registered-admin';

    public static function tryFromName(string $template): ?self
    {
        return self::tryFrom($template);
    }
}
