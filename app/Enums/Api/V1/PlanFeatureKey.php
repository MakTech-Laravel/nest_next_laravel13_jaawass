<?php

namespace App\Enums\Api\V1;

enum PlanFeatureKey: string
{
    case PRODUCT_LIMIT = 'product_limit';
    case COMPANY_PROFILE = 'company_profile';
    case INTERNAL_MESSAGING = 'internal_messaging';
    case INQUIRY_RFQ_INBOX = 'inquiry_rfq_inbox';
    case CATALOG_UPLOAD = 'catalog_upload';
    case BASIC_ANALYTICS = 'basic_analytics';
    case ADVANCED_ANALYTICS = 'advanced_analytics';
    case CERTIFICATIONS_SECTION = 'certifications_section';
    case EXPORT_MARKETS_SECTION = 'export_markets_section';
    case LIMITED_BUYER_VISIBILITY = 'limited_buyer_visibility';
    case ENHANCED_BUYER_VISIBILITY = 'enhanced_buyer_visibility';
    case MAXIMUM_BUYER_VISIBILITY = 'maximum_buyer_visibility';
    case PRIORITY_SEARCH_VISIBILITY = 'priority_search_visibility';
    case PREMIUM_SEARCH_PLACEMENT = 'premium_search_placement';
    case FEATURED_SUPPLIER_BADGE = 'featured_supplier_badge';
    case TEAM_USERS_LIMIT = 'team_users_limit';
    case UNLIMITED_TEAM_USERS = 'unlimited_team_users';
    case HIGHER_CHANCE_RECEIVE_RFQ = 'higher_chance_receive_rfq';
    case HIGHER_PRIORITY_BUYER_INQUIRIES = 'higher_priority_buyer_inquiries';
    case PRIORITY_SUPPORT = 'priority_support';

    public function label(): string
    {
        return __('subscription.features.'.$this->value);
    }
}
