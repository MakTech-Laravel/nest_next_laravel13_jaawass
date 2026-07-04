<?php

namespace App\Support\Cms;

final class AboutPageContentFlattener
{
    /**
     * @param  array<string, mixed>  $content
     * @return array<string, string>
     */
    public static function flatten(array $content, string $prefix = ''): array
    {
        $strings = [];

        foreach ($content as $key => $value) {
            $path = $prefix === '' ? (string) $key : "{$prefix}.{$key}";

            if (is_string($value)) {
                if (trim($value) !== '') {
                    $strings[$path] = $value;
                }

                continue;
            }

            if (is_array($value)) {
                $strings = array_merge($strings, self::flatten($value, $path));
            }
        }

        return $strings;
    }

    /**
     * @param  array<string, mixed>  $content
     * @param  array<string, string>  $translations
     * @return array<string, mixed>
     */
    public static function apply(array $content, array $translations): array
    {
        $result = $content;

        foreach ($translations as $path => $value) {
            self::setByPath($result, $path, $value);
        }

        return $result;
    }

    /**
     * @param  array<string, mixed>  $target
     */
    private static function setByPath(array &$target, string $path, string $value): void
    {
        $segments = explode('.', $path);
        $cursor = &$target;

        foreach ($segments as $index => $segment) {
            $isLast = $index === count($segments) - 1;

            if ($isLast) {
                $cursor[$segment] = $value;

                return;
            }

            if (! isset($cursor[$segment]) || ! is_array($cursor[$segment])) {
                $cursor[$segment] = ctype_digit($segments[$index + 1] ?? '') ? [] : [];
            }

            $cursor = &$cursor[$segment];
        }
    }
}
