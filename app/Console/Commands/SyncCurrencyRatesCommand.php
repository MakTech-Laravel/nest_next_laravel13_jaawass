<?php

namespace App\Console\Commands;

use App\Models\Currency;
use App\Services\Currency\CurrencyRateLedger;
use App\Services\Currency\ExchangeRateService;
use App\Services\Currency\FrankfurterExchangeRateClient;
use Carbon\CarbonImmutable;
use Illuminate\Console\Command;
use Illuminate\Support\Str;
use Throwable;

class SyncCurrencyRatesCommand extends Command
{
    protected $signature = 'currency:sync-rates';

    protected $description = 'Fetch latest FX rates from Frankfurter and append currency_rates rows (when enabled).';

    public function handle(
        FrankfurterExchangeRateClient $client,
        CurrencyRateLedger $ledger,
        ExchangeRateService $exchangeRateService,
    ): int {
        if (! config('currency.fx_sync.enabled', false)) {
            $this->info('Currency FX sync is disabled (CURRENCY_FX_SYNC_ENABLED).');

            return self::SUCCESS;
        }

        $base = Currency::base();
        $quotes = collect(config('currency.enabled_codes', []))
            ->map(fn (string $c) => strtoupper($c))
            ->reject(fn (string $c) => $c === $base->code)
            ->values()
            ->all();

        if ($quotes === []) {
            $this->warn('No quote currencies configured.');

            return self::SUCCESS;
        }

        try {
            $payload = $client->fetchLatest($base->code, $quotes);
        } catch (Throwable $e) {
            $this->error('Failed to fetch rates: '.$e->getMessage());

            return self::FAILURE;
        }

        $effectiveAt = CarbonImmutable::parse($payload['date'].' 00:00:00', 'UTC');
        $batchId = (string) Str::uuid();
        $inserted = 0;

        foreach ($payload['rates'] as $code => $numericRate) {
            $quote = Currency::query()->where('code', strtoupper($code))->active()->first();
            if ($quote === null) {
                continue;
            }

            $rate = (string) $numericRate;
            $row = $ledger->recordApi(
                $base->id,
                $quote->id,
                $rate,
                $effectiveAt,
                $batchId,
            );
            if ($row !== null) {
                $inserted++;
            }
        }

        $exchangeRateService->flushCacheForAllConfiguredPairs();

        $this->info("Currency sync batch {$batchId}: {$inserted} new row(s).");

        return self::SUCCESS;
    }
}
