<?php

use FinityLabs\LinSupport\Locale\InstalledLocales;

function linSupportTempLang(array $directories): string
{
    $path = sys_get_temp_dir().'/lin-support-lang-'.uniqid();
    mkdir($path);

    foreach ($directories as $directory) {
        mkdir($path.'/'.$directory);
    }

    return $path;
}

it('lists the lang directories sorted, vendor excluded', function (): void {
    $path = linSupportTempLang(['hu', 'vendor', 'de', 'en']);

    expect(InstalledLocales::detect($path))->toBe(['de', 'en', 'hu']);
});

it('falls back to the application locale when the path is missing or empty', function (): void {
    config()->set('app.locale', 'fr');

    expect(InstalledLocales::detect(sys_get_temp_dir().'/lin-support-none-'.uniqid()))->toBe(['fr'])
        ->and(InstalledLocales::detect(linSupportTempLang([])))->toBe(['fr'])
        ->and(InstalledLocales::detect(linSupportTempLang(['vendor'])))->toBe(['fr']);
});
