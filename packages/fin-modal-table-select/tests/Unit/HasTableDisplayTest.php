<?php

declare(strict_types=1);

use Filament\Infolists\Components\RepeatableEntry\TableColumn;
use Filament\Infolists\Components\TextEntry;
use FinityLabs\FinModalTableSelect\Components\ModalTableSelect;

it('returns false for hasTableColumns when none configured', function () {
    expect(ModalTableSelect::make('posts')->hasTableColumns())->toBeFalse();
});

it('returns true for hasTableColumns after tableColumns set', function () {
    $field = ModalTableSelect::make('posts')
        ->tableColumns([TableColumn::make('Title')]);

    expect($field->hasTableColumns())->toBeTrue();
});

it('returns an empty array from getTableColumns when none configured', function () {
    expect(ModalTableSelect::make('posts')->getTableColumns())->toBe([]);
});

it('returns the configured header columns from getTableColumns', function () {
    $columns = [TableColumn::make('Title'), TableColumn::make('Status')];

    $field = ModalTableSelect::make('posts')->tableColumns($columns);

    expect($field->getTableColumns())->toBe($columns);
});

it('evaluates a Closure for tableColumns', function () {
    $field = ModalTableSelect::make('posts')
        ->tableColumns(fn () => [TableColumn::make('Title')]);

    expect($field->getTableColumns())->toHaveCount(1);
});

it('returns an empty array from getTableSchema when none configured', function () {
    expect(ModalTableSelect::make('posts')->getTableSchema())->toBe([]);
});

it('returns the configured entries from getTableSchema', function () {
    $entries = [TextEntry::make('title'), TextEntry::make('status')];

    $field = ModalTableSelect::make('posts')->tableSchema($entries);

    expect($field->getTableSchema())->toBe($entries);
});

it('evaluates a Closure for tableSchema', function () {
    $field = ModalTableSelect::make('posts')
        ->tableSchema(fn () => [TextEntry::make('title')]);

    expect($field->getTableSchema())->toHaveCount(1);
});

it('is chainable for tableModifyQueryUsing', function () {
    $field = ModalTableSelect::make('posts');

    expect($field->tableModifyQueryUsing(fn ($query) => $query))->toBe($field);
});

it('returns translation key for getTableEmptyMessage when not set', function () {
    expect(ModalTableSelect::make('posts')->getTableEmptyMessage())
        ->toBeString()->not->toBeEmpty();
});

it('returns custom message for getTableEmptyMessage when set', function () {
    $field = ModalTableSelect::make('posts')->tableEmptyMessage('No records found');

    expect($field->getTableEmptyMessage())->toBe('No records found');
});

it('evaluates Closure for tableEmptyMessage', function () {
    $field = ModalTableSelect::make('posts')->tableEmptyMessage(fn () => 'Custom empty');

    expect($field->getTableEmptyMessage())->toBe('Custom empty');
});
