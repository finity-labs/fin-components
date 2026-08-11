<?php

declare(strict_types=1);

use FinityLabs\FinModalTableSelect\Components\ModalTableSelect;
use FinityLabs\FinModalTableSelect\Tests\Fixtures\Models\Post;
use Illuminate\Database\Eloquent\Collection as EloquentCollection;

function repeaterField(): ModalTableSelect
{
    return ModalTableSelect::make('product_picker')
        ->multiple()
        ->fillsRepeater('items', fn (Post $record): array => [
            'post_id' => $record->getKey(),
            'title' => $record->title,
            'quantity' => 1,
        ], keyAttribute: 'post_id');
}

function postRecords(int ...$ids): EloquentCollection
{
    return new EloquentCollection(array_map(
        fn (int $id): Post => (new Post)->forceFill(['id' => $id, 'title' => "Post {$id}"]),
        $ids,
    ));
}

it('is chainable', function () {
    $field = ModalTableSelect::make('picker');

    expect($field->fillsRepeater('items', fn () => []))->toBe($field);
});

it('appends rows for newly selected records', function () {
    $merged = repeaterField()->mergeRepeaterItems([], postRecords(1, 2));

    expect($merged)->toHaveCount(2)
        ->and(array_column($merged, 'post_id'))->toBe([1, 2])
        ->and(array_column($merged, 'quantity'))->toBe([1, 1]);
});

it('preserves user-edited rows for records that stay selected', function () {
    $existing = [
        'uuid-1' => ['post_id' => 1, 'title' => 'Post 1', 'quantity' => 99],
    ];

    $merged = repeaterField()->mergeRepeaterItems($existing, postRecords(1, 2));

    expect($merged)->toHaveCount(2)
        ->and($merged['uuid-1']['quantity'])->toBe(99)
        ->and(array_column($merged, 'post_id'))->toBe([1, 2]);
});

it('removes rows for deselected records', function () {
    $existing = [
        'uuid-1' => ['post_id' => 1, 'title' => 'Post 1', 'quantity' => 5],
        'uuid-2' => ['post_id' => 2, 'title' => 'Post 2', 'quantity' => 7],
    ];

    $merged = repeaterField()->mergeRepeaterItems($existing, postRecords(2));

    expect($merged)->toHaveCount(1)
        ->and(array_column($merged, 'post_id'))->toBe([2])
        ->and($merged['uuid-2']['quantity'])->toBe(7);
});

it('clears all rows when everything is deselected', function () {
    $existing = [
        'uuid-1' => ['post_id' => 1, 'quantity' => 5],
    ];

    $merged = repeaterField()->mergeRepeaterItems($existing, postRecords());

    expect($merged)->toBe([]);
});

it('injects the key attribute when the item closure omits it', function () {
    $field = ModalTableSelect::make('picker')
        ->multiple()
        ->fillsRepeater('items', fn (Post $record): array => [
            'title' => $record->title,
        ], keyAttribute: 'post_id');

    $merged = $field->mergeRepeaterItems([], postRecords(7));

    expect(array_column($merged, 'post_id'))->toBe([7]);
});

it('keeps existing row keys stable across merges', function () {
    $field = repeaterField();

    $first = $field->mergeRepeaterItems([], postRecords(1));
    $firstKey = array_key_first($first);

    $second = $field->mergeRepeaterItems($first, postRecords(1, 2));

    expect(array_key_first($second))->toBe($firstKey);
});
