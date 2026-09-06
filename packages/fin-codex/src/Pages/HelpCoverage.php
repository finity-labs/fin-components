<?php

declare(strict_types=1);

namespace FinityLabs\FinCodex\Pages;

use BackedEnum;
use Filament\Facades\Filament;
use Filament\Pages\Page;
use Filament\Schemas\Components\EmbeddedTable;
use Filament\Schemas\Schema;
use Filament\Support\Enums\FontWeight;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Concerns\InteractsWithTable;
use Filament\Tables\Contracts\HasTable;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Filters\TernaryFilter;
use Filament\Tables\Table;
use FinityLabs\FinCodex\Coverage\CoverageReport;
use FinityLabs\FinCodex\FinCodexPlugin;
use Illuminate\Contracts\Support\Htmlable;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Str;
use UnitEnum;

/**
 * Which screens of this application have a help article, and which do not.
 *
 * The rows are CoverageReport's, not the core route report's: one row per
 * screen, with a Filament resource's list, create and edit routes folded into
 * one and a resource-class context credited (07-01 explains why the core's own
 * report structurally cannot do either). The number on this page's navigation
 * badge is therefore the number of rows this page shows for this panel by
 * default, and NOT `php artisan codex:coverage`'s route-level exit code. The
 * README says so.
 *
 * The table is array-backed: records() returns a LengthAwarePaginator of plain
 * arrays keyed by the row key, so Filament copies that key onto `__key` and an
 * action's recordKey speaks route names. Filters do nothing by themselves on a
 * records table — the query branch is the only place Filament applies them —
 * so the closure reads and applies them and the filter form is pure state.
 * There are no bulk actions and no row selection on purpose: the "all records"
 * count calls count() on a null query for anything that is not a paginator,
 * which is also why a paginator is returned rather than a plain array.
 *
 * Not final: a coveragePage() override extends it, exactly as HelpSettings is
 * open for settingsPage() and ArticleResource for articleResource().
 */
class HelpCoverage extends Page implements HasTable
{
    use InteractsWithTable;

    protected static ?string $slug = 'help-coverage';

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedClipboardDocumentCheck;

    public static function getNavigationGroup(): string|UnitEnum|null
    {
        return FinCodexPlugin::get()->getNavigationGroup();
    }

    /**
     * Two slots after the article resource: the settings page takes the slot
     * between them, and both read the panel's single navigationSort() option.
     * Null stays null, in which case Filament sorts by label.
     */
    public static function getNavigationSort(): ?int
    {
        $sort = FinCodexPlugin::get()->getNavigationSort();

        return $sort === null ? null : $sort + 2;
    }

    public static function getNavigationLabel(): string
    {
        return (string) __('fin-codex::fin-codex.coverage.navigation');
    }

    public function getTitle(): string|Htmlable
    {
        return (string) __('fin-codex::fin-codex.coverage.title');
    }

    /**
     * The screens of this panel that have no article yet.
     *
     * Read eagerly when the navigation item is built (Filament passes a VALUE,
     * not a closure), which is once per panel page render, so the report's
     * request memo is what keeps this at one reading of the content source.
     * Null at zero, the Phase 3 help-button rule.
     */
    public static function getNavigationBadge(): ?string
    {
        $count = app(CoverageReport::class)->uncovered(Filament::getCurrentPanel()?->getId());

        return $count === 0 ? null : (string) $count;
    }

    public static function getNavigationBadgeColor(): string|array|null
    {
        return 'warning';
    }

    public static function getNavigationBadgeTooltip(): string|Htmlable|null
    {
        return (string) __('fin-codex::fin-codex.coverage.badge_tooltip');
    }

    /** The base Page renders {{ $this->content }}; 07-03 prepends the warnings section here. */
    public function content(Schema $schema): Schema
    {
        return $schema->components([
            EmbeddedTable::make(),
        ]);
    }

