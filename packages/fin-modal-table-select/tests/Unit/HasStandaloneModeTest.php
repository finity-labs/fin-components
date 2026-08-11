<?php

declare(strict_types=1);

use FinityLabs\FinModalTableSelect\Components\ModalTableSelect;
use FinityLabs\FinModalTableSelect\Tests\Fixtures\Models\Post;

it('is not standalone by default', function () {
    expect(ModalTableSelect::make('posts')->getIsStandalone())->toBeFalse();
});

it('is standalone after standalone() is called', function () {
    $field = ModalTableSelect::make('post_ids')->standalone(Post::class);

    expect($field->getIsStandalone())->toBeTrue()
        ->and($field->getStandaloneModel())->toBe(Post::class);
});

it('builds a query for the standalone model', function () {
    $field = ModalTableSelect::make('post_ids')->standalone(Post::class);

    expect($field->getStandaloneQuery()->getModel())->toBeInstanceOf(Post::class);
});

it('applies standaloneModifyQueryUsing', function () {
    $field = ModalTableSelect::make('post_ids')
        ->standalone(Post::class)
        ->standaloneModifyQueryUsing(fn ($query) => $query->where('title', 'like', 'A%'));

    expect($field->getStandaloneQuery()->toSql())->toContain('"title" like ?');
});

it('uses the standalone title attribute for record labels', function () {
    $record = (new Post)->forceFill(['id' => 1, 'title' => 'Hello']);

    $field = ModalTableSelect::make('post_ids')->standalone(Post::class, 'title');

    expect($field->getRecordDisplayLabel($record))->toBe('Hello');
});

it('falls back to the record key for labels without a title attribute', function () {
    $record = (new Post)->forceFill(['id' => 9]);

    $field = ModalTableSelect::make('post_ids')->standalone(Post::class);

    expect($field->getRecordDisplayLabel($record))->toBe('9');
});
