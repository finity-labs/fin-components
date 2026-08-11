<?php

declare(strict_types=1);

use FinityLabs\FinModalTableSelect\Components\ModalTableSelect;
use FinityLabs\FinModalTableSelect\Tests\Fixtures\Models\Post;
use FinityLabs\FinModalTableSelect\Tests\Fixtures\Models\User;

it('is chainable', function () {
    $field = ModalTableSelect::make('company_id');

    expect($field->fillsFields(['name' => 'name']))->toBe($field);
});

it('resolves attribute paths from the record', function () {
    $record = (new Post)->forceFill(['id' => 1, 'title' => 'Hello', 'body' => 'World']);

    $values = ModalTableSelect::make('post_id')
        ->fillsFields([
            'heading' => 'title',
            'content' => 'body',
        ])
        ->resolveFieldFills($record);

    expect($values)->toBe(['heading' => 'Hello', 'content' => 'World']);
});

it('resolves dot-notation paths through relationships', function () {
    $user = User::query()->create(['name' => 'Jane', 'email' => 'jane@example.com']);
    $post = $user->posts()->create(['title' => 'Hello']);

    $values = ModalTableSelect::make('post_id')
        ->fillsFields([
            'author_name' => 'user.name',
        ])
        ->resolveFieldFills($post->fresh());

    expect($values)->toBe(['author_name' => 'Jane']);
});

it('resolves closures with the record injected', function () {
    $record = (new Post)->forceFill(['id' => 1, 'title' => 'Hello']);

    $values = ModalTableSelect::make('post_id')
        ->fillsFields([
            'slug' => fn (Post $record): string => strtolower($record->title).'-slug',
        ])
        ->resolveFieldFills($record);

    expect($values)->toBe(['slug' => 'hello-slug']);
});

it('resolves every target to null on deselection', function () {
    $values = ModalTableSelect::make('post_id')
        ->fillsFields([
            'heading' => 'title',
            'slug' => fn (Post $record): string => 'never',
        ])
        ->resolveFieldFills(null);

    expect($values)->toBe(['heading' => null, 'slug' => null]);
});

it('evaluates a Closure for the whole map', function () {
    $record = (new Post)->forceFill(['id' => 1, 'title' => 'Hello']);

    $values = ModalTableSelect::make('post_id')
        ->fillsFields(fn (): array => ['heading' => 'title'])
        ->resolveFieldFills($record);

    expect($values)->toBe(['heading' => 'Hello']);
});
