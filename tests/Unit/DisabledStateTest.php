<?php

declare(strict_types=1);

use FinityLabs\FinComponents\Components\ModalTableSelect\ModalTableSelect;

it('is disabled after calling disabled', function () {
    $field = ModalTableSelect::make('posts')
        ->disabled();

    expect($field->isDisabled())->toBeTrue();
});

it('accepts a closure for disabled', function () {
    $field = ModalTableSelect::make('posts')
        ->disabled(fn () => true);

    expect($field->isDisabled())->toBeTrue();
});
