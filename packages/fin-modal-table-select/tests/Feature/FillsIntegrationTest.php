<?php

declare(strict_types=1);

use Filament\Forms\Components\Repeater;
use Filament\Forms\Components\TextInput;
use FinityLabs\FinModalTableSelect\Components\ModalTableSelect;
use FinityLabs\FinModalTableSelect\Tests\Fixtures\Livewire\TestForm;
use FinityLabs\FinModalTableSelect\Tests\Fixtures\Models\Post;
use FinityLabs\FinModalTableSelect\Tests\Fixtures\Models\User;
use FinityLabs\FinModalTableSelect\Tests\Fixtures\Tables\PostsTable;
use FinityLabs\FinModalTableSelect\Tests\Fixtures\Tables\UsersTable;
use Livewire\Features\SupportTesting\Testable;
use Livewire\Livewire;

function mountFillsForm(callable $makeComponents, $record = null): Testable
{
    TestForm::$makeComponents = Closure::fromCallable($makeComponents);
    TestForm::$record = $record;

    return Livewire::test(TestForm::class);
}

function fillsField(Testable $livewire, string $name): ModalTableSelect
{
    return $livewire->instance()->getSchema('form')->getFlatFields()[$name];
}

it('fills sibling fields when the selection changes', function () {
    $user = User::query()->create(['name' => 'Jane', 'email' => 'jane@example.com']);
    $post = $user->posts()->create(['title' => 'Hello world']);

    $livewire = mountFillsForm(fn (): array => [
        ModalTableSelect::make('user')
            ->tableConfiguration(UsersTable::class)
            ->relationship('user', 'name')
            ->fillsFields([
                'author_name' => 'name',
                'author_email' => 'email',
            ]),
        TextInput::make('author_name'),
        TextInput::make('author_email'),
    ], $post);

    $field = fillsField($livewire, 'user');
    $field->state($user->getKey());
    $field->callAfterStateUpdated();

    expect($livewire->get('data.author_name'))->toBe('Jane')
        ->and($livewire->get('data.author_email'))->toBe('jane@example.com');
});

it('clears sibling fields on deselection', function () {
    $user = User::query()->create(['name' => 'Jane', 'email' => 'jane@example.com']);
    $post = $user->posts()->create(['title' => 'Hello world']);

    $livewire = mountFillsForm(fn (): array => [
        ModalTableSelect::make('user')
            ->tableConfiguration(UsersTable::class)
            ->relationship('user', 'name')
            ->fillsFields(['author_name' => 'name']),
        TextInput::make('author_name'),
    ], $post);

    $field = fillsField($livewire, 'user');
    $field->state($user->getKey());
    $field->callAfterStateUpdated();

    expect($livewire->get('data.author_name'))->toBe('Jane');

    $field->state(null);
    $field->callAfterStateUpdated();

    expect($livewire->get('data.author_name'))->toBeNull();
});

it('syncs the selection into a sibling repeater with merge semantics', function () {
    $user = User::query()->create(['name' => 'Jane', 'email' => 'jane@example.com']);
    $first = $user->posts()->create(['title' => 'First post']);
    $second = $user->posts()->create(['title' => 'Second post']);

    $livewire = mountFillsForm(fn (): array => [
        ModalTableSelect::make('posts')
            ->tableConfiguration(PostsTable::class)
            ->relationship('posts', 'title')
            ->multiple()
            ->fillsRepeater('items', fn (Post $record): array => [
                'post_id' => $record->getKey(),
                'title' => $record->title,
                'quantity' => 1,
            ], keyAttribute: 'post_id'),
        Repeater::make('items')
            ->schema([
                TextInput::make('post_id'),
                TextInput::make('title'),
                TextInput::make('quantity'),
            ]),
    ], $user);

    $field = fillsField($livewire, 'posts');

    // Initial selection: both posts become rows.
    $field->state([$first->getKey(), $second->getKey()]);
    $field->callAfterStateUpdated();

    $items = $livewire->get('data.items');

    expect(array_column($items, 'post_id'))->toBe([$first->getKey(), $second->getKey()])
        ->and(array_column($items, 'quantity'))->toBe([1, 1]);

    // User edits a quantity (mutating the live instance directly — a
    // $livewire->set() round-trip would discard the field state written
    // above, which never went through Livewire's snapshot cycle), then
    // deselects the second post.
    $firstRowKey = array_key_first($items);
    $livewire->instance()->data['items'][$firstRowKey]['quantity'] = 42;

    $field->state([$first->getKey()]);
    $field->callAfterStateUpdated();

    $rows = array_values($livewire->get('data.items'));

    expect($rows)->toHaveCount(1)
        ->and($rows[0]['quantity'])->toBe(42)
        ->and($rows[0]['post_id'])->toBe($first->getKey());
});

it('derives table display from the modal tableConfiguration via displayAsTable', function () {
    $user = User::query()->create(['name' => 'Jane', 'email' => 'jane@example.com']);
    $post = $user->posts()->create(['title' => 'Inherited title', 'body' => 'Inherited body']);

    $livewire = mountFillsForm(fn (): array => [
        ModalTableSelect::make('posts')
            ->tableConfiguration(PostsTable::class)
            ->relationship('posts', 'title')
            ->multiple()
            ->displayAsTable(),
    ], $user);

    $livewire->set('data.posts', [$post->getKey()]);

    $field = fillsField($livewire, 'posts');

    expect($field->getTableColumns())->toHaveCount(2)
        ->and($field->getTableSchema())->toHaveCount(2);

    $html = $field->makeSelectedTableSchema()->toHtml();

    expect($html)
        ->toContain('Inherited title')
        ->toContain('Inherited body');
});
