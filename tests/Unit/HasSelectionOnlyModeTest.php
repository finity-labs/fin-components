<?php

declare(strict_types=1);

use FinityLabs\FinComponents\Components\ModalTableSelect\ModalTableSelect;

it('defaults getIsSelectionOnly to false', function () {
    $field = ModalTableSelect::make('posts');

    expect($field->getIsSelectionOnly())->toBeFalse();
});

it('returns true for getIsSelectionOnly after selectionOnly', function () {
    $field = ModalTableSelect::make('posts')
        ->selectionOnly();

    expect($field->getIsSelectionOnly())->toBeTrue();
});

it('evaluates Closure for selectionOnly', function () {
    $field = ModalTableSelect::make('posts')
        ->selectionOnly(fn () => true);

    expect($field->getIsSelectionOnly())->toBeTrue();
});

it('defaults getHasSelectionSummary to false', function () {
    $field = ModalTableSelect::make('posts');

    expect($field->getHasSelectionSummary())->toBeFalse();
});

it('returns true for getHasSelectionSummary after selectionSummary', function () {
    $field = ModalTableSelect::make('posts')
        ->selectionSummary();

    expect($field->getHasSelectionSummary())->toBeTrue();
});

it('returns translation for getSelectionSummaryLabel when no custom label', function () {
    $field = ModalTableSelect::make('posts');

    expect($field->getSelectionSummaryLabel(1))->toBeString()->not->toBeEmpty();
});

it('replaces count placeholder in custom selectionSummaryLabel', function () {
    $field = ModalTableSelect::make('posts')
        ->selectionSummaryLabel(':count items');

    expect($field->getSelectionSummaryLabel(3))->toBe('3 items');
});
