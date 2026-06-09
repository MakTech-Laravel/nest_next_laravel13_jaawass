<?php

namespace App\Providers;

use App\Contracts\Currency\CurrencyContextInterface;
use App\Services\Currency\CurrencyContext;
use App\Services\Currency\CurrencyDisplayResolver;
use Illuminate\Cache\RateLimiting\Limit;
use Illuminate\Http\Request;
use Illuminate\Log\Context\Repository;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\Context;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\ServiceProvider;
use Illuminate\Support\Str;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        $this->app->scoped(CurrencyContextInterface::class, function ($app) {
            return new CurrencyContext($app->make(CurrencyDisplayResolver::class));
        });
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        $this->validateCurrencyConfig();

        Context::dehydrating(function (Repository $context): void {
            $context->addHidden('locale', Config::get('app.locale'));
        });

        Context::hydrated(function (Repository $context): void {
            if ($context->hasHidden('locale')) {
                Config::set('app.locale', $context->getHidden('locale'));
            }
        });

        RateLimiter::for('api-login', function (Request $request) {
            $usernameField = config('fortify.username', 'email');

            $throttleKey = Str::transliterate(
                Str::lower((string) $request->input($usernameField)).'|'.$request->ip()
            );

            return Limit::perMinute(5)->by($throttleKey);
        });

        RateLimiter::for('api-register', function (Request $request) {
            $throttleKey = Str::transliterate(
                Str::lower((string) $request->input('email')).'|'.$request->ip()
            );

            return Limit::perMinute(3)->by($throttleKey);
        });

        RateLimiter::for('api-password-reset', function (Request $request) {
            $throttleKey = Str::transliterate(
                Str::lower((string) $request->input('email')).'|'.$request->ip()
            );

            return Limit::perMinute(3)->by($throttleKey);
        });

        RateLimiter::for('api-password-reset-verify', function (Request $request) {
            $throttleKey = Str::transliterate(
                Str::lower((string) $request->input('email')).'|'.$request->ip()
            );

            return Limit::perMinute(10)->by($throttleKey);
        });

        RateLimiter::for('account-restore-otp-request', function (Request $request) {
            $throttleKey = Str::transliterate(
                Str::lower((string) $request->input('email')).'|'.$request->ip()
            );

            return Limit::perMinute(5)->by($throttleKey);
        });

        RateLimiter::for('account-restore-otp-verify', function (Request $request) {
            $throttleKey = Str::transliterate(
                Str::lower((string) $request->input('email')).'|'.$request->ip()
            );

            return Limit::perMinute(10)->by($throttleKey);
        });

        RateLimiter::for('currency-admin', function (Request $request) {
            return Limit::perMinute(60)->by((string) $request->user()?->getAuthIdentifier().'|'.$request->ip());
        });
    }

    private function validateCurrencyConfig(): void
    {
        $base = strtoupper((string) config('currency.base_currency', 'USD'));
        $enabled = array_map('strtoupper', config('currency.enabled_codes', []));

        if ($enabled !== [] && ! in_array($base, $enabled, true)) {
            throw new \LogicException('config currency.base_currency must be listed in currency.enabled_codes.');
        }
    }
}
