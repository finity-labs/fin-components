<?php

declare(strict_types=1);

namespace FinityLabs\FinComponents\Components\ModalTableSelect\Concerns;

use Closure;
use Filament\Infolists\Components\Component as InfolistComponent;
use Illuminate\Database\Eloquent\Model;

trait HasInfolistDisplay
{
    /** @var array<InfolistComponent>|Closure|null */
    protected array | Closure | null $infolistSchema = null;

    /** @var int|Closure */
    protected int | Closure $infolistColumns = 1;

    /** @var array<string>|Closure|null */
    protected array | Closure | null $infolistEagerLoad = null;

    /**
     * Define the infolist schema for displaying a single selected record.
     *
     * @param  array<InfolistComponent>|Closure  $schema
     */
    public function infolistSchema(array | Closure $schema): static
    {
        $this->infolistSchema = $schema;

        return $this;
    }

    public function infolistColumns(int | Closure $columns): static
    {
        $this->infolistColumns = $columns;

        return $this;
    }

    /**
     * @param  array<string>|Closure  $relationships
     */
    public function infolistEagerLoad(array | Closure $relationships): static
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
     * Get the full Eloquent model for the selected single record.
     * Used by both infolist and form display modes.
     */
    public function getSelectedRecord(): ?Model
    {
        $state = $this->getState();

        if (empty($state)) {
            return null;
        }

        $id = is_array($state) ? ($state[0] ?? null) : $state;

        if ($id === null) {
            return null;
        }

        $relationship = $this->getRelationship();
        $relatedModel = $relationship->getRelated();

        $query = $relatedModel->newQuery()->where($relatedModel->getKeyName(), $id);

        $eagerLoad = $this->evaluate($this->infolistEagerLoad)
            ?? $this->evaluate($this->formEagerLoad ?? null)
            ?? [];

        if (! empty($eagerLoad)) {
            $query->with($eagerLoad);
        }

        return $query->first();
    }
}
