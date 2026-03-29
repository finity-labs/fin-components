<?php

declare(strict_types=1);

namespace FinityLabs\FinComponents\Components\ModalTableSelect\Concerns;

use Closure;
use Filament\Tables\Columns\Column;
use Filament\Tables\Columns\TextColumn;
use Illuminate\Database\Eloquent\Collection as EloquentCollection;
use Illuminate\Database\Eloquent\Model;

trait HasTableDisplay
{
    /** @var array<Column>|Closure|null */
    protected array|Closure|null $tableColumns = null;

    protected bool|Closure $isTableStriped = false;

    protected string|Closure|null $tableEmptyMessage = null;

    /** @var array<string>|Closure|null */
    protected array|Closure|null $tableEagerLoad = null;

    /**
     * Define the columns displayed in the selected items table.
     *
     * @param  array<Column>|Closure  $columns
     */
    public function tableColumns(array|Closure $columns): static
    {
        $this->tableColumns = $columns;

        return $this;
    }

    public function tableStriped(bool|Closure $condition = true): static
    {
        $this->isTableStriped = $condition;

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

    /** @return array<Column> */
    public function getTableColumns(): array
    {
        $columns = $this->evaluate($this->tableColumns);

        if ($columns === null) {
            $titleAttribute = $this->getRelationshipTitleAttribute();

            return [
                TextColumn::make($titleAttribute ?? 'id')
                    ->label(str($titleAttribute ?? 'ID')->headline()->toString()),
            ];
        }

        return $columns;
    }

    public function getIsTableStriped(): bool
    {
        return (bool) $this->evaluate($this->isTableStriped);
    }

    public function getTableEmptyMessage(): string
    {
        return $this->evaluate($this->tableEmptyMessage)
            ?? __('fin-components::modal-table-select.empty_message');
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

        return $query->get();
    }

    /**
     * Resolve a column value from a record, supporting dot notation.
     */
    public function resolveColumnValue(Model $record, string $name): mixed
    {
        return data_get($record, $name);
    }

    /**
     * Render a column cell for a given record using Filament's column pipeline.
     * This runs the full rendering: badges, colors, icons, formatStateUsing, etc.
     */
    public function renderColumn(Column $column, Model $record): string
    {
        $clone = clone $column;
        $clone->record($record);

        if (method_exists($clone, 'toEmbeddedHtml')) {
            return $clone->toEmbeddedHtml();
        }

        $value = data_get($record, $clone->getName());

        return e($value ?? '—');
    }
}
