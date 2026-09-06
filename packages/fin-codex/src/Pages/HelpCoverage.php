<?php

declare(strict_types=1);

namespace FinityLabs\FinCodex\Pages;

use BackedEnum;
use Filament\Facades\Filament;
use Filament\Pages\Page;
use Filament\Schemas\Components\EmbeddedTable;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Concerns\InteractsWithTable;
use Filament\Tables\Contracts\HasTable;
use Filament\Tables\Table;
use FinityLabs\FinCodex\Coverage\CoverageReport;
use FinityLabs\FinCodex\FinCodexPlugin;
use Illuminate\Contracts\Support\Htmlable;
use Illuminate\Pagination\LengthAwarePaginator;
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

    public function table(Table $table): Table
    {
        return $table
            ->records(fn (int|string $page, int|string $recordsPerPage): LengthAwarePaginator => $this->paginate([], (int) $page, $recordsPerPage))
            ->columns([
                TextColumn::make('label')
                    ->label(__('fin-codex::fin-codex.coverage.columns.page')),
            ]);
    }

    /**
     * One page of rows, keyed by the row key.
     *
     * @param  array<string, array<string, mixed>>  $rows
     */
    private function paginate(array $rows, int $page, int|string $recordsPerPage): LengthAwarePaginator
    {
        $perPage = $recordsPerPage === 'all' ? max(1, count($rows)) : (int) $recordsPerPage;

        return new LengthAwarePaginator(
            array_slice($rows, ($page - 1) * $perPage, $perPage, preserve_keys: true),
            count($rows),
            $perPage,
            $page,
        );
    }
}
