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
     * The selected record, resolved through the parent component's pipeline
     * (so getSelectedRecordUsing() and the record cache are respected), with
     * any configured relationships loaded for display.
     */
    public function getSelectedDisplayRecord(): ?Model
    {
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
     * Build the schema that renders the selected record as an infolist. The
     * schema is bound to the record itself, so entries resolve dot-notation
     * relationships, casts, and enums natively.
     */
    public function makeSelectedInfolistSchema(): ?Schema
    {
        $record = $this->getSelectedDisplayRecord();
        $components = $this->getInfolistSchema();

        if ((! $record) || blank($components)) {
            return null;
        }

        return Schema::make($this->getLivewire())
            ->components($components)
            ->record($record)
            ->columns($this->getInfolistColumns());
    }
}
