<?php

namespace App\Providers;

use App\Services\Translation\GoogleTranslationService;
use App\Services\Translation\TranslationOrchestrator;
use Illuminate\Support\ServiceProvider;

class TranslationServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        // Merge so config/translation.php values are always available
        // even before vendor:publish is run
        $this->mergeConfigFrom(
            base_path('config/translation.php'),
            'translation'
        );

        // One TranslateClient instance per request lifecycle
        $this->app->singleton(GoogleTranslationService::class);

        // Orchestrator depends only on the service above
        $this->app->singleton(TranslationOrchestrator::class);
    }

    public function boot(): void
    {
        $this->publishes([
            base_path('config/translation.php') => config_path('translation.php'),
        ], 'translation-config');
    }
}
