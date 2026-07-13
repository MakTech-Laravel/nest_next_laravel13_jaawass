<?php

namespace App\Support\Localization;

/**
 * Canonical BCP-47 locale codes for DB storage and API resolution.
 *
 * App convention: language subtags lowercase, region uppercase, joined with underscore
 * (e.g. zh_CN). Google Cloud Translation expects hyphen form (zh-CN).
 */
final class LocaleCode
{
    public static function canonical(string $locale): string
    {
        $locale = trim($locale);
        if ($locale === '') {
            return 'en';
        }

        $normalized = str_replace('_', '-', $locale);
        $parts = explode('-', $normalized, 2);
        $language = strtolower($parts[0]);

        if (! isset($parts[1]) || $parts[1] === '') {
            return $language;
        }

        return $language.'_'.strtoupper($parts[1]);
    }

    public static function matches(string $a, string $b): bool
    {
        return self::canonical($a) === self::canonical($b);
    }

    /** Google Translate API target/source language code (hyphen form). */
    public static function toGoogle(string $locale): string
    {
        return str_replace('_', '-', self::canonical($locale));
    }

    /**
     * @param  array<int, string>  $supported
     */
    public static function resolveSupported(string $candidate, array $supported): ?string
    {
        if ($supported === [] || trim($candidate) === '') {
            return null;
        }

        foreach ($supported as $locale) {
            if (self::matches($candidate, $locale)) {
                return self::canonical($locale);
            }
        }

        $primary = strtolower(explode('-', str_replace('_', '-', trim($candidate)))[0]);

        foreach ($supported as $locale) {
            if (self::canonical($locale) === $primary) {
                return self::canonical($locale);
            }
        }

        // Legacy frontend Chinese slot used "es". When Spanish is not a product locale
        // but Chinese (zh_CN) is, treat "es" as Chinese so old clients keep working.
        $supportedCanonical = array_map(self::canonical(...), $supported);
        if (
            self::canonical($candidate) === 'es'
            && ! in_array('es', $supportedCanonical, true)
            && in_array('zh_CN', $supportedCanonical, true)
        ) {
            return 'zh_CN';
        }

        return null;
    }
}
