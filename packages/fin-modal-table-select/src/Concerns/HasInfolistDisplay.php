<?php

declare(strict_types=1);

namespace FinityLabs\FinModalTableSelect\Concerns;

use Closure;
use Filament\Schemas\Components\Component as InfolistComponent;
use Filament\Schemas\Schema;
use Illuminate\Database\Eloquent\Model;

trait HasInfolistDisplay
{
    /** @var array<InfolistComponent>|Closure|null */
    protected array|Closure|null $infolistSchema = null;

    protected int|Closure $infolistColumns = 1;

    /** @var array<string>|Closure|null */
    protected array|Closure|null $infolistEagerLoad = null;

    /**
     * Define the infolist schema for displaying a single selected record.
     *
     * @param  array<InfolistComponent>|Closure  $schema
     */
    public function infolistSchema(array|Closure $schema): static
    {
        $this->infolistSchema = $schema;

        return $this;
    }

    public function infolistColumns(int|Closure $columns): static
    {
        $this->infolistColumns = $columns;

        return $this;
    }

    /**
     * @param  array<string>|Closure  $relationships
     */
    public function infolistEagerLoad(array|Closure $relationships): static
    {
        $this->infolistEagerLoad = $relationships;

        return $this;
    }

    /** @return array<InfolistComponent>|null */
    public function getInfolistSchema(): ?array
    {
        return $this->evaluate($this->infolistSchema);
    }

    public function getInfolistColumns(): int
    {
        return (int) $this->evaluate($this->infolistColumns);
    }

    public function hasInfolistSchema(): bool
    {
        return $this->infolistSchema !== null;
    }

    /**
     * The selected record for display: models through the parent component's
     * pipeline (so getSelectedRecordUsing() and the record cache are
     * respected) with any configured relationships loaded; array records by
     * key from the standaloneRecords() list, a stale key becoming its raw-key
     * stand-in.
     *
     * @return Model|array<string, mixed>|null
     */
    public function getSelectedDisplayRecord(): Model|array|null
    {
        if ($this->hasStandaloneRecords()) {
            $state = $this->getState();
            $key = is_array($state) ? ($state[0] ?? null) : $state;

            if (blank($key)) {
                return null;
            }

            return $this->findStandaloneRecord($key) ?? $this->makeStaleStandaloneRecord((string) $key);
        }

        $record = $this->getSelectedRecord();

        if (! $record) {
            return null;
        }

        $eagerLoad = $this->evaluate($this->infolistEagerLoad) ?? [];

        if (filled($eagerLoad)) {
            $record->loadMissing($eagerLoad);
        }

        return $record;
    }

    /**
     * Build the schema that renders the selected record as an infolist. A
     * model binds as the schema's record, so entries resolve dot-notation
     * relationships, casts, and enums natively; an array record binds as
     * constant state, which data_get resolves the same way.
     */
    public function makeSelectedInfolistSchema(): ?Schema
    {
        $record = $this->getSelectedDisplayRecord();
        $components = $this->getInfolistSchema();

        if (blank($record) || blank($components)) {
            return null;
        }

        $schema = Schema::make($this->getLivewire())
            ->components($components)
            ->columns($this->getInfolistColumns());

        return $record instanceof Model
            ? $schema->record($record)
            : $schema->constantState($record);
    }
}
