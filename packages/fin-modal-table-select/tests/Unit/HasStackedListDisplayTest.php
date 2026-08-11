<?php

declare(strict_types=1);

use FinityLabs\FinModalTableSelect\Components\ModalTableSelect;
use FinityLabs\FinModalTableSelect\Enums\DisplayMode;
use FinityLabs\FinModalTableSelect\Tests\Fixtures\Models\Post;

it('is disabled by default', function () {
    expect(ModalTableSelect::make('posts')->hasStackedListDisplay())->toBeFalse();
});

it('resolves to StackedList mode when enabled', function () {
    $field = ModalTableSelect::make('posts')->multiple()->stackedList();

    expect($field->getDisplayMode())->toBe(DisplayMode::StackedList)
        ->and($field->hasCustomDisplay())->toBeTrue();
});

it('gives Table priority over StackedList', function () {
    $field = ModalTableSelect::make('posts')
        ->multiple()
        ->stackedList()
        ->displayAsTable();

    expect($field->getDisplayMode())->toBe(DisplayMode::Table);
});

it('resolves primary from an attribute path', function () {
    $record = (new Post)->forceFill(['id' => 1, 'title' => 'Hello']);

    $field = ModalTableSelect::make('posts')->stackedListPrimary('title');

    expect($field->getStackedListPrimary($record))->toBe('Hello');
});

it('resolves primary from a closure', function () {
    $record = (new Post)->forceFill(['id' => 1, 'title' => 'Hello']);

    $field = ModalTableSelect::make('posts')
        ->stackedListPrimary(fn (Post $record): string => strtoupper($record->title));

    expect($field->getStackedListPrimary($record))->toBe('HELLO');
});

it('falls back to the record key for primary when nothing is configured', function () {
    $record = (new Post)->forceFill(['id' => 7]);

    expect(ModalTableSelect::make('posts')->getStackedListPrimary($record))->toBe('7');
});

it('resolves secondary and image, defaulting to null', function () {
    $record = (new Post)->forceFill(['id' => 1, 'title' => 'Hello', 'body' => 'World']);

    $field = ModalTableSelect::make('posts')->stackedListSecondary('body');

    expect($field->getStackedListSecondary($record))->toBe('World')
        ->and($field->getStackedListImage($record))->toBeNull();
});

it('is removable by default and toggleable', function () {
    expect(ModalTableSelect::make('posts')->getIsStackedListRemovable())->toBeTrue()
        ->and(ModalTableSelect::make('posts')->stackedListRemovable(false)->getIsStackedListRemovable())->toBeFalse();
});

it('has no display limit by default', function () {
    expect(ModalTableSelect::make('posts')->getDisplayLimit())->toBeNull();
});

it('evaluates the display limit', function () {
    expect(ModalTableSelect::make('posts')->displayLimit(fn (): int => 3)->getDisplayLimit())->toBe(3);
});
