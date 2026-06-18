<?php

namespace App\Http\Middleware;

use App\Services\Subscription\PlanEntitlementResolver;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsureActiveSubscription
{
    public function __construct(
        private readonly PlanEntitlementResolver $entitlementResolver,
    ) {}

    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user();

        if ($user === null || ! $this->entitlementResolver->for($user)->hasActiveSubscription()) {
            return sendResponse(
                status: false,
                message: __('subscription.no_active_subscription'),
                data: ['code' => 'no_active_subscription'],
                statusCode: Response::HTTP_FORBIDDEN,
            );
        }

        return $next($request);
    }
}
