<?php

use App\Http\Middleware\EnsureEmailVerified;
use App\Http\Middleware\EnsureActiveSubscription;
use App\Http\Middleware\EnsureAdmin;
use App\Http\Middleware\EnsureBuyer;
use App\Http\Middleware\EnsureManufacturer;
use App\Http\Middleware\EnsureManufacturerPlanFeature;
use App\Http\Middleware\EnsurePlanFeature;
use App\Http\Middleware\SetLocale;
use App\Exceptions\Subscription\PlanEntitlementException;
use Illuminate\Console\Scheduling\Schedule;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response as HttpStatus;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        api: __DIR__.'/../routes/api.php',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
    )
    ->withBroadcasting(
        __DIR__.'/../routes/channels.php',
        ['middleware' => ['auth:api']],
    )
    ->withSchedule(function (Schedule $schedule): void {
        $schedule->command('users:finalize-scheduled-deletions')->dailyAt('00:00');

        $schedule->command('subscriptions:process-expired')
            ->hourly()
            ->timezone(config('app.timezone'));

        $schedule->command('subscriptions:send-expiry-reminders')
            ->dailyAt('09:00')
            ->timezone(config('app.timezone'));

        $schedule->command('registration:send-buyer-reminders')
            ->dailyAt('10:00')
            ->timezone(config('app.timezone'));

        $schedule->command('registration:send-manufacturer-reminders')
            ->dailyAt('10:15')
            ->timezone(config('app.timezone'));

        $schedule->command('manufacturer:send-first-payment-reminders')
            ->dailyAt('10:20')
            ->timezone(config('app.timezone'));

        $schedule->command('manufacturer:send-activation-reminders')
            ->dailyAt('10:30')
            ->timezone(config('app.timezone'));

        if (config('currency.fx_sync.enabled', false)) {
            $schedule->command('currency:sync-rates')
                ->dailyAt('00:00')
                ->timezone(config('app.timezone'));
        }
    })
    ->withMiddleware(function (Middleware $middleware): void {
        $middleware->trustProxies(at: '*', headers: Request::HEADER_X_FORWARDED_FOR | Request::HEADER_X_FORWARDED_PROTO);
        $middleware->prepend(SetLocale::class);

        $middleware->alias([
            'role.buyer' => EnsureBuyer::class,
            'role.manufacturer' => EnsureManufacturer::class,
            'role.admin' => EnsureAdmin::class,
            'email.verified' => EnsureEmailVerified::class,
            'subscription.active' => EnsureActiveSubscription::class,
            'plan.feature' => EnsurePlanFeature::class,
            'manufacturer.plan.feature' => EnsureManufacturerPlanFeature::class,
        ]);
    })
    ->withExceptions(function (Exceptions $exceptions): void {
        $exceptions->render(function (PlanEntitlementException $exception, Request $request) {
            if (! $request->is('api/*') && ! $request->expectsJson()) {
                return null;
            }

            return sendResponse(
                status: false,
                message: $exception->getMessage(),
                data: $exception->payload(),
                statusCode: $exception->statusCode,
            );
        });

        $exceptions->render(function (AuthenticationException $exception, Request $request) {
            if (! $request->is('api/*') && ! $request->expectsJson()) {
                return null;
            }

            return sendResponse(
                status: false,
                message: __('api.unauthenticated'),
                data: null,
                statusCode: HttpStatus::HTTP_UNAUTHORIZED
            );
        });

        $exceptions->render(function (ModelNotFoundException $exception, Request $request) {
            if (! $request->is('api/*') && ! $request->expectsJson()) {
                return null;
            }

            return sendResponse(
                status: false,
                message: __('common.not_found'),
                data: null,
                statusCode: HttpStatus::HTTP_NOT_FOUND
            );
        });
    })->create();