    /**
     * The closure injections are resolved by PARAMETER NAME, so none of these
     * may be renamed: search, sort ([column, direction], both null until a
     * header is clicked), filters (the raw table filter state), page and
     * recordsPerPage (which can be the string 'all').
     */
    public function table(Table $table): Table
    {
        return $table
            ->records(fn (?string $search, array $sort, ?array $filters, int|string $page, int|string $recordsPerPage): LengthAwarePaginator => $this->paginate($search, $sort, $filters, (int) $page, $recordsPerPage))
            ->columns([
                TextColumn::make('label')
                    ->label(__('fin-codex::fin-codex.coverage.columns.page'))
                    ->searchable()
                    ->sortable()
                    ->weight(FontWeight::Medium),
                TextColumn::make('panel')
                    ->label(__('fin-codex::fin-codex.coverage.columns.panel'))
                    ->badge()
                    ->color('gray')
                    ->formatStateUsing(fn (?string $state): string => $state ?? (string) __('fin-codex::fin-codex.coverage.outside_panels')),
                TextColumn::make('routes')
                    ->label(__('fin-codex::fin-codex.coverage.columns.routes'))
                    ->alignRight()
                    ->toggleable(isToggledHiddenByDefault: true),
                TextColumn::make('route')
                    ->label(__('fin-codex::fin-codex.coverage.columns.route'))
                    ->color('gray')
                    ->searchable()
                    ->sortable()
                    ->toggleable(),
                TextColumn::make('uri')
                    ->label(__('fin-codex::fin-codex.coverage.columns.uri'))
                    ->color('gray')
                    ->toggleable(isToggledHiddenByDefault: true),
                TextColumn::make('matched')
                    ->label(__('fin-codex::fin-codex.coverage.columns.matched'))
                    ->color('gray')
                    ->placeholder('—'),
                TextColumn::make('slug')
                    ->label(__('fin-codex::fin-codex.coverage.columns.article'))
                    ->badge()
                    ->color(fn (array $record): string => $record['covered'] ? 'success' : 'danger')
                    ->placeholder('—'),
            ])
            ->filters([
                TernaryFilter::make('covered')
                    ->label(__('fin-codex::fin-codex.coverage.filters.covered'))
                    ->trueLabel(__('fin-codex::fin-codex.coverage.filters.covered_true'))
                    ->falseLabel(__('fin-codex::fin-codex.coverage.filters.covered_false')),
                // Opening on the panel the admin is standing in is the whole
                // point of a per-panel badge; clearing it shows every screen
                // of the application, including the ones outside any panel.
                SelectFilter::make('panel')
                    ->label(__('fin-codex::fin-codex.coverage.filters.panel'))
                    ->options(fn (): array => app(CoverageReport::class)->panelOptions())
                    ->default(Filament::getCurrentPanel()?->getId()),
            ])
            ->emptyStateHeading(__('fin-codex::fin-codex.coverage.empty'))
            ->emptyStateDescription(__('fin-codex::fin-codex.coverage.empty_description'))
            ->emptyStateIcon(Heroicon::OutlinedClipboardDocumentCheck);
    }

    /**
     * One page of rows, keyed by the row key.
     *
     * A paginator rather than a plain array for two reasons: a host panel has
     * 50 to 400 screens, and the "all records" count Filament reaches for on
     * anything that is not a paginator would go through a query this table
     * does not have.
     *
     * @param  array{0: string|null, 1: string|null}  $sort
     * @param  array<string, mixed>|null  $filters
     */
    private function paginate(?string $search, array $sort, ?array $filters, int $page, int|string $recordsPerPage): LengthAwarePaginator
    {
        $rows = $this->rows($search, $sort, $filters);
        $perPage = $recordsPerPage === 'all' ? max(1, count($rows)) : (int) $recordsPerPage;

        return new LengthAwarePaginator(
            array_slice($rows, ($page - 1) * $perPage, $perPage, preserve_keys: true),
            count($rows),
            $perPage,
            $page,
        );
    }

    /**
     * Every screen the report knows, filtered, searched and ordered.
     *
     * The report is asked once and closed over: it memoises one reading of the
     * content source per request, and a per-row read would be one reading per
     * screen.
     *
     * @param  array{0: string|null, 1: string|null}  $sort
     * @param  array<string, mixed>|null  $filters
     *
     * @return array<string, array<string, mixed>> keyed by CoverageRow::$key
     */
    private function rows(?string $search, array $sort, ?array $filters): array
    {
        $rows = [];

        foreach (app(CoverageReport::class)->rows() as $row) {
            $rows[$row->key] = $row->toArray();
        }

        $rows = $this->filtered($rows, $filters);

        if (filled($search)) {
            $rows = array_filter($rows, fn (array $row): bool => Str::contains((string) $row['label'], $search, ignoreCase: true)
                || Str::contains((string) $row['route'], $search, ignoreCase: true));
        }

        // Only the two sortable columns can arrive here; anything else keeps
        // the opening order, which puts the gap first and orders each block by
        // the screen's name.
        $column = in_array($sort[0] ?? null, ['label', 'route'], true) ? $sort[0] : null;
        $direction = ($sort[1] ?? 'asc') === 'desc' ? -1 : 1;

        uasort($rows, function (array $a, array $b) use ($column, $direction): int {
            if ($column === null) {
                return ($a['covered'] <=> $b['covered']) ?: strnatcasecmp((string) $a['label'], (string) $b['label']);
            }

            return $direction * strnatcasecmp((string) $a[$column], (string) $b[$column]);
        });

        return $rows;
    }

    /**
     * The panel filter first — it is the default view — then the covered one.
     *
     * The state shape is the filter's own form field: a select filter's field
     * is named `value`. `blank()` is what makes the ternary's `0` and `'0'`
     * read as "no article" rather than as "no filter"; a browser sends those
     * two and a test sends a real bool.
     *
     * @param  array<string, array<string, mixed>>  $rows
     * @param  array<string, mixed>|null  $filters
     *
     * @return array<string, array<string, mixed>>
     */
    private function filtered(array $rows, ?array $filters): array
    {
        $panel = $filters['panel']['value'] ?? null;

        if (filled($panel)) {
            $rows = array_filter($rows, fn (array $row): bool => $panel === CoverageReport::OUTSIDE_PANELS
                ? $row['panel'] === null
                : $row['panel'] === $panel);
        }

        $covered = $filters['covered']['value'] ?? null;

        if (! blank($covered)) {
            $rows = array_filter($rows, fn (array $row): bool => $row['covered'] === (bool) $covered);
        }

        return $rows;
    }
}
