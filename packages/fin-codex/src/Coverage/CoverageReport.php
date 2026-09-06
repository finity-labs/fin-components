<?php

declare(strict_types=1);

namespace FinityLabs\FinCodex\Coverage;

use Filament\Facades\Filament;
use Filament\Pages\Page as BasePage;
use Filament\Resources\Pages\Page as ResourcePage;
use FinityLabs\FinCodex\Editor\ContextPicker;
use FinityLabs\LinCodex\Contracts\ContentSource;
use FinityLabs\LinCodex\Coverage\RouteCoverage;
use FinityLabs\LinCodex\Coverage\RouteCoverageRow;
use Illuminate\Contracts\Foundation\Application;
use Illuminate\Database\QueryException;
use Illuminate\Support\Str;
use Spatie\LaravelSettings\Exceptions\MissingSettings;

/**
 * Which screens have a help article, as the coverage page and its badge see
 * it. Built once per request over one RouteCoverage::report() and one
 * ContentSource::all().
 *
 * This is a VIEW over the core's report, not a second report. RouteCoverage
 * decides which routes are pages at all (GET, named, session-starting, not
 * matching a lin-codex.coverage.ignore glob, not vendor-namespaced) and this
 * class only regroups what it returns. Two things are added on top, and both
 * exist because a Filament resource registers each page as its OWN invokable
 * controller (Resources\Pages\Page::route() is Route::get($path, static::class)):
 *
 *  1. The collapse. RouteCoverage derives ListUsers, CreateUser and EditUser
 *     for one resource. helpClass() folds them back onto the resource class,
 *     which is the identity PageIdentity::pageClass() hands the drawer.
 *  2. The class credit. lin-codex's PatternMatcher matches class: keys exactly,
 *     with no inheritance walk, so `class:UserResource` never wins a resource
 *     route in report() — while the drawer on that page matches it every time.
 *     candidates() is asked again with the help class so the page agrees with
 *     the drawer.
 *
 * The number this produces is therefore NOT `codex:coverage`'s exit-code
 * number: the console counts routes and credits only what report() matched,
 * this counts screens and additionally credits a resource-class context. The
 * README says so (Phase 8).
 *
 * ContextResolver is deliberately not used: it applies ArticleGate and the
 * locale pick, and coverage asks whether a MAPPING exists, not whether the
 * current viewer may read it — RouteCoverage's own documented stance.
 */
final class CoverageReport
{
    /** The panel filter's value for rows that belong to no panel. */
    public const OUTSIDE_PANELS = '__outside';

    public function __construct(private readonly Application $app) {}

    /**
     * @return list<CoverageRow>
     */
    public function rows(): array
    {
        return $this->build();
    }

    /** Uncovered rows of one panel — the badge number, and the default view's count. */
    public function uncovered(?string $panelId): int
    {
        $count = 0;

        foreach ($this->rows() as $row) {
            if ($row->panelId === $panelId && ! $row->covered()) {
                $count++;
            }
        }

        return $count;
    }

    /**
     * Panel ids present in the report, plus OUTSIDE_PANELS when any row has
     * none. The coverage page's filter options.
     *
     * @return array<string, string>
     */
    public function panelOptions(): array
    {
        $options = [];
        $outside = false;

        foreach ($this->rows() as $row) {
            if ($row->panelId === null) {
                $outside = true;

                continue;
            }

            $options[$row->panelId] ??= $row->panelId;
        }

        if ($outside) {
            $options[self::OUTSIDE_PANELS] = (string) __('fin-codex::fin-codex.coverage.outside_panels');
        }

        return $options;
    }

    /**
     * The help identity of a route's derived page class — PageIdentity's rule:
     * a resource page answers with its resource, a custom Filament page with
     * itself, anything else (plain controller, closure, unresolved) with null.
     *
     * Resources\Pages\Page extends BasePage, not Pages\Page, so the two checks
     * are disjoint; the resource check comes first for readability.
     *
     * @param  class-string|string|null  $pageClass
     */
    public static function helpClass(?string $pageClass): ?string
    {
        if ($pageClass === null || $pageClass === '' || ! class_exists($pageClass)) {
            return null;
        }

        if (is_subclass_of($pageClass, ResourcePage::class)) {
            return $pageClass::getResource();
        }

        return is_subclass_of($pageClass, BasePage::class) ? ltrim($pageClass, '\\') : null;
    }

    /**
     * One row per screen, in the order the routes of that screen first appear
     * in the core's report.
     *
     * @return list<CoverageRow>
     */
    private function build(): array
    {
        try {
            $report = $this->app->make(RouteCoverage::class)->report();
            $this->app->make(ContentSource::class)->all();
        } catch (MissingSettings|QueryException) {
            // A fresh install before the settings migration must not break
            // every panel page through the navigation badge.
            return [];
        }

        // Both picker methods rebuild from the panel registry and the router's
        // route collection on every call, so they are asked once per report,
        // never once per row. A null panel unions every panel, which keeps the
        // first registered label for a class two panels share.
        $picker = $this->app->make(ContextPicker::class);
        $classLabels = $picker->classKeys(null);
        $routeLabels = $picker->routeKeys(null);

        /** @var array<string, array{panelId: ?string, helpClass: ?string, routes: list<RouteCoverageRow>}> $groups */
        $groups = [];

        foreach ($report as $route) {
            $panelId = $this->panelId($route->name);
            $helpClass = self::helpClass($route->pageClass);
            $key = $helpClass !== null ? $panelId.'|'.$helpClass : $route->name;

            $groups[$key] ??= ['panelId' => $panelId, 'helpClass' => $helpClass, 'routes' => []];
            $groups[$key]['routes'][] = $route;
        }

        $rows = [];

        foreach ($groups as $group) {
            $key = $group['routes'][0]->name;

            $rows[] = new CoverageRow(
                key: $key,
                panelId: $group['panelId'],
                helpClass: $group['helpClass'],
                label: $this->label($key, $group['helpClass'], $classLabels, $routeLabels),
                routes: $group['routes'],
                matchedBy: null,
                slug: null,
                isDeclared: false,
                isFileOnly: false,
                articleId: null,
            );
        }

        return $rows;
    }

    /**
     * The panel a route name belongs to. Every panel route is
     * `filament.{id}.…` (a multi-domain panel inserts the domain after the
     * id), so the id is matched with its trailing dot against the registered
     * panels rather than parsed out of the name: ids that prefix one another
     * would otherwise collide.
     */
    private function panelId(string $routeName): ?string
    {
        foreach (Filament::getPanels() as $panel) {
            if (str_starts_with($routeName, 'filament.'.$panel->getId().'.')) {
                return $panel->getId();
            }
        }

        return null;
    }

    /**
     * The navigation label of the screen: the class label when there is a help
     * class, the route label otherwise, and a humanised route name as the last
     * resort — which never returns an empty string.
     *
     * @param  array<string, string>  $classLabels
     * @param  array<string, string>  $routeLabels
     */
    private function label(string $key, ?string $helpClass, array $classLabels, array $routeLabels): string
    {
        if ($helpClass !== null && isset($classLabels[ltrim($helpClass, '\\')])) {
            return $classLabels[ltrim($helpClass, '\\')];
        }

        return $routeLabels[$key] ?? Str::headline(str_replace(['filament.', '.'], ['', ' '], $key));
    }
}
