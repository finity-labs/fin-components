<?php

declare(strict_types=1);

use FinityLabs\FinModalTableSelect\Enums\DisplayMode;

it('has exactly 8 cases', function () {
    expect(DisplayMode::cases())->toHaveCount(8);
});

it('has badges case with correct value', function () {
    expect(DisplayMode::Badges->value)->toBe('badges');
});

it('has table case with correct value', function () {
    expect(DisplayMode::Table->value)->toBe('table');
});

it('has stacked_list case with correct value', function () {
    expect(DisplayMode::StackedList->value)->toBe('stacked_list');
});

it('has cards case with correct value', function () {
    expect(DisplayMode::Cards->value)->toBe('cards');
});

it('has thumbnails case with correct value', function () {
    expect(DisplayMode::Thumbnails->value)->toBe('thumbnails');
});

it('has item_view case with correct value', function () {
    expect(DisplayMode::ItemView->value)->toBe('item_view');
});

it('has infolist case with correct value', function () {
    expect(DisplayMode::Infolist->value)->toBe('infolist');
});

it('has selection_only case with correct value', function () {
    expect(DisplayMode::SelectionOnly->value)->toBe('selection_only');
});
