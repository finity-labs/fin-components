<?php

declare(strict_types=1);

namespace FinityLabs\FinModalTableSelect\Concerns;

use Closure;
use FinityLabs\FinModalTableSelect\Components\ModalTableSelect;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Arr;

trait HasStandaloneMode
{
    /** @var class-string<Model>|Closure|null */
    protected string|Closure|null $standaloneModel = null;

    protected string|Closure|null $standaloneTitleAttribute = null;

    protected ?Closure $standaloneModifyQueryUsing = null;

    /**
     * Use the component without an Eloquent relationship: records are queried
     * from the given model and the selected primary keys are stored in the
     * field state (e.g. a JSON column).
     *
     * The modal table needs a query of its own, so the tableConfiguration()
     * class must call $table->query(...) (or ->model(...)).
     *
     * @param  class-string<Model>|Closure  $model
     */
    public function standalone(string|Closure $model, string|Closure|null $titleAttribute = null): static
    {
        $this->standaloneModel = $model;
        $this->standaloneTitleAttribute = $titleAttribute;

        $this->getSelectedRecordUsing(static function (ModalTableSelect $component, mixed $state): ?Model {
            if (blank($state)) {
                return null;
            }

            $key = is_array($state) ? Arr::first($state) : $state;

            return $component->getStandaloneQuery()->whereKey($key)->first();
        });

        $this->getOptionLabelUsing(static function (ModalTableSelect $component): ?string {
            $record = $component->getSelectedRecord();

            return $record ? $component->getRecordDisplayLabel($record) : null;
        });

        $this->getOptionLabelsUsing(static function (ModalTableSelect $component, array $values): array {
            if (blank($values)) {
                return [];
            }

            return $component->getStandaloneQuery()
                ->findMany($values)
                ->mapWithKeys(fn (Model $record): array => [
                    $record->getKey() => $component->getRecordDisplayLabel($record),
                ])
                ->all();
        });

        return $this;
    }

    /**
     * Modify the query used to resolve standalone records (labels, selected
     * records, and display loading).
     */
    public function standaloneModifyQueryUsing(?Closure $callback): static
    {
        $this->standaloneModifyQueryUsing = $callback;

        return $this;
    }

    public function getIsStandalone(): bool
    {
        return filled($this->evaluate($this->standaloneModel));
    }

    /** @return class-string<Model> */
    public function getStandaloneModel(): string
    {
        return $this->evaluate($this->standaloneModel);
    }

    public function getStandaloneTitleAttribute(): ?string
    {
        return $this->evaluate($this->standaloneTitleAttribute);
    }

    /** @return Builder<Model> */
    public function getStandaloneQuery(): Builder
    {
        $model = $this->getStandaloneModel();

        $query = $model::query();

        if ($this->standaloneModifyQueryUsing !== null) {
            $query = $this->evaluate($this->standaloneModifyQueryUsing, [
                'query' => $query,
            ], [
                Builder::class => $query,
            ]) ?? $query;
        }

        return $query;
    }
}
