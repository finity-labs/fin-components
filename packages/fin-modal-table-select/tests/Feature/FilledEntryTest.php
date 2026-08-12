<?php

declare(strict_types=1);

use Filament\Infolists\Components\TextEntry;
use FinityLabs\FinModalTableSelect\Components\FilledEntry;
use FinityLabs\FinModalTableSelect\Components\ModalTableSelect;
use FinityLabs\FinModalTableSelect\Tests\Fixtures\Livewire\TestForm;
use FinityLabs\FinModalTableSelect\Tests\Fixtures\Models\User;
use FinityLabs\FinModalTableSelect\Tests\Fixtures\Tables\UsersTable;
use Livewire\Livewire;

it('fills a FilledEntry target: state lands in the hidden field and renders in the entry', function () {
    $user = User::query()->create(['name' => 'Jane', 'email' => 'jane@example.com']);
    $post = $user->posts()->create(['title' => 'Hello']);

    TestForm::$makeComponents = fn (): array => [
        ModalTableSelect::make('user')
            ->tableConfiguration(UsersTable::class)
            ->relationship('user', 'name')
            ->fillsFields(['author_name' => 'name']),
        FilledEntry::make('author_name', label: 'Author'),
    ];
    TestForm::$record = $post;

    $livewire = Livewire::test(TestForm::class);

    $livewire->set('data.user', $user->getKey());

    expect($livewire->get('data.author_name'))->toBe('Jane')
        ->and($livewire->html())->toContain('Jane')->toContain('Author');
});

it('does not fill a bare infolist entry: entries are not stateful', function () {
    $user = User::query()->create(['name' => 'Jane', 'email' => 'jane@example.com']);
    $post = $user->posts()->create(['title' => 'Hello']);

    TestForm::$makeComponents = fn (): array => [
        ModalTableSelect::make('user')
            ->tableConfiguration(UsersTable::class)
            ->relationship('user', 'name')
            ->fillsFields(['author_name' => 'name']),
        TextEntry::make('author_name'),
    ];
    TestForm::$record = $post;

    $livewire = Livewire::test(TestForm::class);

    $livewire->set('data.user', $user->getKey());

    // The bare entry shadows the state path, so the value never reaches form
    // state — this documents WHY FilledEntry exists.
    expect($livewire->get('data.author_name'))->toBeNull();
});

it('applies the entry modifier on FilledEntry', function () {
    $user = User::query()->create(['name' => 'Jane', 'email' => 'jane@example.com']);
    $post = $user->posts()->create(['title' => 'Hello']);

    TestForm::$makeComponents = fn (): array => [
        ModalTableSelect::make('user')
            ->tableConfiguration(UsersTable::class)
            ->relationship('user', 'name')
            ->fillsFields(['author_name' => 'name']),
        FilledEntry::make('author_name', modifyEntryUsing: fn (TextEntry $entry) => $entry->badge()),
    ];
    TestForm::$record = $post;

    $livewire = Livewire::test(TestForm::class);
    $livewire->set('data.user', $user->getKey());

    expect($livewire->html())->toContain('fi-badge');
});
