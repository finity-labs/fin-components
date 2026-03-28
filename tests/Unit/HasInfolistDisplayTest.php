<?php

declare(strict_types=1);

use Filament\Infolists\Components\TextEntry;
use FinityLabs\FinComponents\Components\ModalTableSelect\ModalTableSelect;

it('returns false for hasInfolistSchema when none configured', function () {
    $field = ModalTableSelect::make('posts');

    expect($field->hasInfolistSchema())->toBeFalse();
});

it('returns true for hasInfolistSchema after infolistSchema set', function () {
    $field = ModalTableSelect::make('posts')
        ->infolistSchema([
            TextEntry::make('title'),
        ]);

    expect($field->hasInfolistSchema())->toBeTrue();
});

it('defaults getInfolistColumns to 1', function () {
    $field = ModalTableSelect::make('posts');

    expect($field->getInfolistColumns())->toBe(1);
});

it('returns custom value for getInfolistColumns', function () {
    $field = ModalTableSelect::make('posts')
        ->infolistColumns(3);

    expect($field->getInfolistColumns())->toBe(3);
});

it('evaluates Closure for infolistColumns', function () {
    $field = ModalTableSelect::make('posts')
        ->infolistColumns(fn () => 2);

    expect($field->getInfolistColumns())->toBe(2);
});
