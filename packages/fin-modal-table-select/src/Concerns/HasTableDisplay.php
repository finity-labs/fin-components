<?php

declare(strict_types=1);

namespace FinityLabs\FinModalTableSelect\Concerns;

use Closure;
use Filament\Forms\Components\TableSelect\Livewire\TableSelectLivewireComponent;
use Filament\Infolists\Components\IconEntry;
use Filament\Infolists\Components\ImageEntry;
use Filament\Infolists\Components\RepeatableEntry;
use Filament\Infolists\Components\RepeatableEntry\TableColumn;
use Filament\Infolists\Components\TextEntry;
use Filament\Schemas\Components\Component as SchemaComponent;
use Filament\Schemas\Schema;
use Filament\Support\Services\RelationshipJoiner;
use Filament\Tables\Columns\Column;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\ImageColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection as EloquentCollection;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\Relation;
use Throwable;

trait HasTableDisplay
{
    /** @var array<TableColumn>|Closure|null */
    protected array|Closure|null $tableColumns = null;

    /** @var array<SchemaComponent>|Closure|null */
    protected array|Closure|null $tableSchema = null;

    protected bool|Closure $isDisplayAsTable = false;

    protected string|Closure|null $tableEmptyMessage = null;

    /** @var array<string>|Closure|null */
    protected array|Closure|null $tableEagerLoad = null;

    protected ?Closure $tableModifyQueryUsing = null;

    protected bool|Closure $hasTableFooterCount = false;

    protected bool|Closure $isTableCollapsible = false;

    protected bool|Closure $isTableCollapsed = false;

    protected ?EloquentCollection $cachedSelectedRecords = null;

    protected ?string $cachedSelectedRecordsKey = null;

    /**
     * Display the selected records as a table. With no explicit tableColumns()
     * or tableSchema(), the columns are inherited from the modal's
     * tableConfiguration() class — same table inside and outside the modal.
     */
    public function displayAsTable(bool|Closure $condition = true): static
    {
        $this->isDisplayAsTable = $condition;

        return $this;
    }

    /**
     * Define the header columns for the selected items table.
     *
     * These are infolist RepeatableEntry table columns; their order lines up
     * with the entries passed to tableSchema().
     *
     * @param  array<TableColumn>|Closure  $columns
     */
    public function tableColumns(array|Closure $columns): static
    {
        $this->tableColumns = $columns;

        return $this;
    }

    /**
     * Define the infolist entries rendered for each row of the selected items
     * table. Because these are real infolist entries, Filament applies their
     * formatting (badge(), date(), money(), etc.) for us.
     *
     * @param  array<SchemaComponent>|Closure  $schema
     */
    public function tableSchema(array|Closure $schema): static
    {
        $this->tableSchema = $schema;

        return $this;
    }

    public function tableEmptyMessage(string|Closure|null $message): static
    {
        $this->tableEmptyMessage = $message;

        return $this;
    }

    /**
     * Eager load relationships for selected records in table display.
     *
     * @param  array<string>|Closure  $relationships
     */
    public function tableEagerLoad(array|Closure $relationships): static
    {
        $this->tableEagerLoad = $relationships;

        return $this;
    }

    /**
     * Modify the query used to load the selected records for table display.
     *
     * Useful for aggregates the row entries reference, for example:
     * fn (Builder $query) => $query->withSum('timeEntries', 'duration_minutes').
     */
    public function tableModifyQueryUsing(?Closure $callback): static
    {
        $this->tableModifyQueryUsing = $callback;

        return $this;
    }

    /**
     * Show a footer beneath the table displaying the selected row count.
     */
    public function tableFooterCount(bool|Closure $condition = true): static
    {
        $this->hasTableFooterCount = $condition;

        return $this;
    }

    /**
     * Render the selected items table inside a collapsible region with a
     * show/hide toggle.
     */
    public function tableCollapsible(bool|Closure $condition = true): static
    {
        $this->isTableCollapsible = $condition;

        return $this;
    }

    /**
     * Start the collapsible table in its collapsed (hidden) state. Has no
     * effect unless tableCollapsible() is enabled.
     */
    public function tableCollapsed(bool|Closure $condition = true): static
    {
        $this->isTableCollapsed = $condition;

        return $this;
    }

    public function getTableEmptyMessage(): string
    {
        return $this->evaluate($this->tableEmptyMessage)
            ?? __('fin-modal-table-select::modal-table-select.empty_message');
    }

    public function getHasTableFooterCount(): bool
    {
        return (bool) $this->evaluate($this->hasTableFooterCount);
    }

    public function getIsTableCollapsible(): bool
    {
        return (bool) $this->evaluate($this->isTableCollapsible);
    }

    public function getIsTableCollapsed(): bool
    {
        return (bool) $this->evaluate($this->isTableCollapsed);
    }

    /**
     * Table display is active when explicitly enabled via displayAsTable()
     * or when custom columns/schema are configured.
     */
    public function hasTableDisplay(): bool
    {
        return ($this->tableColumns !== null)
            || ($this->tableSchema !== null)
            || ((bool) $this->evaluate($this->isDisplayAsTable));
    }

