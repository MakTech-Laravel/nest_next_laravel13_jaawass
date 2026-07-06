<?php

namespace App\Http\Middleware;

use App\Services\Subscription\PlanEntitlementResolver;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsurePlanFeature
{
    public function __construct(
        private readonly PlanEntitlementResolver $entitlementResolver,
    ) {}

    /**
     * @param  string  ...$features  Feature keys or pipe-separated alternatives (any match passes).
     */
    public function handle(Request $request, Closure $next, string ...$features): Response
    {
        $entitlements = $this->entitlementResolver->for($request->user());

        if (! $entitlements->hasActiveSubscription()) {
            return sendResponse(
                status: false,
                message: __('subscription.no_active_subscription'),
                data: ['code' => 'no_active_subscription'],
                statusCode: Response::HTTP_FORBIDDEN,
            );
        }

       
    
        foreach ($features as $feature) {
            if (str_contains($feature, '|')) {
                $alternatives = array_filter(explode('|', $feature));

                if (! $entitlements->hasAnyFeature(...$alternatives)) {
                    return $this->denyResponse($alternatives[0]);
                }

                continue;
            }

            if (! $entitlements->hasFeature($feature)) {
                return $this->denyResponse($feature);
            }
        }

        return $next($request);
    }

    private function denyResponse(string $featureKey): Response
    {
        return sendResponse(
            status: false,
            message: __('subscription.feature_not_available', [
                'feature' => __('subscription.features.'.$featureKey),
            ]),
            data: [
                'code' => 'feature_not_available',
                'feature' => $featureKey,
            ],
            statusCode: Response::HTTP_FORBIDDEN,
        );
    }
}
