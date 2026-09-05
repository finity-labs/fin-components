<?php

use FinityLabs\FinCodex\Enums\NavigationGroup;

/** @return list<string> Sorted dotted key paths of one locale's lang file. */
function finCodexLangKeys(string $locale): array
{
    $flatten = function (array $values, string $prefix = '') use (&$flatten): array {
        $keys = [];

        foreach ($values as $key => $value) {
            $path = $prefix === '' ? (string) $key : $prefix.'.'.$key;
            $keys = is_array($value) ? [...$keys, ...$flatten($value, $path)] : [...$keys, $path];
        }

        sort($keys);

        return $keys;
    };

    return $flatten(require dirname(__DIR__, 2).'/resources/lang/'.$locale.'/fin-codex.php');
}

it('ships the same lang keys in en, de and hu', function (string $locale): void {
    expect(finCodexLangKeys($locale))->toBe(finCodexLangKeys('en'))
        ->and(finCodexLangKeys('en'))->not->toBeEmpty();
})->with(['de', 'hu']);

it('loads the package translations under the fin-codex namespace', function (): void {
    expect(__('fin-codex::fin-codex.navigation.group'))->toBe('Help');

    app()->setLocale('de');
    expect(__('fin-codex::fin-codex.navigation.group'))->toBe('Hilfe');

    app()->setLocale('hu');
    expect(NavigationGroup::Help->getLabel())->toBe('Súgó');
});
