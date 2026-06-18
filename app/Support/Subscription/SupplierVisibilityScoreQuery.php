<?php

namespace App\Support\Subscription;

use App\Enums\Api\V1\PlanFeatureKey;

class SupplierVisibilityScoreQuery
{
    /**
     * SQL expression that ranks suppliers by plan visibility features.
     */
    public static function selectExpression(string $userIdColumn = 'users.id'): string
    {
        $premium = PlanFeatureKey::PREMIUM_SEARCH_PLACEMENT->value;
        $maximum = PlanFeatureKey::MAXIMUM_BUYER_VISIBILITY->value;
        $priority = PlanFeatureKey::PRIORITY_SEARCH_VISIBILITY->value;
        $enhanced = PlanFeatureKey::ENHANCED_BUYER_VISIBILITY->value;
        $limited = PlanFeatureKey::LIMITED_BUYER_VISIBILITY->value;

        return "COALESCE((
            SELECT MAX(
                CASE features.key
                    WHEN '{$premium}' THEN 40
                    WHEN '{$maximum}' THEN 30
                    WHEN '{$priority}' THEN 20
                    WHEN '{$enhanced}' THEN 10
                    WHEN '{$limited}' THEN 5
                    ELSE 0
                END
            )
            FROM subscriptions
            INNER JOIN plan_feature ON plan_feature.plan_id = subscriptions.plan_id
            INNER JOIN features ON features.id = plan_feature.feature_id
            WHERE subscriptions.manufacturer_id = {$userIdColumn}
              AND subscriptions.status IN ('active', 'trialing')
              AND (subscriptions.ends_at IS NULL OR subscriptions.ends_at > NOW())
              AND (
                    (plan_feature.input_type = 'boolean' AND plan_feature.value = '1')
                    OR (plan_feature.input_type = 'text' AND plan_feature.value <> '')
              )
              AND features.key IN ('{$premium}', '{$maximum}', '{$priority}', '{$enhanced}', '{$limited}')
        ), 0)";
    }
}
