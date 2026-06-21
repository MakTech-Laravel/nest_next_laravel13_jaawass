<?php

namespace App\Support\Countries;

use App\Support\ExportMarkets\ExportMarketCatalog;

class CountryCatalog
{
    /**
     * @return array{name: string, country: string, country_code: string, group: string, subregion: string}|null
     */
    public static function findByCode(string $code): ?array
    {
        return CountryMapCatalog::findByCode($code)
            ?? self::fromExportMarketCatalog($code);
    }

    /**
     * @return array{name: string, country: string, country_code: string, group: string, subregion: string}|null
     */
    public static function findByName(string $name): ?array
    {
        return CountryMapCatalog::findByName($name)
            ?? self::fromExportMarketName($name);
    }

    public static function normalizeCode(?string $value): ?string
    {
        if ($value === null) {
            return null;
        }

        $value = strtoupper(trim($value));

        if ($value === '' || strlen($value) !== 2) {
            return null;
        }

        return self::findByCode($value) !== null ? $value : null;
    }

    public static function resolveCode(?string $countryOrCode): ?string
    {
        if ($countryOrCode === null || trim($countryOrCode) === '') {
            return null;
        }

        $value = trim($countryOrCode);

        if (strlen($value) === 2) {
            return self::normalizeCode($value);
        }

        $fromMap = CountryMapCatalog::findByName($value);

        if ($fromMap !== null) {
            return strtoupper($fromMap['country_code']);
        }

        $fromExport = ExportMarketCatalog::findByName($value);

        return $fromExport !== null ? strtoupper($fromExport['code']) : null;
    }

    public static function resolveName(?string $countryOrCode): ?string
    {
        if ($countryOrCode === null || trim($countryOrCode) === '') {
            return null;
        }

        $code = self::resolveCode($countryOrCode);

        if ($code === null) {
            return null;
        }

        $country = self::findByCode($code);

        if ($country !== null) {
            return $country['country'];
        }

        $exportCountry = ExportMarketCatalog::findByCode($code);

        return $exportCountry['name'] ?? null;
    }

    /**
     * @return array<int, string>
     */
    public static function legacyRegionPatternsForCountry(string $countryCode): array
    {
        $code = self::normalizeCode($countryCode);

        if ($code === null) {
            return [];
        }

        $patterns = [];

        $exportCountry = ExportMarketCatalog::findByCode($code);

        if ($exportCountry !== null) {
            $patterns[] = '%'.$exportCountry['export_market_region'].'%';
        }

        $mapCountry = CountryMapCatalog::findByCode($code);

        if ($mapCountry !== null) {
            $patterns[] = '%'.$mapCountry['group'].'%';
            $patterns[] = '%'.$mapCountry['subregion'].'%';
        }

        foreach (CountryMapCatalog::legacyRegionAliases() as $legacyRegion => $aliases) {
            $matchesAlias = collect($aliases)->contains(function (string $alias) use ($exportCountry, $mapCountry): bool {
                if ($exportCountry !== null && $alias === $exportCountry['export_market_region']) {
                    return true;
                }

                if ($mapCountry !== null && ($alias === $mapCountry['group'] || $alias === $mapCountry['subregion'])) {
                    return true;
                }

                return false;
            });

            if ($matchesAlias) {
                $patterns[] = '%'.$legacyRegion.'%';
            }
        }

        $name = self::resolveName($code);

        if ($name !== null) {
            $patterns[] = '%'.$name.'%';
        }

        return collect($patterns)->unique()->values()->all();
    }

    /**
     * @return array{name: string, country: string, country_code: string, group: string, subregion: string}|null
     */
    private static function fromExportMarketCatalog(string $code): ?array
    {
        $country = ExportMarketCatalog::findByCode($code);

        if ($country === null) {
            return null;
        }

        return [
            'name' => $country['name'],
            'country' => $country['name'],
            'country_code' => strtoupper($country['code']),
            'group' => $country['geographic_region'],
            'subregion' => $country['export_market_region'],
        ];
    }

    /**
     * @return array{name: string, country: string, country_code: string, group: string, subregion: string}|null
     */
    private static function fromExportMarketName(string $name): ?array
    {
        $country = ExportMarketCatalog::findByName($name);

        if ($country === null) {
            return null;
        }

        return self::fromExportMarketCatalog($country['code']);
    }
}
