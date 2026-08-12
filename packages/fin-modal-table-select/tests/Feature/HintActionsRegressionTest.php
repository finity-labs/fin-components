<?php

declare(strict_types=1);

use FinityLabs\FinModalTableSelect\Components\ModalTableSelect;
use FinityLabs\FinModalTableSelect\Tests\Fixtures\Livewire\TestForm;
use FinityLabs\FinModalTableSelect\Tests\Fixtures\Models\User;
use FinityLabs\FinModalTableSelect\Tests\Fixtures\Tables\PostsTable;
use Livewire\Livewire;

/*
 * Regression: Filament v5 renders the hint actions (the select icon, the
 * collapse chevron) through the afterLabel child-schema slot, and calling
 * afterLabel() on the field REPLACES that slot. The selection summary badge
 * must therefore use the wrapper's labelSuffix slot, never afterLabel().
 */
it('renders the select hint action with no summary configured', function () {
    User::query()->create(['name' => 'Jane', 'email' => 'jane@example.com']);

    TestForm::$makeComponents = fn (): array => [
        ModalTableSelect::make('posts')
            ->tableConfiguration(PostsTable::class)
            ->relationship('posts', 'title')
            ->multiple(),
    ];
    TestForm::$record = User::query()->first();

    $html = Livewire::test(TestForm::class)->html();

    expect($html)
        ->toContain('fi-icon-btn')
        ->toContain('mountAction');
});

it('renders the select hint action alongside the selection summary badge', function () {
    $user = User::query()->create(['name' => 'Jane', 'email' => 'jane@example.com']);
    $first = $user->posts()->create(['title' => 'First post']);
    $second = $user->posts()->create(['title' => 'Second post']);

    TestForm::$makeComponents = fn (): array => [
        ModalTableSelect::make('posts')
            ->tableConfiguration(PostsTable::class)
            ->relationship('posts', 'title')
            ->multiple()
            ->stackedList()
            ->selectionSummary(),
    ];
    TestForm::$record = $user;

    $livewire = Livewire::test(TestForm::class);
    $livewire->set('data.posts', [$first->getKey(), $second->getKey()]);

    $html = $livewire->html();

    expect($html)
        ->toContain('2 items selected')
        ->toContain('fi-icon-btn')
        ->toContain('mountAction');
});
