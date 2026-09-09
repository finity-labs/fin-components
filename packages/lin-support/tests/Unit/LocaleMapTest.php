<?php

use FinityLabs\LinSupport\Locale\LocaleMap;

it('knows sixty locales with a native display name and a flag', function (): void {
    expect(LocaleMap::codes())->toHaveCount(60)
        ->and(LocaleMap::has('de'))->toBeTrue()
        ->and(LocaleMap::has('xx'))->toBeFalse()
        ->and(LocaleMap::entry('de'))->toBe(['code' => 'de', 'display' => 'Deutsch', 'flag-icon' => 'de'])
        ->and(LocaleMap::entry('en')['flag-icon'])->toBe('gb')
        ->and(LocaleMap::entry('hu')['display'])->toBe('Magyar');
});

it('derives an entry for a code it does not carry', function (): void {
    $entry = LocaleMap::entry('xx');

    expect($entry['code'])->toBe('xx')
        ->and($entry['display'])->toBe('XX')
        ->and($entry['flag-icon'])->toBe('xx');
});

it('labels a code for a prompt', function (): void {
    expect(LocaleMap::label('de'))->toBe('Deutsch (de)')
        ->and(LocaleMap::label('xx'))->toBe('XX (xx)');
});

it('turns codes into settings rows, keeping order, dropping blanks and duplicates', function (): void {
    expect(array_column(LocaleMap::entries(['hu', '', ' de ', 'hu', 'en']), 'code'))->toBe(['hu', 'de', 'en'])
        ->and(LocaleMap::entries(['de'])[0])->toBe(['code' => 'de', 'display' => 'Deutsch', 'flag-icon' => 'de']);
});
