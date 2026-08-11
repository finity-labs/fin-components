<?php

declare(strict_types=1);

use FinityLabs\FinModalTableSelect\Components\ModalTableSelect;
use FinityLabs\FinModalTableSelect\Enums\ListStyle;
use FinityLabs\FinModalTableSelect\Tests\Fixtures\Livewire\TestForm;
use FinityLabs\FinModalTableSelect\Tests\Fixtures\Models\Post;
use FinityLabs\FinModalTableSelect\Tests\Fixtures\Models\User;
use FinityLabs\FinModalTableSelect\Tests\Fixtures\Tables\PostsTable;
use Livewire\Features\SupportTesting\Testable;
use Livewire\Livewire;

beforeEach(function () {
    app('view')->addNamespace('test-fixtures', __DIR__.'/../Fixtures/views');
});

function mountViewTypesForm(callable $makeComponents, $record = null): Testable
{
    TestForm::$makeComponents = Closure::fromCallable($makeComponents);
    TestForm::$record = $record;

    return Livewire::test(TestForm::class);
}

function seedPosts(int $count = 3): array
{
    $user = User::query()->create(['name' => 'Jane', 'email' => 'jane@example.com']);

    $posts = collect(range(1, $count))
        ->map(fn (int $i) => $user->posts()->create(['title' => "Post {$i}", 'body' => "/img/{$i}.jpg"]));

    return [$user, $posts];
}

it('renders a bullet list style', function () {
    [$user, $posts] = seedPosts(2);

    $livewire = mountViewTypesForm(fn (): array => [
        ModalTableSelect::make('posts')
            ->tableConfiguration(PostsTable::class)
            ->relationship('posts', 'title')
            ->multiple()
            ->listStyle(ListStyle::Bullet),
    ], $user);

    $livewire->set('data.posts', $posts->pluck('id')->all());

    expect($livewire->html())
        ->toContain('list-disc')
        ->toContain('Post 1')
        ->toContain('Post 2');
});

it('renders a dot-separated list style', function () {
    [$user, $posts] = seedPosts(2);

    $livewire = mountViewTypesForm(fn (): array => [
        ModalTableSelect::make('posts')
            ->tableConfiguration(PostsTable::class)
            ->relationship('posts', 'title')
            ->multiple()
            ->listStyle(ListStyle::Dot),
    ], $user);

    $livewire->set('data.posts', $posts->pluck('id')->all());

    expect($livewire->html())->toContain('Post 1 · Post 2');
});

it('renders per-record badge colors and icons', function () {
    [$user, $posts] = seedPosts(2);

    $livewire = mountViewTypesForm(fn (): array => [
        ModalTableSelect::make('posts')
            ->tableConfiguration(PostsTable::class)
            ->relationship('posts', 'title')
            ->multiple()
            ->badgeColorFromRecord(fn (Post $record): string => $record->title === 'Post 1' ? 'success' : 'danger')
            ->badgeIconFromRecord(fn (): string => 'heroicon-m-star'),
    ], $user);

    $livewire->set('data.posts', $posts->pluck('id')->all());

    $html = $livewire->html();

    expect($html)
        ->toContain('Post 1')
        ->toContain('fi-color-success')
        ->toContain('fi-color-danger');
});

it('renders the card grid with title, description, and remove buttons', function () {
    [$user, $posts] = seedPosts(2);

    $livewire = mountViewTypesForm(fn (): array => [
        ModalTableSelect::make('posts')
            ->tableConfiguration(PostsTable::class)
            ->relationship('posts', 'title')
            ->multiple()
            ->cardGrid()
            ->cardTitle('title')
            ->cardDescription('body')
            ->cardColumns(2),
    ], $user);

    $livewire->set('data.posts', $posts->pluck('id')->all());

    $html = $livewire->html();

    expect($html)
        ->toContain('fi-fo-modal-table-select-cards')
        ->toContain('Post 1')
        ->toContain('/img/1.jpg')
        ->toContain('removeSelectedItem');
});

it('renders the thumbnail strip with tooltips and initials fallback', function () {
    [$user, $posts] = seedPosts(2);
    $noImage = $user->posts()->create(['title' => 'No image']);

    $livewire = mountViewTypesForm(fn (): array => [
        ModalTableSelect::make('posts')
            ->tableConfiguration(PostsTable::class)
            ->relationship('posts', 'title')
            ->multiple()
            ->thumbnails('body'),
    ], $user);

    $livewire->set('data.posts', [...$posts->pluck('id')->all(), $noImage->getKey()]);

    $html = $livewire->html();

    expect($html)
        ->toContain('fi-fo-modal-table-select-thumbs')
        ->toContain('src="/img/1.jpg"')
        ->toContain('title="Post 1"')
        ->toContain('title="No image"');
});

it('renders each record through a custom item view', function () {
    [$user, $posts] = seedPosts(2);

    $livewire = mountViewTypesForm(fn (): array => [
        ModalTableSelect::make('posts')
            ->tableConfiguration(PostsTable::class)
            ->relationship('posts', 'title')
            ->multiple()
            ->itemView('test-fixtures::demo-item'),
    ], $user);

    $livewire->set('data.posts', $posts->pluck('id')->all());

    $html = $livewire->html();

    expect($html)
        ->toContain('demo-item-view')
        ->toContain('ITEM: Post 1')
        ->toContain('ITEM: Post 2')
        ->toContain('removeSelectedItem');
});

it('renders the empty-state select button when enabled', function () {
    [$user] = seedPosts(1);

    $livewire = mountViewTypesForm(fn (): array => [
        ModalTableSelect::make('posts')
            ->tableConfiguration(PostsTable::class)
            ->relationship('posts', 'title')
            ->multiple()
            ->stackedList()
            ->emptyStateSelectButton(),
    ], $user);

    expect($livewire->html())->toContain('Select…');
});
