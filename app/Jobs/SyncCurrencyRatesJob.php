<?php

namespace App\Jobs;

use App\Console\Commands\SyncCurrencyRatesCommand;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Log;

/**
 * Runs {@see SyncCurrencyRatesCommand} (Frankfurter → currency_rates).
 * Dispatch for async processing; the app scheduler also runs the same command at midnight.
 */
class SyncCurrencyRatesJob implements ShouldQueue
{
    use Queueable;

    public int $tries = 3;

    public int $timeout = 120;

    public function handle(): void
    {
        if (! config('currency.fx_sync.enabled', false)) {
            Log::info('currency.sync_job.skipped', ['reason' => 'CURRENCY_FX_SYNC_ENABLED is false']);

            return;
        }

        $exit = Artisan::call('currency:sync-rates');

        if ($exit !== 0) {
            Log::warning('currency.sync_job.command_failed', ['exit_code' => $exit]);

            throw new \RuntimeException("currency:sync-rates exited with code {$exit}");
        }
    }
}
