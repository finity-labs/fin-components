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

    return $flatten(finCodexLang($locale));
}

/** @return array<string, mixed> The raw lang array of one locale. */
function finCodexLang(string $locale): array
{
    return require dirname(__DIR__, 2).'/resources/lang/'.$locale.'/fin-codex.php';
}

/** @return array<string, mixed> Dotted leaf key path => value for one locale. */
function finCodexLangValues(string $locale): array
{
    $flatten = function (array $values, string $prefix = '') use (&$flatten): array {
        $flat = [];

        foreach ($values as $key => $value) {
            $path = $prefix === '' ? (string) $key : $prefix.'.'.$key;
            $flat += is_array($value) ? $flatten($value, $path) : [$path => $value];
        }

        return $flat;
    };

    return $flatten(finCodexLang($locale));
}

/**
 * Values a translation is allowed to leave byte-identical to the English one.
 *
 * The list is by VALUE, never by key: a key-based list rots the moment someone
 * renames a key, and then silently stops guarding anything.
 *
 * Format, Panel, URI and Name are the words German and Hungarian actually
 * borrow whole — they are the measured identical pairs on today's files
 * (`Format` in de; `Panel` and `URI` in both; `Name` in de). Markdown, HTML and
 * Codex are product names that never translate. Anything added beyond these
 * seven needs a comment saying why the word is the same in all three.
 *
 * @var list<string>
 */
const FIN_CODEX_IDENTICAL_ALLOWED = ['Format', 'Panel', 'URI', 'Name', 'Markdown', 'HTML', 'Codex'];

it('ships the same lang keys in en, de and hu', function (string $locale): void {
    expect(array_keys(finCodexLang($locale)))->toBe(array_keys(finCodexLang('en')))
        ->and(finCodexLangKeys($locale))->toBe(finCodexLangKeys('en'))
        ->and(finCodexLangKeys('en'))->not->toBeEmpty();
})->with(['de', 'hu']);

it('leaves no English string sitting in the de or hu file', function (string $locale): void {
    $translated = finCodexLangValues($locale);

    $untranslated = [];

    foreach (finCodexLangValues('en') as $key => $value) {
        if (! is_string($value) || ($translated[$key] ?? null) !== $value) {
            continue;
        }

        if (in_array($value, FIN_CODEX_IDENTICAL_ALLOWED, true)) {
            continue;
        }

        // ":slug (:locale)" and anything of that shape carries no translatable
        // words at all — only placeholders and punctuation.
        if (trim((string) preg_replace('/:\w+/', '', $value), " \t\n()[]{}·-—:") === '') {
            continue;
        }

        $untranslated[] = "{$locale}.{$key} is still the English string: \"{$value}\"";
    }

    expect($untranslated)->toBe([], PHP_EOL.implode(PHP_EOL, $untranslated));
})->with(['de', 'hu']);

it('loads the package translations under the fin-codex namespace', function (): void {
    expect(__('fin-codex::fin-codex.navigation.group'))->toBe('Help');

    app()->setLocale('de');
    expect(__('fin-codex::fin-codex.navigation.group'))->toBe('Hilfe');

    app()->setLocale('hu');
    expect(NavigationGroup::Help->getLabel())->toBe('Súgó');
});