    /** @return array<TableColumn> */
    public function getTableColumns(): array
    {
        $columns = $this->evaluate($this->tableColumns);

        if ($columns !== null) {
            return $columns;
        }

        return $this->getInheritedTableDisplay()['columns'];
    }

    /** @return array<SchemaComponent> */
    public function getTableSchema(): array
    {
        $schema = $this->evaluate($this->tableSchema);

        if ($schema !== null) {
            return $schema;
        }

        return $this->getInheritedTableDisplay()['entries'];
    }

    /**
     * Derive selected-items table columns and row entries from the modal's
     * tableConfiguration() class, so displayAsTable() works with zero extra
     * configuration.
     *
     * @return array{columns: array<TableColumn>, entries: array<SchemaComponent>}
     */
    protected function getInheritedTableDisplay(): array
    {
        try {
            $configuration = $this->getTableConfiguration();

            $table = Table::make(app(TableSelectLivewireComponent::class));
            $configuration::configure($table);
        } catch (Throwable) {
            return ['columns' => [], 'entries' => []];
        }

        $columns = [];
        $entries = [];

        foreach ($table->getColumns() as $column) {
            if ($column->isHidden()) {
                continue;
            }

            $columns[] = TableColumn::make((string) $column->getLabel());
            $entries[] = $this->makeEntryForTableColumn($column);
        }

        return ['columns' => $columns, 'entries' => $entries];
    }

    protected function makeEntryForTableColumn(Column $column): SchemaComponent
    {
        $name = $column->getName();

        if ($column instanceof ImageColumn) {
            return ImageEntry::make($name)->hiddenLabel();
        }

        if ($column instanceof IconColumn) {
            return IconEntry::make($name)->hiddenLabel();
        }

        $entry = TextEntry::make($name)->hiddenLabel();

        if (($column instanceof TextColumn) && $column->isBadge()) {
            $entry->badge();
        }

        return $entry;
    }

    /**
     * Retrieve the full Eloquent models for the currently selected IDs, in
     * selection order. Uses the same relationship query strategy as the parent
     * component and memoizes per state, so repeated calls within a render are
     * free.
     *
     * @return EloquentCollection<int, Model>
     */
    public function getSelectedRecords(): EloquentCollection
    {
        $state = $this->getState();
        $ids = array_values(array_filter(
            is_array($state) ? $state : [$state],
            fn ($id): bool => filled($id),
        ));

        if (blank($ids)) {
            return new EloquentCollection;
        }

        $ids = array_map(strval(...), $ids);
        $cacheKey = implode(',', $ids);

        if (($this->cachedSelectedRecordsKey === $cacheKey) && ($this->cachedSelectedRecords !== null)) {
            return $this->cachedSelectedRecords;
        }

        $relationship = Relation::noConstraints(fn (): Relation => $this->getRelationship());

        $query = app(RelationshipJoiner::class)->prepareQueryForNoConstraints($relationship);

        $query->whereIn($this->getQualifiedRelatedKeyNameForRelationship($relationship), $ids);

        if ($this->modifyRelationshipQueryUsing) {
            $query = $this->evaluate($this->modifyRelationshipQueryUsing, [
                'query' => $query,
            ], [
                Builder::class => $query,
            ]) ?? $query;
        }

        $eagerLoad = $this->evaluate($this->tableEagerLoad);

        if (filled($eagerLoad)) {
            $query->with($eagerLoad);
        }

        if ($this->tableModifyQueryUsing !== null) {
            $query = $this->evaluate($this->tableModifyQueryUsing, [
                'query' => $query,
            ], [
                Builder::class => $query,
            ]) ?? $query;
        }

        $records = $query->get()
            ->sortBy(fn (Model $record): int => (int) array_search((string) $record->getKey(), $ids, strict: true))
            ->values();

        $this->cachedSelectedRecordsKey = $cacheKey;

        return $this->cachedSelectedRecords = $records;
    }

    /**
     * Re-resolve the selected records, bypassing the memoized result. Used
     * after the state has just changed (e.g. inside afterStateUpdated).
     *
     * @return EloquentCollection<int, Model>
     */
    public function getFreshSelectedRecords(): EloquentCollection
    {
        $this->cachedSelectedRecords = null;
        $this->cachedSelectedRecordsKey = null;

        return $this->getSelectedRecords();
    }

    /**
     * Build the schema that renders the selected records as a table. Each row
     * is a RepeatableEntry item bound to the record model itself, so entries
     * resolve dot-notation relationships, casts, and enums natively.
     */
    public function makeSelectedTableSchema(): ?Schema
    {
        $records = $this->getSelectedRecords();

        if ($records->isEmpty()) {
            return null;
        }

        $entries = $this->getTableSchema();

        if (blank($entries)) {
            return null;
        }

        $entryName = 'finSelectedTable';

        $repeatable = RepeatableEntry::make($entryName)
            ->table($this->getTableColumns())
            ->schema($entries)
            ->contained(false)
            ->hiddenLabel();

        return Schema::make($this->getLivewire())
            ->components([$repeatable])
            ->constantState([$entryName => $records->all()]);
    }
}
