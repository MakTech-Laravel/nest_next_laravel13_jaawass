<?php

namespace App\Http\Middleware;

use App\Enums\UserManuFactureStatus;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Restricts manufacturer routes to approved accounts only.
 * Pending manufacturers may still log in and use additional-information review routes.
 */
class EnsureApprovedManufacturer
{
    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user();

        if ($user === null || ! $user->role->isManufacturer()) {
            return $next($request);
        }

        $status = UserManuFactureStatus::normalizedForManufacturer($user->manufacture_status);

        if ($status->allowsApiLogin()) {
            return $next($request);
        }

        return sendResponse(
            status: false,
            message: __('auth.manufacturer.login-pending'),
            data: ['code' => 'manufacturer_pending_approval'],
            statusCode: Response::HTTP_FORBIDDEN,
        );
    }
}
