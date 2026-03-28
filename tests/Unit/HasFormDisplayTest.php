<?php

declare(strict_types=1);

use Filament\Forms\Components\TextInput;
use FinityLabs\FinComponents\Components\ModalTableSelect\ModalTableSelect;

it('returns false for hasFormSchema when none configured', function () {
    $field = ModalTableSelect::make('posts');

    expect($field->hasFormSchema())->toBeFalse();
});

it('returns true for hasFormSchema after formSchema set', function () {
    $field = ModalTableSelect::make('posts')
        ->formSchema([
            TextInput::make('title'),
        ]);

    expect($field->hasFormSchema())->toBeTrue();
});

it('defaults getFormColumns to 1', function () {
    $field = ModalTableSelect::make('posts');

    expect($field->getFormColumns())->toBe(1);
});

it('returns custom value for getFormColumns', function () {
    $field = ModalTableSelect::make('posts')
        ->formColumns(2);

    expect($field->getFormColumns())->toBe(2);
});

it('evaluates Closure for formColumns', function () {
    $field = ModalTableSelect::make('posts')
        ->formColumns(fn () => 3);

    expect($field->getFormColumns())->toBe(3);
});
