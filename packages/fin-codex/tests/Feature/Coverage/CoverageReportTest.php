<?php

use Filament\Pages\Dashboard;
use FinityLabs\FinCodex\Coverage\CoverageReport;
use FinityLabs\FinCodex\Coverage\CoverageRow;
use FinityLabs\FinCodex\Editor\ContextPicker;
use FinityLabs\FinCodex\Resources\ArticleResource;
use FinityLabs\FinCodex\Tests\Fixtures\Pages\AdminHelpSettings;
use FinityLabs\FinCodex\Tests\Fixtures\Pages\Reports;
use FinityLabs\FinCodex\Tests\Fixtures\Resources\UserResource;
use FinityLabs\FinCodex\Tests\Fixtures\Resources\UserResource\Pages\CreateUser;
use FinityLabs\FinCodex\Tests\Fixtures\Resources\UserResource\Pages\EditUser;
use FinityLabs\FinCodex\Tests\Fixtures\Resources\UserResource\Pages\ListUsers;
use FinityLabs\LinCodex\Coverage\RouteCoverage;
use FinityLabs\LinCodex\Coverage\RouteCoverageRow;
use Illuminate\Support\Facades\Route;

/*
 * COV-01's row grain and COV-03's number, on the real fixture route table.
 * Four panels register real routes here, so the collapse is proven against
 * routes Filament actually built rather than against a hand-made list.
 */

/**
 * The report's rows keyed by CoverageRow::key.
 *
 * @return array<string, CoverageRow>
 */
function finCodexCoverageByKey(): array
{
    $rows = [];

    foreach (app(CoverageReport::class)->rows() as $row) {
        $rows[$row->key] = $row;
    }

    return $rows;
}

/**
 * Every row of one panel with one help identity — a list, so "exactly one"
 * is assertable rather than assumed.
 *
 * @return list<CoverageRow>
 */
function finCodexCoverageRowsFor(?string $panelId, ?string $helpClass): array
{
    return array_values(array_filter(
        app(CoverageReport::class)->rows(),
        static fn (CoverageRow $row): bool => $row->panelId === $panelId && $row->helpClass === $helpClass,
    ));
}

/**
 * A named GET route with no Filament page behind it. RouteCoverage reads the
 * router's collection live, so this must be registered before the first
 * rows() call of a test.
 */
function finCodexCoverageShopRoute(): void
{
    Route::get('/shop', fn (): string => '')->name('shop.index')->middleware('web');
}

it('answers the help identity of a page class exactly as PageIdentity does', function (?string $pageClass, ?string $expected): void {
    expect(CoverageReport::helpClass($pageClass))->toBe($expected);
})->with([
    'resource list page' => [ListUsers::class, UserResource::class],
    'resource create page' => [CreateUser::class, UserResource::class],
    'resource edit page' => [EditUser::class, UserResource::class],
    'custom Filament page' => [Reports::class, Reports::class],
    'host settings page' => [AdminHelpSettings::class, AdminHelpSettings::class],
    'plain controller' => ['App\Http\Controllers\ThingController', null],
    'closure route' => [null, null],
]);

/*
 * A Filament resource registers each of its pages as its own invokable
 * controller, so RouteCoverage reports three routes for one screen. The row
 * folds them back onto the resource, which is the identity the drawer is
 * handed on all three pages.
 */
it('collapses the three routes of a resource into one row per panel', function (): void {
    $admin = finCodexCoverageRowsFor('admin', UserResource::class);
    $staff = finCodexCoverageRowsFor('staff', UserResource::class);

    expect($admin)->toHaveCount(1)
        ->and($staff)->toHaveCount(1)
        ->and($admin[0]->routeCount())->toBe(3)
        ->and($admin[0]->routeNames())->toBe([
            'filament.admin.resources.users.create',
            'filament.admin.resources.users.edit',
            'filament.admin.resources.users.index',
        ])
        ->and($staff[0]->routeCount())->toBe(3)
        ->and($staff[0]->routeNames())->toBe([
            'filament.staff.resources.users.create',
            'filament.staff.resources.users.edit',
            'filament.staff.resources.users.index',
        ]);
});

