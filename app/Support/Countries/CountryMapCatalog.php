<?php

namespace App\Support\Countries;

use Illuminate\Support\Collection;

class CountryMapCatalog
{
    /**
     * @return array<int, array{name: string, country: string, country_code: string, group: string, subregion: string}>
     */
    public static function all(): array
    {
        return config('country_map.countries', []);
    }

    /**
     * @return array<string, array<int, string>>
     */
    public static function legacyRegionAliases(): array
    {
        return config('country_map.legacy_region_aliases', []);
    }

    /**
     * @return Collection<int, array{name: string, country: string, country_code: string, group: string, subregion: string}>
     */
    public static function collection(): Collection
    {
        return collect(self::all());
    }

    /**
     * @return array{name: string, country: string, country_code: string, group: string, subregion: string}|null
     */
    public static function findByCode(string $code): ?array
    {
        $normalized = strtoupper(trim($code));

        return self::collection()->first(
            fn (array $country): bool => strtoupper($country['country_code']) === $normalized
        );
    }

    /**
     * @return array{name: string, country: string, country_code: string, group: string, subregion: string}|null
     */
    public static function findByName(string $name): ?array
    {
        $needle = strtolower(trim($name));

        return self::collection()->first(
            fn (array $country): bool => strtolower($country['country']) === $needle
                || strtolower($country['name']) === $needle
        );
    }

    /**
     * @return array<int, string>
     */
    public static function groups(): array
    {
        return self::collection()
            ->pluck('group')
            ->unique()
            ->sort()
            ->values()
            ->all();
    }

    /**
     * @return Collection<int, array{code: string, name: string, export_market_region: string, geographic_region: string}>
     */
    public static function exportCountries(): Collection
    {
        return self::collection()
            ->map(function (array $country): ?array {
                return CountryExportRegionResolver::toExportCountry($country['country_code']);
            })
            ->filter()
            ->values();
    }

    /**
     * @param  array<int, string>  $codes
     * @return array<string, array<int, array{code: string, name: string, export_market_region: string, geographic_region: string}>>
     */
    public static function groupByExportRegion(array $codes): array
    {
        return CountryExportRegionResolver::groupByExportRegion($codes);
    }
}
