<?php

declare(strict_types=1);

use FinityLabs\FinModalTableSelect\Components\ModalTableSelect;
use FinityLabs\FinModalTableSelect\Tests\Fixtures\Models\Post;

function arrayRecordsField(): ModalTableSelect
{
    return ModalTableSelect::make('routes')
        ->standaloneRecords([
            ['key' => 'dashboard', 'label' => 'Dashboard', 'uri' => '/admin'],
            ['key' => 'users.index', 'label' => 'Users', 'uri' => '/admin/users'],
        ], titleAttribute: 'label');
}

it('is off by default', function () {
    expect(ModalTableSelect::make('routes')->hasStandaloneRecords())->toBeFalse();
});

it('is on after standaloneRecords with its attribute configuration', function () {
    $field = arrayRecordsField();

    expect($field->hasStandaloneRecords())->toBeTrue()
        ->and($field->getStandaloneRecordsKeyAttribute())->toBe('key')
        ->and($field->getStandaloneRecordsTitleAttribute())->toBe('label');
});

it('indexes the records list by the key attribute', function () {
    $index = arrayRecordsField()->getStandaloneRecordsIndex();

    expect(array_keys($index))->toBe(['dashboard', 'users.index'])
        ->and($index['dashboard']['label'])->toBe('Dashboard');
});

it('accepts a custom key attribute', function () {
    $field = ModalTableSelect::make('routes')
        ->standaloneRecords([
            ['name' => 'r1', 'label' => 'One'],
        ], keyAttribute: 'name');

    expect(array_keys($field->getStandaloneRecordsIndex()))->toBe(['r1'])
        ->and($field->getRecordKey(['name' => 'r1']))->toBe('r1');
});

it('finds a record by key and returns null for unknown keys', function () {
    $field = arrayRecordsField();

    expect($field->findStandaloneRecord('dashboard'))->toBe(['key' => 'dashboard', 'label' => 'Dashboard', 'uri' => '/admin'])
        ->and($field->findStandaloneRecord('ghost'))->toBeNull()
        ->and($field->findStandaloneRecord(null))->toBeNull();
});

it('builds a raw-key stand-in for stale keys', function () {
    expect(arrayRecordsField()->makeStaleStandaloneRecord('ghost.route'))->toBe(['key' => 'ghost.route']);
});

it('labels array records by the title attribute, falling back to the key', function () {
    $field = arrayRecordsField();

    expect($field->getRecordDisplayLabel(['key' => 'dashboard', 'label' => 'Dashboard']))->toBe('Dashboard')
        ->and($field->getRecordDisplayLabel(['key' => 'ghost.route']))->toBe('ghost.route');
});

it('keeps model record keys and labels working through getRecordKey', function () {
    $record = (new Post)->forceFill(['id' => 7, 'title' => 'Hello']);

    expect(ModalTableSelect::make('posts')->getRecordKey($record))->toBe('7');
});

it('resolves display values from array records via paths and closures', function () {
    $field = arrayRecordsField();
    $record = ['key' => 'dashboard', 'label' => 'Dashboard', 'meta' => ['icon' => 'home']];

    expect($field->resolveRecordDisplayValue($record, 'meta.icon'))->toBe('home')
        ->and($field->resolveRecordDisplayValue($record, fn (array $record): string => strtoupper($record['label'])))->toBe('DASHBOARD');
});

it('merges repeater rows from array records using the record key', function () {
    $field = ModalTableSelect::make('routes')
        ->multiple()
        ->standaloneRecords([], titleAttribute: 'label')
        ->fillsRepeater('items', fn (array $record): array => [
            'route' => $record['key'],
            'label' => $record['label'] ?? $record['key'],
        ], keyAttribute: 'route');

    $records = collect([
        ['key' => 'dashboard', 'label' => 'Dashboard'],
        ['key' => 'users.index', 'label' => 'Users'],
    ]);

    $existing = [
        'row-a' => ['route' => 'dashboard', 'label' => 'Dashboard (edited)'],
    ];

    $merged = $field->mergeRepeaterItems($existing, $records);

    expect($merged)->toHaveCount(2)
        ->and($merged['row-a']['label'])->toBe('Dashboard (edited)')
        ->and(array_column($merged, 'route'))->toBe(['dashboard', 'users.index']);
});
