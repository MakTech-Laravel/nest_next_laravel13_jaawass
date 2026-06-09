<?php

namespace App\Http\Controllers\Api\V1\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Api\V1\Admin\IndexCurrencyRateHistoryRequest;
use App\Http\Requests\Api\V1\Admin\StoreCurrencyRateRequest;
use App\Http\Requests\Api\V1\Admin\UpdateSeededCurrencyRequest;
use App\Http\Resources\Api\V1\CurrencyResource;
use App\Models\Currency;
use App\Models\CurrencyExchangeRate;
use App\Services\Currency\CurrencyRateLedger;
use App\Services\Currency\ExchangeRateService;
use Carbon\CarbonImmutable;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Artisan;
use Symfony\Component\HttpFoundation\Response as HttpStatus;

class CurrencyAdminController extends Controller
{
    public function indexCurrencies(): JsonResponse
    {
        $currencies = Currency::query()
            ->enabledInConfig()
            ->ordered()
            ->get();

        return sendResponse(
            status: true,
            message: __('common.success'),
            data: CurrencyResource::collection($currencies),
            statusCode: HttpStatus::HTTP_OK
        );
    }

    public function updateCurrency(UpdateSeededCurrencyRequest $request, Currency $currency): JsonResponse
    {
        $enabled = array_map('strtoupper', config('currency.enabled_codes', []));
        if (! in_array(strtoupper($currency->code), $enabled, true)) {
            return sendResponse(
                status: false,
                message: __('common.not_found'),
                data: null,
                statusCode: HttpStatus::HTTP_NOT_FOUND
            );
        }

        if (
            strtoupper($currency->code) === strtoupper((string) config('currency.base_currency', 'USD'))
            && $request->has('is_active')
            && ! $request->boolean('is_active')
        ) {
            return sendResponse(
                status: false,
                message: __('The base currency cannot be deactivated.'),
                data: null,
                statusCode: HttpStatus::HTTP_UNPROCESSABLE_ENTITY
            );
        }

        $currency->fill($request->validated());
        $currency->save();

        return sendResponse(
            status: true,
            message: __('common.updated'),
            data: new CurrencyResource($currency->fresh()),
            statusCode: HttpStatus::HTTP_OK
        );
    }

    public function storeRate(StoreCurrencyRateRequest $request, CurrencyRateLedger $ledger): JsonResponse
    {
        $base = Currency::base();
        $quote = Currency::query()->where('code', strtoupper($request->validated('quote_currency_code')))->firstOrFail();

        $effectiveAt = $request->validated('effective_at')
            ? CarbonImmutable::parse($request->validated('effective_at'), 'UTC')
            : now('UTC');

        $rate = (string) $request->validated('rate');

        $row = $ledger->recordManual(
            $base->id,
            $quote->id,
            $rate,
            $effectiveAt,
            (int) $request->user()->getAuthIdentifier(),
        );

        return sendResponse(
            status: true,
            message: __('common.created'),
            data: [
                'id' => $row->id,
                'base' => $base->code,
                'quote' => $quote->code,
                'rate' => (string) $row->rate,
                'effective_at' => $row->effective_at->toIso8601String(),
                'source' => $row->source->value,
            ],
            statusCode: HttpStatus::HTTP_CREATED
        );
    }

    public function ratesCurrent(ExchangeRateService $exchangeRateService): JsonResponse
    {
        $base = Currency::base();
        $rows = [];

        foreach (config('currency.enabled_codes', []) as $code) {
            $code = strtoupper($code);
            if ($code === strtoupper($base->code)) {
                continue;
            }

            $quote = Currency::query()->where('code', $code)->first();
            if ($quote === null) {
                continue;
            }

            $latest = $exchangeRateService->latestRate($base, $quote);
            $rows[] = [
                'base' => $base->code,
                'quote' => $quote->code,
                'rate' => $latest ? (string) $latest->rate : null,
                'effective_at' => $latest?->effective_at?->toIso8601String(),
                'source' => $latest?->source?->value,
            ];
        }

        return sendResponse(
            status: true,
            message: __('common.success'),
            data: $rows,
            statusCode: HttpStatus::HTTP_OK
        );
    }

    public function ratesHistory(IndexCurrencyRateHistoryRequest $request): JsonResponse
    {
        $perPage = (int) ($request->validated('per_page') ?? 20);

        $query = CurrencyExchangeRate::query()
            ->with(['baseCurrency', 'quoteCurrency'])
            ->orderByDesc('effective_at')
            ->orderByDesc('id');

        if ($request->filled('base_code')) {
            $baseId = Currency::query()->where('code', strtoupper($request->validated('base_code')))->value('id');
            if ($baseId) {
                $query->where('base_currency_id', $baseId);
            }
        }

        if ($request->filled('quote_code')) {
            $quoteId = Currency::query()->where('code', strtoupper($request->validated('quote_code')))->value('id');
            if ($quoteId) {
                $query->where('quote_currency_id', $quoteId);
            }
        }

        if ($request->filled('source')) {
            $query->where('source', $request->validated('source'));
        }

        if ($request->filled('sync_batch_id')) {
            $query->where('sync_batch_id', $request->validated('sync_batch_id'));
        }

        if ($request->filled('effective_from')) {
            $query->where('effective_at', '>=', $request->validated('effective_from'));
        }

        if ($request->filled('effective_to')) {
            $query->where('effective_at', '<=', $request->validated('effective_to'));
        }

        $paginator = $query->paginate($perPage);

        $data = collect($paginator->items())->map(static function (CurrencyExchangeRate $rate) {
            return [
                'id' => $rate->id,
                'base' => $rate->baseCurrency?->code,
                'quote' => $rate->quoteCurrency?->code,
                'rate' => (string) $rate->rate,
                'effective_at' => $rate->effective_at->toIso8601String(),
                'source' => $rate->source->value,
                'sync_batch_id' => $rate->sync_batch_id,
                'created_at' => $rate->created_at?->toIso8601String(),
            ];
        });

        return sendResponse(
            status: true,
            message: __('common.success'),
            data: [
                'data' => $data,
                'meta' => [
                    'current_page' => $paginator->currentPage(),
                    'last_page' => $paginator->lastPage(),
                    'per_page' => $paginator->perPage(),
                    'total' => $paginator->total(),
                ],
            ],
            statusCode: HttpStatus::HTTP_OK
        );
    }

    public function syncRates(): JsonResponse
    {
        if (! config('currency.fx_sync.enabled', false)) {
            return sendResponse(
                status: false,
                message: __('Currency sync is disabled.'),
                data: null,
                statusCode: HttpStatus::HTTP_UNPROCESSABLE_ENTITY
            );
        }

        $exit = Artisan::call('currency:sync-rates');

        return sendResponse(
            status: $exit === 0,
            message: $exit === 0 ? __('common.success') : __('Currency sync failed.'),
            data: ['output' => Artisan::output()],
            statusCode: $exit === 0 ? HttpStatus::HTTP_OK : HttpStatus::HTTP_INTERNAL_SERVER_ERROR
        );
    }
}
