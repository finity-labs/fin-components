<?php

declare(strict_types=1);

namespace FinityLabs\FinModalTableSelect\Concerns;

use Closure;
use Filament\Infolists\Components\RepeatableEntry\TableColumn;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection as EloquentCollection;
use Illuminate\Database\Eloquent\Model;

trait HasTableDisplay
{
    /** @var array<TableColumn>|Closure|null */
    protected array|Closure|null $tableColumns = null;

    /** @var array<object>|Closure|null */
    protected array|Closure|null $tableSchema = null;

    protected string|Closure|null $tableEmptyMessage = null;

    /** @var array<string>|Closure|null */
    protected array|Closure|null $tableEagerLoad = null;

    protected ?Closure $tableModifyQueryUsing = null;

    protected bool|Closure $hasTableFooterCount = false;

    protected bool|Closure $isTableCollapsible = false;

    protected bool|Closure $isTableCollapsed = false;

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
     * formatting (badge(), date(), money(), durationHours(), etc.) for us.
     *
     * @param  array<object>|Closure  $schema
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
     * show/hide toggle. The row count stays visible in the toggle header even
     * while the table is collapsed.
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

    /** @return array<TableColumn> */
    public function getTableColumns(): array
    {
        return $this->evaluate($this->tableColumns) ?? [];
    }

    /** @return array<object> */
    public function getTableSchema(): array
    {
        return $this->evaluate($this->tableSchema) ?? [];
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

    public function hasTableColumns(): bool
    {
        return $this->tableColumns !== null;
    }

    /**
     * Retrieve the full Eloquent models for the currently selected IDs.
     *
     * @return EloquentCollection<int, Model>
     */
    public function getRecords(): EloquentCollection
    {
        $state = $this->getState();

        if (empty($state)) {
            return new EloquentCollection;
        }

        $ids = is_array($state) ? $state : [$state];

        $relationship = $this->getRelationship();
        $relatedModel = $relationship->getRelated();
        $keyName = $relatedModel->getKeyName();

        $query = $relatedModel->newQuery()->whereIn($keyName, $ids);

        $eagerLoad = $this->evaluate($this->tableEagerLoad);

        if (! empty($eagerLoad)) {
            $query->with($eagerLoad);
        }

        if ($this->tableModifyQueryUsing !== null) {
            $query = $this->evaluate($this->tableModifyQueryUsing, [
                'query' => $query,
            ], [
                Builder::class => $query,
            ]) ?? $query;
        }

        return $query->get();
    }
}
