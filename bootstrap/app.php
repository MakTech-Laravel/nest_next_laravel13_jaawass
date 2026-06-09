<?php

use App\Http\Middleware\EnsureAdmin;
use App\Http\Middleware\EnsureBuyer;
use App\Http\Middleware\EnsureManufacturer;
use App\Http\Middleware\SetLocale;
use Illuminate\Console\Scheduling\Schedule;
use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
use Illuminate\Http\Request;

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
        ]);
    })
    ->withExceptions(function (Exceptions $exceptions): void {
        //
    })->create();
