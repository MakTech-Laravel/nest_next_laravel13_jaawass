<?php

namespace App\Support\ExportMarkets;

use App\Support\Countries\CountryCatalog;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\DB;

class ManufacturerExportMarketVisibility
{
    /**
     * @var array<int, string>
     */
    public const DEFAULT_COUNTRY_POOL = [
        'US', 'GB', 'DE', 'FR', 'AU', 'CA', 'AE', 'SG', 'IN', 'JP',
    ];

    public function applyToSupplierQuery(Builder $query, ?string $viewerCountryCode): Builder
    {
        $code = CountryCatalog::normalizeCode($viewerCountryCode);

        if ($code === null) {
            return $query;
        }

        $exportCountry = ExportMarketCatalog::findByCode($code);
        $legacyPatterns = CountryCatalog::legacyRegionPatternsForCountry($code);

        return $query->where(function (Builder $builder) use ($code, $exportCountry, $legacyPatterns): void {
            $builder->whereExists(function ($sub) use ($code): void {
                $sub->select(DB::raw(1))
                    ->from('manufacturer_export_market_countries as memc')
                    ->join('manufacturer_export_markets as mem', 'mem.id', '=', 'memc.manufacturer_export_market_id')
                    ->whereColumn('mem.user_id', 'users.id')
                    ->where('memc.country_code', $code);
            });

            if ($exportCountry !== null) {
                $builder->orWhereExists(function ($sub) use ($exportCountry): void {
                    $sub->select(DB::raw(1))
                        ->from('manufacturer_export_markets as mem')
                        ->whereColumn('mem.user_id', 'users.id')
                        ->where('mem.region', $exportCountry['export_market_region'])
                        ->whereNotExists(function ($countries) {
                            $countries->select(DB::raw(1))
                                ->from('manufacturer_export_market_countries as memc')
                                ->whereColumn('memc.manufacturer_export_market_id', 'mem.id');
                        });
                });
            }

            if ($legacyPatterns !== []) {
                $builder->orWhere(function (Builder $legacy) use ($legacyPatterns): void {
                    $legacy->whereDoesntHave('exportMarkets')
                        ->whereHas('company', function (Builder $company) use ($legacyPatterns): void {
                            $company->where(function (Builder $json) use ($legacyPatterns): void {
                                foreach ($legacyPatterns as $pattern) {
                                    $json->orWhere('export_markets', 'like', $pattern);
                                }
                            });
                        });
                });
            }

            $builder->orWhere(function (Builder $defaults) use ($code): void {
                $defaults
                    ->whereDoesntHave('exportMarkets')
                    ->where(function (Builder $noLegacy) {
                        $noLegacy->whereDoesntHave('company')
                            ->orWhereHas('company', function (Builder $company) {
                                $company->where(function (Builder $json) {
                                    $json->whereNull('export_markets')
                                        ->orWhere('export_markets', '')
                                        ->orWhere('export_markets', '[]');
                                });
                            });
                    })
                    ->whereRaw($this->defaultCountryCaseExpression().' = ?', [$code]);
            });
        });
    }

    public function applyToProductQuery(Builder $query, ?string $viewerCountryCode): Builder
    {
        $code = CountryCatalog::normalizeCode($viewerCountryCode);

        if ($code === null) {
            return $query;
        }

        return $query->whereHas('user', function (Builder $supplier) use ($code): void {
            $this->applyToSupplierQuery($supplier, $code);
        });
    }

    public function supplierVisibleToCountry(int $supplierUserId, ?string $viewerCountryCode): bool
    {
        $code = CountryCatalog::normalizeCode($viewerCountryCode);

        if ($code === null) {
            return true;
        }

        $query = \App\Models\User::query()->whereKey($supplierUserId);
        $this->applyToSupplierQuery($query, $code);

        return $query->exists();
    }

    /**
     * @return array<string, int>
     */
    public function exportSupplierCountsByCode(Builder $baseSupplierQuery): array
    {
        $counts = [];

        $explicit = DB::table('manufacturer_export_market_countries as memc')
            ->join('manufacturer_export_markets as mem', 'mem.id', '=', 'memc.manufacturer_export_market_id')
            ->join('users', 'users.id', '=', 'mem.user_id')
            ->whereIn('users.id', (clone $baseSupplierQuery)->select('users.id'))
            ->selectRaw('memc.country_code, COUNT(DISTINCT users.id) as total')
            ->groupBy('memc.country_code')
            ->pluck('total', 'country_code');

        foreach ($explicit as $code => $total) {
            $counts[strtoupper((string) $code)] = (int) $total;
        }

        return $counts;
    }

    private function defaultCountryCaseExpression(): string
    {
        $pool = self::DEFAULT_COUNTRY_POOL;
        $cases = [];

        foreach ($pool as $index => $countryCode) {
            $cases[] = 'WHEN (users.id % '.count($pool).") = {$index} THEN '{$countryCode}'";
        }

        return 'CASE '.implode(' ', $cases).' END';
    }
}
