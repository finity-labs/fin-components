<?php

declare(strict_types=1);

namespace FinityLabs\LinSupport\Locale;

/**
 * The locales an application already translates: one per directory under its
 * lang path, `vendor` excluded, sorted; the application locale alone when the
 * path is missing or empty. An installer offers these as the default answer
 * to "which languages", because a host that ships lang/de wants de.
 */
final class InstalledLocales
{
    /**
     * @return list<string>
     */
    public static function detect(?string $langPath = null): array
    {
        $langPath ??= lang_path();
        $fallback = [(string) config('app.locale', 'en')];

        if (! is_dir($langPath)) {
            return $fallback;
        }

        $directories = glob($langPath.'/*', GLOB_ONLYDIR);

        if ($directories === false || $directories === []) {
            return $fallback;
        }

        $locales = [];

        foreach ($directories as $directory) {
            $locale = basename($directory);

            if ($locale !== 'vendor') {
                $locales[] = $locale;
            }
        }

        sort($locales);

        return $locales === [] ? $fallback : $locales;
    }
}
