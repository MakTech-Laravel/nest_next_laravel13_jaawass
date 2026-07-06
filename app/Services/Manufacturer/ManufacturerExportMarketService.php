<?php

namespace App\Services\Manufacturer;

use App\Enums\ExportMarketPotential;
use App\Models\ManufacturerExportMarket;
use App\Models\ManufacturerExportMarketCountry;
use App\Models\Order;
use App\Models\RfqSubmission;
use App\Models\User;
use App\Services\Dashboard\BuildsDashboardMetrics;
use App\Support\Countries\CountryExportRegionResolver;
use App\Support\Countries\CountryMapCatalog;
use App\Support\ExportMarkets\ExportMarketCatalog;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Pagination\LengthAwarePaginator as Paginator;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class ManufacturerExportMarketService
{
    use BuildsDashboardMetrics;

    /**
     * @return array<string, mixed>
     */
    public function overview(User $manufacturer): array
    {
        $this->importLegacyMarketsIfNeeded($manufacturer);

        $markets = $this->loadMarkets($manufacturer);
        $selectedCodes = $this->selectedCountryCodes($markets);
        $range = $this->defaultStatsRange();

        $inquiriesCurrent = $this->countMarketInquiries(
            (int) $manufacturer->id,
            $selectedCodes,
            $range['current_start'],
            $range['current_end'],
        );
        $inquiriesPrevious = $this->countMarketInquiries(
            (int) $manufacturer->id,
            $selectedCodes,
            $range['previous_start'],
            $range['previous_end'],
        );

        $ordersCurrent = $this->countMarketOrders(
            (int) $manufacturer->id,
            $selectedCodes,
            $range['current_start'],
            $range['current_end'],
        );
        $ordersPrevious = $this->countMarketOrders(
            (int) $manufacturer->id,
            $selectedCodes,
            $range['previous_start'],
            $range['previous_end'],
        );

        $totalInquiries = $this->countMarketInquiries((int) $manufacturer->id, $selectedCodes);
        $totalOrders = $this->countMarketOrders((int) $manufacturer->id, $selectedCodes);

        $activityCurrent = $inquiriesCurrent + $ordersCurrent;
        $activityPrevious = $inquiriesPrevious + $ordersPrevious;
        $growth = $this->metricWithTrend($activityCurrent, $activityPrevious);

        return [
            'stats' => [
                'active_markets' => count($selectedCodes),
                'total_inquiries' => $totalInquiries,
                'total_orders' => $totalOrders,
                'growth_rate' => [
                    'value' => $growth['change'],
                    'raw_value' => $this->percentChange($activityCurrent, $activityPrevious),
                    'trend' => $growth['trend'],
                ],
            ],
            'active_regions' => $markets->map(function (ManufacturerExportMarket $market): array {
                $countryNames = $market->countries
                    ->pluck('country_name')
                    ->values()
                    ->all();

                $codes = $market->countries
                    ->pluck('country_code')
                    ->map(fn (string $code): string => strtoupper($code))
                    ->values()
                    ->all();

                return [
                    'id' => $market->id,
                    'region' => $market->region,
                    'countries' => $countryNames,
                    'country_codes' => $codes,
                    'inquiries' => $this->countMarketInquiries((int) $market->user_id, $codes),
                    'orders' => $this->countMarketOrders((int) $market->user_id, $codes),
                ];
            })->values()->all(),
            'suggestions' => $this->suggestions($manufacturer, $markets),
            'meta' => [
                'regions' => ExportMarketCatalog::regions(),
                'geographic_regions' => CountryMapCatalog::groups(),
            ],
        ];
    }

    public function countries(
        User $manufacturer,
        ?string $search,
        ?string $geographicRegion,
        int $perPage,
        int $page,
    ): LengthAwarePaginator {
        $this->importLegacyMarketsIfNeeded($manufacturer);

        $selectedCodes = $this->selectedCountryCodes($this->loadMarkets($manufacturer));

        $filtered = CountryMapCatalog::exportCountries()
            ->filter(function (array $country) use ($search, $geographicRegion): bool {
                if ($geographicRegion !== null && $country['geographic_region'] !== $geographicRegion) {
                    return false;
                }

                if ($search === null) {
                    return true;
                }

                $needle = strtolower($search);

                return str_contains(strtolower($country['name']), $needle)
                    || str_contains(strtolower($country['code']), $needle);
            })
            ->values();

        $total = $filtered->count();
        $items = $filtered
            ->slice(($page - 1) * $perPage, $perPage)
            ->map(fn (array $country): array => [
                'code' => $country['code'],
                'name' => $country['name'],
                'export_market_region' => $country['export_market_region'],
                'geographic_region' => $country['geographic_region'],
                'is_selected' => in_array(strtoupper($country['code']), $selectedCodes, true),
            ])
            ->values()
            ->all();

        return new Paginator(
            items: $items,
            total: $total,
            perPage: $perPage,
            currentPage: $page,
        );
    }

    /**
     * @param  array<int, string>  $countryCodes
     * @return array<string, mixed>
     */
    public function storeRegion(User $manufacturer, string $region, array $countryCodes): array
    {
        if (! ExportMarketCatalog::isValidRegion($region)) {
            throw ValidationException::withMessages([
                'region' => [__('validation.in', ['attribute' => 'region'])],
            ]);
        }

        $normalizedCodes = $this->normalizeCountryCodes($countryCodes, $region);

        if ($normalizedCodes === []) {
            throw ValidationException::withMessages([
                'country_codes' => [__('validation.required', ['attribute' => 'country codes'])],
            ]);
        }

        return DB::transaction(function () use ($manufacturer, $region, $normalizedCodes): array {
            $market = ManufacturerExportMarket::query()->firstOrCreate([
                'user_id' => $manufacturer->id,
                'region' => $region,
            ]);

            $this->syncMarketCountries($market, $normalizedCodes);
            $this->syncCompanyExportMarkets($manufacturer);

            return $this->regionPayload($market->fresh(['countries']));
        });
    }

    /**
     * @param  array<int, string>  $countryCodes
     * @return array<string, mixed>
     */
    public function updateRegion(User $manufacturer, ManufacturerExportMarket $market, array $countryCodes): array
    {
        $this->assertMarketOwner($manufacturer, $market);

        $normalizedCodes = $this->normalizeCountryCodes($countryCodes, $market->region);

        if ($normalizedCodes === []) {
            throw ValidationException::withMessages([
                'country_codes' => [__('validation.required', ['attribute' => 'country codes'])],
            ]);
        }

        return DB::transaction(function () use ($manufacturer, $market, $normalizedCodes): array {
            $this->syncMarketCountries($market, $normalizedCodes);
            $this->syncCompanyExportMarkets($manufacturer);

            return $this->regionPayload($market->fresh(['countries']));
        });
    }

    /**
     * @return array<string, mixed>
     */
    public function destroyRegion(User $manufacturer, ManufacturerExportMarket $market): array
    {
        $this->assertMarketOwner($manufacturer, $market);

        return DB::transaction(function () use ($manufacturer, $market): array {
            $payload = $this->regionPayload($market);
            $market->delete();
            $this->syncCompanyExportMarkets($manufacturer);

            return $payload;
        });
    }

    /**
     * @param  array<int, string>  $countryCodes
     * @return array<string, mixed>
     */
    public function syncCountries(User $manufacturer, array $countryCodes): array
    {
        $normalizedCodes = collect($countryCodes)
            ->map(function (string $code): ?string {
                $country = CountryExportRegionResolver::toExportCountry($code);

                return $country['code'] ?? null;
            })
            ->filter()
            ->unique()
            ->values()
            ->all();

        return DB::transaction(function () use ($manufacturer, $normalizedCodes): array {
            $grouped = CountryMapCatalog::groupByExportRegion($normalizedCodes);

            ManufacturerExportMarket::query()
                ->where('user_id', $manufacturer->id)
                ->each(function (ManufacturerExportMarket $market) use ($grouped): void {
                    $countries = $grouped[$market->region] ?? [];

                    if ($countries === []) {
                        $market->delete();

                        return;
                    }

                    $this->syncMarketCountries(
                        $market,
                        collect($countries)->pluck('code')->all(),
                    );

                    unset($grouped[$market->region]);
                });

            foreach ($grouped as $region => $countries) {
                $market = ManufacturerExportMarket::query()->create([
                    'user_id' => $manufacturer->id,
                    'region' => $region,
                ]);

                $this->syncMarketCountries(
                    $market,
                    collect($countries)->pluck('code')->all(),
                );
            }

            $this->syncCompanyExportMarkets($manufacturer);

            return $this->overview($manufacturer);
        });
    }

    /**
     * @param  array<int, string>  $regionNames
     */
    public function syncFromProfileRegions(User $manufacturer, array $regionNames): void
    {
        $validRegions = collect($regionNames)
            ->filter(fn (string $region): bool => ExportMarketCatalog::isValidRegion($region))
            ->unique()
            ->values();

        DB::transaction(function () use ($manufacturer, $validRegions): void {
            $existing = ManufacturerExportMarket::query()
                ->where('user_id', $manufacturer->id)
                ->get()
                ->keyBy('region');

            foreach ($validRegions as $region) {
                if (! $existing->has($region)) {
                    ManufacturerExportMarket::query()->create([
                        'user_id' => $manufacturer->id,
                        'region' => $region,
                    ]);
                }
            }

            ManufacturerExportMarket::query()
                ->where('user_id', $manufacturer->id)
                ->whereNotIn('region', $validRegions->all())
                ->delete();

            $this->syncCompanyExportMarkets($manufacturer);
        });
    }

    private function importLegacyMarketsIfNeeded(User $manufacturer): void
    {
        $hasMarkets = ManufacturerExportMarket::query()
            ->where('user_id', $manufacturer->id)
            ->exists();

        if ($hasMarkets) {
            return;
        }

        $company = $manufacturer->company;

        if ($company === null || empty($company->export_markets)) {
            return;
        }

        $regions = json_decode($company->export_markets, true);

        if (! is_array($regions) || $regions === []) {
            return;
        }

        $this->syncFromProfileRegions($manufacturer, $regions);
    }

    /**
     * @return Collection<int, ManufacturerExportMarket>
     */
    private function loadMarkets(User $manufacturer): Collection
    {
        return ManufacturerExportMarket::query()
            ->where('user_id', $manufacturer->id)
            ->with('countries')
            ->orderBy('region')
            ->get();
    }

    /**
     * @param  Collection<int, ManufacturerExportMarket>  $markets
     * @return array<int, string>
     */
    private function selectedCountryCodes(Collection $markets): array
    {
        return $markets
            ->flatMap(fn (ManufacturerExportMarket $market) => $market->countries->pluck('country_code'))
            ->map(fn (string $code): string => strtoupper($code))
            ->unique()
            ->values()
            ->all();
    }

    /**
     * @param  array<int, string>  $countryCodes
     * @return array<int, string>
     */
    private function normalizeCountryCodes(array $countryCodes, string $region): array
    {
        return collect($countryCodes)
            ->map(function (string $code) use ($region): ?string {
                $country = CountryExportRegionResolver::toExportCountry($code);

                if ($country === null || $country['export_market_region'] !== $region) {
                    return null;
                }

                return $country['code'];
            })
            ->filter()
            ->unique()
            ->values()
            ->all();
    }

    /**
     * @param  array<int, string>  $countryCodes
     */
    private function syncMarketCountries(ManufacturerExportMarket $market, array $countryCodes): void
    {
        $market->countries()->delete();

        foreach ($countryCodes as $code) {
            $country = CountryExportRegionResolver::toExportCountry($code);

            if ($country === null) {
                continue;
            }

            ManufacturerExportMarketCountry::query()->create([
                'manufacturer_export_market_id' => $market->id,
                'country_code' => $country['code'],
                'country_name' => $country['name'],
            ]);
        }
    }

    private function syncCompanyExportMarkets(User $manufacturer): void
    {
        $company = $manufacturer->company;

        if ($company === null) {
            return;
        }

        $regions = ManufacturerExportMarket::query()
            ->where('user_id', $manufacturer->id)
            ->orderBy('region')
            ->pluck('region')
            ->unique()
            ->values()
            ->all();

        $company->update([
            'export_markets' => json_encode($regions),
        ]);
    }

    private function assertMarketOwner(User $manufacturer, ManufacturerExportMarket $market): void
    {
        if ((int) $market->user_id !== (int) $manufacturer->id) {
            abort(404);
        }
    }

    /**
     * @return array<string, mixed>
     */
    private function regionPayload(ManufacturerExportMarket $market): array
    {
        $codes = $market->countries
            ->pluck('country_code')
            ->map(fn (string $code): string => strtoupper($code))
            ->sort()
            ->values()
            ->all();

        return [
            'id' => $market->id,
            'region' => $market->region,
            'countries' => $market->countries->pluck('country_name')->values()->all(),
            'country_codes' => $codes,
            'inquiries' => $this->countMarketInquiries((int) $market->user_id, $codes),
            'orders' => $this->countMarketOrders((int) $market->user_id, $codes),
        ];
    }

    /**
     * @param  Collection<int, ManufacturerExportMarket>  $markets
     * @return array<int, array<string, mixed>>
     */
    private function suggestions(User $manufacturer, Collection $markets): array
    {
        $activeRegions = $markets->pluck('region')->all();
        $inactiveRegions = collect(ExportMarketCatalog::regions())
            ->reject(fn (string $region): bool => in_array($region, $activeRegions, true))
            ->values();

        if ($inactiveRegions->isEmpty()) {
            return [];
        }

        $scores = $inactiveRegions->map(function (string $region) use ($manufacturer): array {
            $countryNames = CountryMapCatalog::exportCountries()
                ->filter(fn (array $country): bool => $country['export_market_region'] === $region)
                ->pluck('name')
                ->all();

            $demand = $this->countPlatformInquiriesForCountries($countryNames, now()->subDays(90), now());

            return [
                'region' => $region,
                'score' => $demand,
            ];
        })->sortByDesc('score')->values();

        return $scores
            ->take(3)
            ->map(function (array $item) use ($manufacturer): array {
                $potential = $item['score'] >= 5
                    ? ExportMarketPotential::High
                    : ExportMarketPotential::Medium;

                return [
                    'region' => $item['region'],
                    'reason' => $this->suggestionReason($manufacturer, $item['region'], $potential),
                    'potential' => $potential->label(),
                    'potential_key' => $potential->value,
                ];
            })
            ->all();
    }

    private function suggestionReason(User $manufacturer, string $region, ExportMarketPotential $potential): string
    {
        $manufacturer->loadMissing('company.industries');

        $industryNames = $manufacturer->company?->industries
            ->pluck('name')
            ->filter()
            ->take(2)
            ->implode(', ');

        if ($industryNames !== '') {
            return match ($potential) {
                ExportMarketPotential::High => "High demand for {$industryNames} products in {$region}.",
                ExportMarketPotential::Medium => "Growing opportunities for {$industryNames} in {$region}.",
                ExportMarketPotential::Low => "Emerging demand for {$industryNames} in {$region}.",
            };
        }

        return match ($potential) {
            ExportMarketPotential::High => "High demand for your product category in {$region}.",
            ExportMarketPotential::Medium => 'Growing market with emerging opportunities.',
            ExportMarketPotential::Low => 'Emerging market opportunities.',
        };
    }

    /**
     * @param  array<int, string>  $countryCodes
     */
    private function countMarketInquiries(
        int $manufacturerId,
        array $countryCodes,
        ?Carbon $start = null,
        ?Carbon $end = null,
    ): int {
        if ($countryCodes === []) {
            return 0;
        }

        $names = $this->countryNamesForCodes($countryCodes);

        return RfqSubmission::query()
            ->where('manufacturer_id', $manufacturerId)
            ->when($start !== null && $end !== null, fn ($query) => $query->whereBetween('created_at', [$start, $end]))
            ->where(function ($query) use ($names): void {
                $query->whereIn('destination_country', $names)
                    ->orWhereHas('buyer.company', function ($companyQuery) use ($names): void {
                        $companyQuery->whereIn('country', $names);
                    });
            })
            ->count();
    }

    /**
     * @param  array<int, string>  $countryCodes
     */
    private function countMarketOrders(
        int $manufacturerId,
        array $countryCodes,
        ?Carbon $start = null,
        ?Carbon $end = null,
    ): int {
        if ($countryCodes === []) {
            return 0;
        }

        $names = $this->countryNamesForCodes($countryCodes);

        return Order::query()
            ->where('manufacturer_id', $manufacturerId)
            ->when($start !== null && $end !== null, fn ($query) => $query->whereBetween('created_at', [$start, $end]))
            ->where(function ($query) use ($names): void {
                $query->whereIn('destination', $names)
                    ->orWhereHas('buyer.company', function ($companyQuery) use ($names): void {
                        $companyQuery->whereIn('country', $names);
                    });
            })
            ->count();
    }

    /**
     * @param  array<int, string>  $countryNames
     */
    private function countPlatformInquiriesForCountries(array $countryNames, Carbon $start, Carbon $end): int
    {
        if ($countryNames === []) {
            return 0;
        }

        return RfqSubmission::query()
            ->whereBetween('created_at', [$start, $end])
            ->where(function ($query) use ($countryNames): void {
                $query->whereIn('destination_country', $countryNames)
                    ->orWhereHas('buyer.company', function ($companyQuery) use ($countryNames): void {
                        $companyQuery->whereIn('country', $countryNames);
                    });
            })
            ->count();
    }

    /**
     * @param  array<int, string>  $countryCodes
     * @return array<int, string>
     */
    private function countryNamesForCodes(array $countryCodes): array
    {
        return collect($countryCodes)
            ->map(function (string $code): ?string {
                $country = CountryExportRegionResolver::toExportCountry($code);

                return $country['name'] ?? null;
            })
            ->filter()
            ->unique()
            ->values()
            ->all();
    }

    /**
     * @return array{
     *     current_start: Carbon,
     *     current_end: Carbon,
     *     previous_start: Carbon,
     *     previous_end: Carbon
     * }
     */
    private function defaultStatsRange(): array
    {
        $currentEnd = now()->endOfDay();
        $currentStart = now()->subDays(29)->startOfDay();
        $previousEnd = $currentStart->copy()->subSecond();
        $previousStart = $previousEnd->copy()->subDays(29)->startOfDay();

        return [
            'current_start' => $currentStart,
            'current_end' => $currentEnd,
            'previous_start' => $previousStart,
            'previous_end' => $previousEnd,
        ];
    }
}
