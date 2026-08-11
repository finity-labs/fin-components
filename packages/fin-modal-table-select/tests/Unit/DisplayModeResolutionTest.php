<?php

declare(strict_types=1);

use Filament\Infolists\Components\RepeatableEntry\TableColumn;
use Filament\Infolists\Components\TextEntry;
use FinityLabs\FinModalTableSelect\Components\ModalTableSelect;
use FinityLabs\FinModalTableSelect\Enums\DisplayMode;

it('defaults to Badges when nothing configured', function () {
    $field = ModalTableSelect::make('posts');

    expect($field->getDisplayMode())->toBe(DisplayMode::Badges);
});

it('resolves to SelectionOnly when selectionOnly enabled', function () {
    $field = ModalTableSelect::make('posts')
        ->selectionOnly();

    expect($field->getDisplayMode())->toBe(DisplayMode::SelectionOnly);
});

it('resolves to Table when multiple with tableColumns configured', function () {
    $field = ModalTableSelect::make('posts')
        ->multiple()
        ->tableColumns([
            TableColumn::make('Title'),
        ]);

    expect($field->getDisplayMode())->toBe(DisplayMode::Table);
});

it('resolves to Table when displayAsTable enabled without explicit columns', function () {
    $field = ModalTableSelect::make('posts')
        ->multiple()
        ->displayAsTable();

    expect($field->getDisplayMode())->toBe(DisplayMode::Table);
});

it('resolves to Table when only tableSchema configured', function () {
    $field = ModalTableSelect::make('posts')
        ->multiple()
        ->tableSchema([
            TextEntry::make('title'),
        ]);

    expect($field->getDisplayMode())->toBe(DisplayMode::Table);
});

it('resolves to Badges when multiple but no table display', function () {
    $field = ModalTableSelect::make('posts')
        ->multiple();

    expect($field->getDisplayMode())->toBe(DisplayMode::Badges);
});

it('resolves to Table when single with tableColumns configured', function () {
    $field = ModalTableSelect::make('posts')
        ->tableColumns([
            TableColumn::make('Title'),
        ]);

    expect($field->getDisplayMode())->toBe(DisplayMode::Table);
});

it('resolves to Infolist when single with infolistSchema configured', function () {
    $field = ModalTableSelect::make('posts')
        ->infolistSchema([
            TextEntry::make('title'),
        ]);

    expect($field->getDisplayMode())->toBe(DisplayMode::Infolist);
});

it('ignores infolistSchema for multiple selection', function () {
    $field = ModalTableSelect::make('posts')
        ->multiple()
        ->infolistSchema([
            TextEntry::make('title'),
        ]);

    expect($field->getDisplayMode())->toBe(DisplayMode::Badges);
});

it('resolves to Badges when single with no schema', function () {
    $field = ModalTableSelect::make('posts');

    expect($field->getDisplayMode())->toBe(DisplayMode::Badges);
});

it('gives SelectionOnly priority over tableColumns', function () {
    $field = ModalTableSelect::make('posts')
        ->multiple()
        ->selectionOnly()
        ->tableColumns([
            TableColumn::make('Title'),
        ]);

    expect($field->getDisplayMode())->toBe(DisplayMode::SelectionOnly);
});

it('gives Table priority over infolistSchema for single selection', function () {
    $field = ModalTableSelect::make('posts')
        ->tableColumns([
            TableColumn::make('Title'),
        ])
        ->infolistSchema([
            TextEntry::make('title'),
        ]);

    expect($field->getDisplayMode())->toBe(DisplayMode::Table);
});

it('returns true for hasCustomDisplay with Table mode', function () {
    $field = ModalTableSelect::make('posts')
        ->multiple()
        ->tableColumns([
            TableColumn::make('Title'),
        ]);

    expect($field->hasCustomDisplay())->toBeTrue();
});

it('returns true for hasCustomDisplay with Infolist mode', function () {
    $field = ModalTableSelect::make('posts')
        ->infolistSchema([
            TextEntry::make('title'),
        ]);

    expect($field->hasCustomDisplay())->toBeTrue();
});

it('returns false for hasCustomDisplay with Badges mode', function () {
    $field = ModalTableSelect::make('posts');

    expect($field->hasCustomDisplay())->toBeFalse();
});

it('returns false for hasCustomDisplay with SelectionOnly mode', function () {
    $field = ModalTableSelect::make('posts')
        ->selectionOnly();

    expect($field->hasCustomDisplay())->toBeFalse();
});
