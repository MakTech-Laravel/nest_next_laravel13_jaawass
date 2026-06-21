<?php

namespace App\Support\Countries;

use App\Support\ExportMarkets\ExportMarketCatalog;
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
}
