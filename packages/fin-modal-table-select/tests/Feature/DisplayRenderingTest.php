<?php

declare(strict_types=1);

use Filament\Infolists\Components\RepeatableEntry\TableColumn;
use Filament\Infolists\Components\TextEntry;
use FinityLabs\FinModalTableSelect\Components\ModalTableSelect;
use FinityLabs\FinModalTableSelect\Tests\Fixtures\Livewire\TestForm;
use FinityLabs\FinModalTableSelect\Tests\Fixtures\Models\User;
use FinityLabs\FinModalTableSelect\Tests\Fixtures\Tables\PostsTable;
use FinityLabs\FinModalTableSelect\Tests\Fixtures\Tables\UsersTable;
use Livewire\Features\SupportTesting\Testable;
use Livewire\Livewire;

function mountForm(callable $makeComponents, $record = null): Testable
{
    TestForm::$makeComponents = Closure::fromCallable($makeComponents);
    TestForm::$record = $record;

    return Livewire::test(TestForm::class);
}

function pickerField(Testable $livewire, string $name = 'posts'): ModalTableSelect
{
    return $livewire->instance()->getSchema('form')->getFlatFields()[$name];
}

it('renders selected records as table rows bound to their models', function () {
    $user = User::query()->create(['name' => 'Jane', 'email' => 'jane@example.com']);
    $first = $user->posts()->create(['title' => 'First post']);
    $second = $user->posts()->create(['title' => 'Second post']);

    $livewire = mountForm(fn (): array => [
        ModalTableSelect::make('posts')
            ->tableConfiguration(PostsTable::class)
            ->relationship('posts', 'title')
            ->multiple()
            ->tableColumns([TableColumn::make('Title')])
            ->tableSchema([TextEntry::make('title')]),
    ], $user);

    $livewire->set('data.posts', [$first->getKey(), $second->getKey()]);

    $field = pickerField($livewire);

    $schema = $field->makeSelectedTableSchema();

    expect($schema)->not->toBeNull();

    $html = $schema->toHtml();

    expect($html)
        ->toContain('First post')
        ->toContain('Second post');
});

it('preserves selection order in getSelectedRecords', function () {
    $user = User::query()->create(['name' => 'Jane', 'email' => 'jane@example.com']);
    $first = $user->posts()->create(['title' => 'First post']);
    $second = $user->posts()->create(['title' => 'Second post']);

    $livewire = mountForm(fn (): array => [
        ModalTableSelect::make('posts')
            ->tableConfiguration(PostsTable::class)
            ->relationship('posts', 'title')
            ->multiple()
            ->tableSchema([TextEntry::make('title')]),
    ], $user);

    $livewire->set('data.posts', [$second->getKey(), $first->getKey()]);

    $records = pickerField($livewire)->getSelectedRecords();

    expect($records->pluck('title')->all())->toBe(['Second post', 'First post']);
});

it('memoizes getSelectedRecords per state and refreshes on change', function () {
    $user = User::query()->create(['name' => 'Jane', 'email' => 'jane@example.com']);
    $first = $user->posts()->create(['title' => 'First post']);
    $second = $user->posts()->create(['title' => 'Second post']);

    $livewire = mountForm(fn (): array => [
        ModalTableSelect::make('posts')
            ->tableConfiguration(PostsTable::class)
            ->relationship('posts', 'title')
            ->multiple()
            ->tableSchema([TextEntry::make('title')]),
    ], $user);

    $livewire->set('data.posts', [$first->getKey()]);

    $field = pickerField($livewire);

    expect($field->getSelectedRecords())->toBe($field->getSelectedRecords())
        ->and($field->getSelectedRecords()->pluck('title')->all())->toBe(['First post']);

    $livewire->set('data.posts', [$first->getKey(), $second->getKey()]);

    expect(pickerField($livewire)->getSelectedRecords()->pluck('title')->all())
        ->toBe(['First post', 'Second post']);
});

it('renders the selected record as an infolist bound to the model', function () {
    $user = User::query()->create(['name' => 'Jane', 'email' => 'jane@example.com']);
    $post = $user->posts()->create(['title' => 'Hello world']);

    $livewire = mountForm(fn (): array => [
        ModalTableSelect::make('user')
            ->tableConfiguration(UsersTable::class)
            ->relationship('user', 'name')
            ->infolistSchema([
                TextEntry::make('name'),
                TextEntry::make('email'),
            ]),
    ], $post);

    $livewire->set('data.user', $user->getKey());

    $schema = pickerField($livewire, 'user')->makeSelectedInfolistSchema();

    expect($schema)->not->toBeNull();

    $html = $schema->toHtml();

    expect($html)
        ->toContain('Jane')
        ->toContain('jane@example.com');
});

it('returns null schemas when nothing is selected', function () {
    $user = User::query()->create(['name' => 'Jane', 'email' => 'jane@example.com']);

    $livewire = mountForm(fn (): array => [
        ModalTableSelect::make('posts')
            ->tableConfiguration(PostsTable::class)
            ->relationship('posts', 'title')
            ->multiple()
            ->tableSchema([TextEntry::make('title')]),
    ], $user);

    $field = pickerField($livewire);

    expect($field->makeSelectedTableSchema())->toBeNull()
        ->and($field->getSelectedRecords())->toBeEmpty();
});
