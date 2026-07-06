<?php

namespace App\Support\Countries;

use App\Support\ExportMarkets\ExportMarketCatalog;

class CountryExportRegionResolver
{
    /**
     * @return array<string, string>
     */
    private static function subregionExportRegions(): array
    {
        return config('country_map.subregion_export_regions', []);
    }

    /**
     * @return array<string, string>
     */
    private static function groupExportRegions(): array
    {
        return config('country_map.group_export_regions', []);
    }

    public static function resolveForCode(string $code): ?string
    {
        $exportCountry = ExportMarketCatalog::findByCode($code);

        if ($exportCountry !== null) {
            return $exportCountry['export_market_region'];
        }

        $mapCountry = CountryMapCatalog::findByCode($code);

        if ($mapCountry === null) {
            return null;
        }

        return self::resolveForMapCountry($mapCountry);
    }

    /**
     * @param  array{name: string, country: string, country_code: string, group: string, subregion: string}  $mapCountry
     */
    public static function resolveForMapCountry(array $mapCountry): ?string
    {
        $code = strtoupper($mapCountry['country_code']);
        $exportCountry = ExportMarketCatalog::findByCode($code);

        if ($exportCountry !== null) {
            return $exportCountry['export_market_region'];
        }

        $subregion = $mapCountry['subregion'] ?? '';
        $subregionRegions = self::subregionExportRegions();

        if ($subregion !== '' && isset($subregionRegions[$subregion])) {
            $region = $subregionRegions[$subregion];

            return ExportMarketCatalog::isValidRegion($region) ? $region : null;
        }

        $group = $mapCountry['group'] ?? '';
        $groupRegions = self::groupExportRegions();

        if ($group !== '' && isset($groupRegions[$group])) {
            $region = $groupRegions[$group];

            return ExportMarketCatalog::isValidRegion($region) ? $region : null;
        }

        return null;
    }

    /**
     * @return array{code: string, name: string, export_market_region: string, geographic_region: string}|null
     */
    public static function toExportCountry(string $code): ?array
    {
        $mapCountry = CountryMapCatalog::findByCode($code);

        if ($mapCountry === null) {
            return null;
        }

        $exportRegion = self::resolveForMapCountry($mapCountry);

        if ($exportRegion === null) {
            return null;
        }

        return [
            'code' => strtoupper($mapCountry['country_code']),
            'name' => $mapCountry['country'],
            'export_market_region' => $exportRegion,
            'geographic_region' => $mapCountry['group'],
        ];
    }

    /**
     * @param  array<int, string>  $codes
     * @return array<string, array<int, array{code: string, name: string, export_market_region: string, geographic_region: string}>>
     */
    public static function groupByExportRegion(array $codes): array
    {
        $grouped = [];

        foreach ($codes as $code) {
            $country = self::toExportCountry($code);

            if ($country === null) {
                continue;
            }

            $grouped[$country['export_market_region']][] = $country;
        }

        return $grouped;
    }
}
