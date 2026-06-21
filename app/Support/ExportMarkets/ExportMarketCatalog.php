<?php

namespace App\Support\ExportMarkets;

use Illuminate\Support\Collection;

class ExportMarketCatalog
{
    /**
     * @return array<int, string>
     */
    public static function regions(): array
    {
        return config('export_markets.regions', []);
    }

    /**
     * @return Collection<int, array{code: string, name: string, export_market_region: string, geographic_region: string}>
     */
    public static function countries(): Collection
    {
        return collect(config('export_markets.countries', []));
    }

    /**
     * @return array{code: string, name: string, export_market_region: string, geographic_region: string}|null
     */
    public static function findByCode(string $code): ?array
    {
        $normalized = strtoupper(trim($code));

        return self::countries()->first(
            fn (array $country): bool => strtoupper($country['code']) === $normalized
        );
    }

    /**
     * @return array{code: string, name: string, export_market_region: string, geographic_region: string}|null
     */
    public static function findByName(string $name): ?array
    {
        $needle = strtolower(trim($name));

        return self::countries()->first(
            fn (array $country): bool => strtolower($country['name']) === $needle
        );
    }

    /**
     * @return array<int, string>
     */
    public static function geographicRegions(): array
    {
        return self::countries()
            ->pluck('geographic_region')
            ->unique()
            ->sort()
            ->values()
            ->all();
    }

    /**
     * @param  array<int, string>  $codes
     * @return array<string, array<int, array{code: string, name: string, export_market_region: string, geographic_region: string}>>
     */
    public static function groupByExportRegion(array $codes): array
    {
        $grouped = [];

        foreach ($codes as $code) {
            $country = self::findByCode($code);

            if ($country === null) {
                continue;
            }

            $grouped[$country['export_market_region']][] = $country;
        }

        return $grouped;
    }

    public static function isValidRegion(string $region): bool
    {
        return in_array($region, self::regions(), true);
    }
}
