<?php

namespace App\Enums;

enum DashboardEventType: string
{
    case ProductViewed = 'product_viewed';
    case SupplierViewed = 'supplier_viewed';
    case ProductSaved = 'product_saved';
    case ProductUnsaved = 'product_unsaved';
    case SupplierSaved = 'supplier_saved';
    case SupplierUnsaved = 'supplier_unsaved';
    case ProductCompared = 'product_compared';
    case ProductCompareRemoved = 'product_compare_removed';
    case SupplierCompared = 'supplier_compared';
    case SupplierCompareRemoved = 'supplier_compare_removed';
    case RfqCreated = 'rfq_created';
    case RfqReplied = 'rfq_replied';
    case RfqQuoted = 'rfq_quoted';
    case MessageSent = 'message_sent';
    case OrderDelivered = 'order_delivered';
    case SupplierApproved = 'supplier_approved';
    case SupplierRejected = 'supplier_rejected';
    case SupplierSuspended = 'supplier_suspended';
    case ReviewSubmitted = 'review_submitted';

    /**
     * @return array<int, string>
     */
    public static function values(): array
    {
        return array_column(self::cases(), 'value');
    }
}
