<?php

declare(strict_types=1);

use Filament\Tables\Columns\TextColumn;
use FinityLabs\FinComponents\Components\ModalTableSelect\ModalTableSelect;

it('returns false for hasTableColumns when none configured', function () {
    $field = ModalTableSelect::make('posts');

    expect($field->hasTableColumns())->toBeFalse();
});

it('returns true for hasTableColumns after tableColumns set', function () {
    $field = ModalTableSelect::make('posts')
        ->tableColumns([
            TextColumn::make('title'),
        ]);

    expect($field->hasTableColumns())->toBeTrue();
});

it('defaults getIsTableStriped to false', function () {
    $field = ModalTableSelect::make('posts');

    expect($field->getIsTableStriped())->toBeFalse();
});

it('returns true for getIsTableStriped after tableStriped', function () {
    $field = ModalTableSelect::make('posts')
        ->tableStriped();

    expect($field->getIsTableStriped())->toBeTrue();
});

it('evaluates Closure for tableStriped', function () {
    $field = ModalTableSelect::make('posts')
        ->tableStriped(fn () => true);

    expect($field->getIsTableStriped())->toBeTrue();
});

it('returns translation key for getTableEmptyMessage when not set', function () {
    $field = ModalTableSelect::make('posts');

    expect($field->getTableEmptyMessage())->toBeString()->not->toBeEmpty();
});

it('returns custom message for getTableEmptyMessage when set', function () {
    $field = ModalTableSelect::make('posts')
        ->tableEmptyMessage('No records found');

    expect($field->getTableEmptyMessage())->toBe('No records found');
});

it('evaluates Closure for tableEmptyMessage', function () {
    $field = ModalTableSelect::make('posts')
        ->tableEmptyMessage(fn () => 'Custom empty');

    expect($field->getTableEmptyMessage())->toBe('Custom empty');
});
