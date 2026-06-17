<?php

namespace App\Providers;

use Illuminate\Support\ServiceProvider;
use Mailgun\Mailgun;

class MailingServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->app->singleton(Mailgun::class, function () {
            $apiKey = (string) config('services.mailgun.secret');

            if ($apiKey === '') {
                return null;
            }

            return Mailgun::create(
                $apiKey,
                (string) config('services.mailgun.endpoint', 'https://api.mailgun.net'),
            );
        });
    }
}
