<?php

declare(strict_types=1);

use Filament\Forms\Components\TextInput;
use Filament\Infolists\Components\TextEntry;
use Filament\Tables\Columns\TextColumn;
use FinityLabs\FinComponents\Components\ModalTableSelect\Enums\DisplayMode;
use FinityLabs\FinComponents\Components\ModalTableSelect\ModalTableSelect;

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
            TextColumn::make('title'),
        ]);

    expect($field->getDisplayMode())->toBe(DisplayMode::Table);
});

it('resolves to Badges when multiple but no tableColumns', function () {
    $field = ModalTableSelect::make('posts')
        ->multiple();

    expect($field->getDisplayMode())->toBe(DisplayMode::Badges);
});

it('resolves to Infolist when single with infolistSchema configured', function () {
    $field = ModalTableSelect::make('posts')
        ->infolistSchema([
            TextEntry::make('title'),
        ]);

    expect($field->getDisplayMode())->toBe(DisplayMode::Infolist);
});

it('resolves to Form when single with formSchema configured', function () {
    $field = ModalTableSelect::make('posts')
        ->formSchema([
            TextInput::make('title'),
        ]);

    expect($field->getDisplayMode())->toBe(DisplayMode::Form);
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
            TextColumn::make('title'),
        ]);

    expect($field->getDisplayMode())->toBe(DisplayMode::SelectionOnly);
});

it('returns true for hasCustomDisplay with Table mode', function () {
    $field = ModalTableSelect::make('posts')
        ->multiple()
        ->tableColumns([
            TextColumn::make('title'),
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

it('returns true for hasCustomDisplay with Form mode', function () {
    $field = ModalTableSelect::make('posts')
        ->formSchema([
            TextInput::make('title'),
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
