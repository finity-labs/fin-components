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

it('defaults getHasTableFooterCount to false', function () {
    expect(ModalTableSelect::make('posts')->getHasTableFooterCount())->toBeFalse();
});

it('returns true for getHasTableFooterCount after tableFooterCount', function () {
    expect(ModalTableSelect::make('posts')->tableFooterCount()->getHasTableFooterCount())->toBeTrue();
});

it('evaluates a Closure for tableFooterCount', function () {
    $field = ModalTableSelect::make('posts')->tableFooterCount(fn () => true);

    expect($field->getHasTableFooterCount())->toBeTrue();
});

it('defaults getIsTableCollapsible to false', function () {
    expect(ModalTableSelect::make('posts')->getIsTableCollapsible())->toBeFalse();
});

it('returns true for getIsTableCollapsible after tableCollapsible', function () {
    expect(ModalTableSelect::make('posts')->tableCollapsible()->getIsTableCollapsible())->toBeTrue();
});

it('evaluates a Closure for tableCollapsible', function () {
    $field = ModalTableSelect::make('posts')->tableCollapsible(fn () => true);

    expect($field->getIsTableCollapsible())->toBeTrue();
});

it('defaults getIsTableCollapsed to false', function () {
    expect(ModalTableSelect::make('posts')->getIsTableCollapsed())->toBeFalse();
});

it('returns true for getIsTableCollapsed after tableCollapsed', function () {
    expect(ModalTableSelect::make('posts')->tableCollapsed()->getIsTableCollapsed())->toBeTrue();
});

it('evaluates a Closure for tableCollapsed', function () {
    $field = ModalTableSelect::make('posts')->tableCollapsed(fn () => true);

    expect($field->getIsTableCollapsed())->toBeTrue();
});

it('builds a client-side toggleTable hint action', function () {
    $action = ModalTableSelect::make('posts')->getCollapseToggleAction();

    expect($action->getName())->toBe('toggleTable')
        ->and($action->getAlpineClickHandler())->toBe('open = ! open');
});

it('does not show the collapse toggle unless the table is collapsible', function () {
    expect(ModalTableSelect::make('posts')->shouldShowCollapseToggle())->toBeFalse();
});
