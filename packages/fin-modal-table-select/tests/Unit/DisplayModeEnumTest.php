<?php

declare(strict_types=1);

use FinityLabs\FinModalTableSelect\Enums\DisplayMode;

it('has exactly 4 cases', function () {
    expect(DisplayMode::cases())->toHaveCount(4);
});

it('has badges case with correct value', function () {
    expect(DisplayMode::Badges->value)->toBe('badges');
});

it('has table case with correct value', function () {
    expect(DisplayMode::Table->value)->toBe('table');
});

it('has infolist case with correct value', function () {
    expect(DisplayMode::Infolist->value)->toBe('infolist');
});

it('has selection_only case with correct value', function () {
    expect(DisplayMode::SelectionOnly->value)->toBe('selection_only');
});
