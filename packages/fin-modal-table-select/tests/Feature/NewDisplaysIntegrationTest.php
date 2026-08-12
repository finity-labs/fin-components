<?php

declare(strict_types=1);

use Filament\Forms\Components\TextInput;
use FinityLabs\FinModalTableSelect\Components\ModalTableSelect;
use FinityLabs\FinModalTableSelect\Components\SelectedItemsRepeater;
use FinityLabs\FinModalTableSelect\Tests\Fixtures\Livewire\TestForm;
use FinityLabs\FinModalTableSelect\Tests\Fixtures\Models\Post;
use FinityLabs\FinModalTableSelect\Tests\Fixtures\Models\User;
use FinityLabs\FinModalTableSelect\Tests\Fixtures\Tables\PostsTable;
use Livewire\Features\SupportTesting\Testable;
use Livewire\Livewire;

function mountDisplayForm(callable $makeComponents, $record = null): Testable
{
    TestForm::$makeComponents = Closure::fromCallable($makeComponents);
    TestForm::$record = $record;

    return Livewire::test(TestForm::class);
}

function displayField(Testable $livewire, string $name): ModalTableSelect
{
    return $livewire->instance()->getSchema('form')->getFlatFields()[$name];
}

it('loads records and labels in standalone mode without a relationship', function () {
    $user = User::query()->create(['name' => 'Jane', 'email' => 'jane@example.com']);
    $first = $user->posts()->create(['title' => 'Standalone one']);
    $second = $user->posts()->create(['title' => 'Standalone two']);

    $livewire = mountDisplayForm(fn (): array => [
        ModalTableSelect::make('post_ids')
            ->tableConfiguration(PostsTable::class)
            ->standalone(Post::class, 'title')
            ->multiple(),
    ]);

    $livewire->set('data.post_ids', [$first->getKey(), $second->getKey()]);

    $field = displayField($livewire, 'post_ids');

    expect($field->getSelectedRecords()->pluck('title')->all())
        ->toBe(['Standalone one', 'Standalone two'])
        ->and($field->getOptionLabels())
        ->toBe([
            $first->getKey() => 'Standalone one',
            $second->getKey() => 'Standalone two',
        ]);
});

it('resolves the standalone single record through the parent pipeline', function () {
    $user = User::query()->create(['name' => 'Jane', 'email' => 'jane@example.com']);
    $post = $user->posts()->create(['title' => 'Single standalone']);

    $livewire = mountDisplayForm(fn (): array => [
        ModalTableSelect::make('post_id')
            ->tableConfiguration(PostsTable::class)
            ->standalone(Post::class, 'title'),
    ]);

    $livewire->set('data.post_id', $post->getKey());

    $field = displayField($livewire, 'post_id');

    expect($field->getSelectedRecord()?->title)->toBe('Single standalone')
        ->and($field->getOptionLabel())->toBe('Single standalone');
});

it('renders the stacked list with primary, secondary, and remove buttons', function () {
    $user = User::query()->create(['name' => 'Jane', 'email' => 'jane@example.com']);
    $first = $user->posts()->create(['title' => 'First post', 'body' => 'First body']);
    $second = $user->posts()->create(['title' => 'Second post', 'body' => 'Second body']);

    $livewire = mountDisplayForm(fn (): array => [
        ModalTableSelect::make('posts')
            ->tableConfiguration(PostsTable::class)
            ->relationship('posts', 'title')
            ->multiple()
            ->stackedList()
            ->stackedListSecondary('body'),
    ], $user);

    $livewire->set('data.posts', [$first->getKey(), $second->getKey()]);

    $html = $livewire->html();

    expect($html)
        ->toContain('fi-fo-modal-table-select-stacked')
        ->toContain('First post')
        ->toContain('First body')
        ->toContain('Second post')
        ->toContain('removeSelectedItem');
});

it('collapses items beyond the display limit behind a +N more toggle', function () {
    $user = User::query()->create(['name' => 'Jane', 'email' => 'jane@example.com']);

    foreach (range(1, 5) as $i) {
        $user->posts()->create(['title' => "Post {$i}"]);
    }

    $livewire = mountDisplayForm(fn (): array => [
        ModalTableSelect::make('posts')
            ->tableConfiguration(PostsTable::class)
            ->relationship('posts', 'title')
            ->multiple()
            ->stackedList()
            ->displayLimit(2),
    ], $user);

    $livewire->set('data.posts', $user->posts()->pluck('id')->all());

    $html = $livewire->html();

    expect($html)
        ->toContain('+3 more')
        ->toContain('Show less');
});

