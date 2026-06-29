<?php

namespace App\Http\Middleware;

use App\Services\Platform\PlatformSettingsService;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsureEmailVerified
{
    public function __construct(
        private readonly PlatformSettingsService $platformSettings,
    ) {}

    public function handle(Request $request, Closure $next): Response
    {
        if (! $this->platformSettings->requiresEmailVerification()) {
            return $next($request);
        }

        $user = $request->user();

        if ($user !== null && $user->hasVerifiedEmail()) {
            return $next($request);
        }

        return sendResponse(
            status: false,
            message: __('api.email_verification_required'),
            data: ['code' => 'email_not_verified'],
            statusCode: Response::HTTP_FORBIDDEN,
        );
    }
}