/*
 * The key is an identifier, not a display value: it is the first route name
 * in report order (the report is sorted by name, so "create" precedes
 * "edit" and "index"), and it is what Filament copies onto __key and what a
 * row action is mounted with.
 */
it('keys a collapsed row by the first route name in report order', function (): void {
    $admin = finCodexCoverageRowsFor('admin', UserResource::class);

    expect($admin[0]->key)->toBe('filament.admin.resources.users.create')
        ->and($admin[0]->key)->toBe($admin[0]->routes[0]->name);
});

it('keeps a custom Filament page as a single row', function (): void {
    $rows = finCodexCoverageByKey();

    expect($rows)->toHaveKey('filament.admin.pages.reports')
        ->and($rows['filament.admin.pages.reports']->helpClass)->toBe(Reports::class)
        ->and($rows['filament.admin.pages.reports']->panelId)->toBe('admin')
        ->and($rows['filament.admin.pages.reports']->routeCount())->toBe(1)
        ->and($rows['filament.admin.pages.dashboard']->helpClass)->toBe(Dashboard::class)
        ->and($rows['filament.admin.pages.dashboard']->routeCount())->toBe(1);
});

it('keeps a route with no Filament page behind it as its own row', function (): void {
    finCodexCoverageShopRoute();

    $rows = finCodexCoverageByKey();

    expect($rows)->toHaveKey('shop.index')
        ->and($rows['shop.index']->helpClass)->toBeNull()
        ->and($rows['shop.index']->panelId)->toBeNull()
        ->and($rows['shop.index']->routeCount())->toBe(1)
        ->and($rows['shop.index']->routes[0]->uri)->toBe('/shop');
});

it('reads every row with a human label rather than a route name', function (): void {
    finCodexCoverageShopRoute();

    $labels = app(ContextPicker::class)->classKeys(null);
    $rows = finCodexCoverageByKey();
    $admin = finCodexCoverageRowsFor('admin', UserResource::class);

    expect($admin[0]->label)->toBe($labels[UserResource::class])
        ->and($admin[0]->label)->not->toContain('filament.')
        ->and($rows['filament.admin.pages.reports']->label)->toBe($labels[Reports::class])
        ->and($rows['shop.index']->label)->not->toBe('')
        ->and($rows['shop.index']->label)->not->toContain('filament.');

    foreach ($rows as $row) {
        expect($row->label)->not->toBe('');
    }
});

it('offers every panel present in the report plus the outside-panels bucket as filter options', function (): void {
    finCodexCoverageShopRoute();

    $options = app(CoverageReport::class)->panelOptions();

    expect(array_keys($options))->toContain('admin', 'staff', 'portal', 'plain', CoverageReport::OUTSIDE_PANELS)
        ->and($options[CoverageReport::OUTSIDE_PANELS])->not->toBe('');
});

/*
 * The view must not lose a route the core reported: every reported name
 * belongs to exactly one row.
 */
it('places every route of the core report in exactly one row', function (): void {
    finCodexCoverageShopRoute();

    $reported = array_map(static fn (RouteCoverageRow $row): string => $row->name, app(RouteCoverage::class)->report());
    $placed = [];

    foreach (app(CoverageReport::class)->rows() as $row) {
        $placed = [...$placed, ...$row->routeNames()];
    }

    sort($reported);
    sort($placed);

    expect($placed)->toBe($reported)
        ->and($placed)->toBe(array_values(array_unique($placed)))
        ->and($reported)->toContain('shop.index');
});

it('folds a host resource subclass onto the package resource the pages name', function (): void {
    // Admin registers AdminHelpArticleResource, but its pages are the
    // package's own, whose getResource() answers ArticleResource — the same
    // class PageIdentity hands the drawer on those pages.
    $rows = finCodexCoverageRowsFor('admin', ArticleResource::class);

    expect($rows)->toHaveCount(1)
        ->and($rows[0]->routeCount())->toBe(3)
        ->and($rows[0]->key)->toBe('filament.admin.resources.help-articles.create');
});