it('removes a single item from the selection and reruns fills', function () {
    $user = User::query()->create(['name' => 'Jane', 'email' => 'jane@example.com']);
    $first = $user->posts()->create(['title' => 'First post']);
    $second = $user->posts()->create(['title' => 'Second post']);

    $livewire = mountDisplayForm(fn (): array => [
        ModalTableSelect::make('posts')
            ->tableConfiguration(PostsTable::class)
            ->relationship('posts', 'title')
            ->multiple()
            ->stackedList()
            ->fillsRepeater('items', fn (Post $record): array => [
                'post_id' => $record->getKey(),
                'title' => $record->title,
            ], keyAttribute: 'post_id'),
        SelectedItemsRepeater::make('items')
            ->for('posts')
            ->schema([
                TextInput::make('post_id'),
                TextInput::make('title'),
            ]),
    ], $user);

    $field = displayField($livewire, 'posts');
    $field->state([$first->getKey(), $second->getKey()]);
    $field->callAfterStateUpdated();

    expect($livewire->get('data.items'))->toHaveCount(2);

    $field->removeSelectedItem($second->getKey());

    expect($field->getState())->toBe([$first->getKey()])
        ->and($livewire->get('data.items'))->toHaveCount(1);
});

it('syncs the picker state when a repeater row is deleted', function () {
    $user = User::query()->create(['name' => 'Jane', 'email' => 'jane@example.com']);
    $first = $user->posts()->create(['title' => 'First post']);
    $second = $user->posts()->create(['title' => 'Second post']);

    $livewire = mountDisplayForm(fn (): array => [
        ModalTableSelect::make('posts')
            ->tableConfiguration(PostsTable::class)
            ->relationship('posts', 'title')
            ->multiple()
            ->selectionOnly()
            ->fillsRepeater('items', fn (Post $record): array => [
                'post_id' => $record->getKey(),
                'title' => $record->title,
            ], keyAttribute: 'post_id'),
        SelectedItemsRepeater::make('items')
            ->for('posts')
            ->schema([
                TextInput::make('post_id'),
                TextInput::make('title'),
            ]),
    ], $user);

    $field = displayField($livewire, 'posts');
    $field->state([$first->getKey(), $second->getKey()]);
    $field->callAfterStateUpdated();

    /** @var SelectedItemsRepeater $repeater */
    $repeater = $livewire->instance()->getSchema('form')->getFlatFields()['items'];

    expect($repeater->isAddable())->toBeFalse()
        ->and($repeater->getPicker())->toBe($field);

    // Simulate the delete action: drop the second row from the repeater
    // state, then run the after-delete hook.
    $items = $livewire->get('data.items');
    $secondRowKey = array_search($second->getKey(), array_map(fn ($row) => $row['post_id'], $items));
    unset($items[$secondRowKey]);
    $repeater->state($items);

    $repeater->syncPickerAfterDelete();

    expect($field->getState())->toBe([$first->getKey()]);
});

it('renders the selection summary as a badge on the label line in any mode', function () {
    $user = User::query()->create(['name' => 'Jane', 'email' => 'jane@example.com']);
    $first = $user->posts()->create(['title' => 'First post']);
    $second = $user->posts()->create(['title' => 'Second post']);

    $livewire = mountDisplayForm(fn (): array => [
        ModalTableSelect::make('posts')
            ->tableConfiguration(PostsTable::class)
            ->relationship('posts', 'title')
            ->multiple()
            ->stackedList()
            ->selectionSummary(),
    ], $user);

    $livewire->set('data.posts', [$first->getKey(), $second->getKey()]);

    expect($livewire->html())->toContain('2 items selected');
});

it('hydrates the picker selection from saved repeater rows', function () {
    $user = User::query()->create(['name' => 'Jane', 'email' => 'jane@example.com']);
    $first = $user->posts()->create(['title' => 'First post']);
    $second = $user->posts()->create(['title' => 'Second post']);

    $livewire = mountDisplayForm(fn (): array => [
        ModalTableSelect::make('posts')
            ->tableConfiguration(PostsTable::class)
            ->relationship('posts', 'title')
            ->multiple()
            ->selectionOnly()
            ->fillsRepeater('items', fn (Post $record): array => [
                'post_id' => $record->getKey(),
                'title' => $record->title,
            ], keyAttribute: 'post_id')
            ->hydrateSelectionFromRepeater(),
        TextInput::make('items')->hidden(),
    ], $user);

    $field = displayField($livewire, 'posts');

    // Simulate an edit page: rows exist, picker state is empty.
    $livewire->instance()->data['items'] = [
        'row-a' => ['post_id' => $first->getKey(), 'title' => 'First post'],
        'row-b' => ['post_id' => $second->getKey(), 'title' => 'Second post'],
    ];

    $field->state(null);
    $field->callAfterStateHydrated();

    expect($field->getState())->toBe([$first->getKey(), $second->getKey()]);
});
