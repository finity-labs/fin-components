<?php

declare(strict_types=1);

use FinityLabs\FinModalTableSelect\Components\ModalTableSelect;
use FinityLabs\FinModalTableSelect\Enums\DisplayMode;
use FinityLabs\FinModalTableSelect\Enums\ListStyle;
use FinityLabs\FinModalTableSelect\Tests\Fixtures\Models\Post;

it('stays in Badges mode with a list style but disables badges', function () {
    $field = ModalTableSelect::make('posts')->multiple()->listStyle(ListStyle::Bullet);

    expect($field->getDisplayMode())->toBe(DisplayMode::Badges)
        ->and($field->getListStyle())->toBe(ListStyle::Bullet)
        ->and($field->hasBadges())->toBeFalse();
});

it('resolves to Cards mode when cardGrid enabled', function () {
    $field = ModalTableSelect::make('posts')->multiple()->cardGrid();

    expect($field->getDisplayMode())->toBe(DisplayMode::Cards)
        ->and($field->hasCustomDisplay())->toBeTrue()
        ->and($field->getCardColumns())->toBe(3)
        ->and($field->getIsCardsRemovable())->toBeTrue();
});

it('resolves card fields from paths and closures with label fallback', function () {
    $record = (new Post)->forceFill(['id' => 3, 'title' => 'Hello', 'body' => 'World']);

    $field = ModalTableSelect::make('posts')
        ->cardGrid()
        ->cardDescription('body')
        ->cardImage(fn (Post $record): string => "/img/{$record->id}.jpg");

    expect($field->getCardTitle($record))->toBe('3')
        ->and($field->getCardDescription($record))->toBe('World')
        ->and($field->getCardImage($record))->toBe('/img/3.jpg');
});

it('resolves to Thumbnails mode when thumbnails set', function () {
    $record = (new Post)->forceFill(['id' => 1, 'body' => '/img/a.jpg']);

    $field = ModalTableSelect::make('posts')->multiple()->thumbnails('body');

    expect($field->getDisplayMode())->toBe(DisplayMode::Thumbnails)
        ->and($field->getThumbnailImage($record))->toBe('/img/a.jpg')
        ->and($field->getIsThumbnailsCircular())->toBeTrue()
        ->and($field->thumbnailsSquare()->getIsThumbnailsCircular())->toBeFalse();
});

it('resolves to ItemView mode with highest custom priority', function () {
    $field = ModalTableSelect::make('posts')
        ->multiple()
        ->displayAsTable()
        ->cardGrid()
        ->itemView('some.view', ['foo' => 'bar']);

    expect($field->getDisplayMode())->toBe(DisplayMode::ItemView)
        ->and($field->getItemView())->toBe('some.view')
        ->and($field->getItemViewData())->toBe(['foo' => 'bar']);
});

it('keeps Table above Cards, Thumbnails, and StackedList', function () {
    $field = ModalTableSelect::make('posts')
        ->multiple()
        ->cardGrid()
        ->thumbnails('body')
        ->stackedList()
        ->displayAsTable();

    expect($field->getDisplayMode())->toBe(DisplayMode::Table);
});

it('reports record badges when a per-record closure is set', function () {
    $field = ModalTableSelect::make('posts')
        ->multiple()
        ->badgeColorFromRecord(fn (Post $record): string => $record->id > 1 ? 'success' : 'danger');

    $first = (new Post)->forceFill(['id' => 1]);
    $second = (new Post)->forceFill(['id' => 2]);

    expect($field->hasRecordBadges())->toBeTrue()
        ->and($field->getBadgeColorForRecord($first))->toBe('danger')
        ->and($field->getBadgeColorForRecord($second))->toBe('success')
        ->and($field->getBadgeIconForRecord($first))->toBeNull();
});

it('resolves per-record badge icons', function () {
    $field = ModalTableSelect::make('posts')
        ->badgeIconFromRecord(fn (Post $record): string => 'heroicon-m-star');

    $record = (new Post)->forceFill(['id' => 1]);

    expect($field->hasRecordBadges())->toBeTrue()
        ->and($field->getBadgeIconForRecord($record))->toBe('heroicon-m-star');
});

it('has no record badges by default', function () {
    expect(ModalTableSelect::make('posts')->hasRecordBadges())->toBeFalse();
});

it('toggles the empty-state select button', function () {
    expect(ModalTableSelect::make('posts')->getHasEmptyStateSelectButton())->toBeFalse()
        ->and(ModalTableSelect::make('posts')->emptyStateSelectButton()->getHasEmptyStateSelectButton())->toBeTrue();
});
