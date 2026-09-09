<?php

declare(strict_types=1);

namespace FinityLabs\FinModalTableSelect\Concerns;

use Closure;
use Filament\Schemas\Components\Utilities\Set;
use FinityLabs\FinModalTableSelect\Components\ModalTableSelect;
use Illuminate\Database\Eloquent\Model;

trait CanFillFields
{
    /** @var array<string, string|Closure>|Closure|null */
    protected array|Closure|null $fillsFieldsMap = null;

    protected bool $isFillsFieldsHookRegistered = false;

    /**
     * Fill sibling form fields from the selected record whenever the
     * selection changes. Intended for single selection.
     *
     * The map is [target field name => source], where source is either a
     * dot-notation attribute path on the record ('contact.phone') or a
     * Closure receiving the record. On deselection, targets are set to null.
     *
     * Runs via afterStateUpdated(), which the modal's submit action triggers
     * server-side — consumer-registered afterStateUpdated() hooks are
     * unaffected because Filament stacks them.
     *
     * @param  array<string, string|Closure>|Closure  $map
     */
    public function fillsFields(array|Closure $map): static
    {
        $this->fillsFieldsMap = $map;

        if (! $this->isFillsFieldsHookRegistered) {
            $this->isFillsFieldsHookRegistered = true;

            $this->afterStateUpdated(static function (ModalTableSelect $component, Set $set): void {
                $component->runFieldFills($set);
            });
        }

        return $this;
    }

    public function runFieldFills(Set $set): void
    {
        if ($this->isMultiple()) {
            return;
        }

        foreach ($this->resolveFieldFills($this->getFreshSelectedRecord()) as $target => $value) {
            $set($target, $value);
        }
    }

    /**
     * Resolve the target => value map for the given record. Null record
     * (deselection) resolves every target to null.
     *
     * @param  Model|array<string, mixed>|null  $record
     *
     * @return array<string, mixed>
     */
    public function resolveFieldFills(Model|array|null $record): array
    {
        $map = $this->evaluate($this->fillsFieldsMap);

        if (blank($map)) {
            return [];
        }

        $values = [];

        foreach ($map as $target => $source) {
            $values[$target] = $this->resolveFillValue($record, $source);
        }

        return $values;
    }

    /** @param  Model|array<string, mixed>|null  $record */
    protected function resolveFillValue(Model|array|null $record, string|Closure $source): mixed
    {
        if ($record === null) {
            return null;
        }

        if ($source instanceof Closure) {
            return $this->evaluateWithRecord($source, $record);
        }

        return data_get($record, $source);
    }

    /**
     * Re-resolve the selected record, bypassing the memoized copies. Needed
     * inside afterStateUpdated, where the caches may predate the state change
     * that just happened. standaloneRecords() mode resolves an array record
     * by key — a stale key resolves to its raw-key stand-in, never to null.
     *
     * @return Model|array<string, mixed>|null
     */
    public function getFreshSelectedRecord(): Model|array|null
    {
        if ($this->hasStandaloneRecords()) {
            $this->clearStandaloneRecordsCache();

            $state = $this->getState();
            $key = is_array($state) ? ($state[0] ?? null) : $state;

            if (blank($key)) {
                return null;
            }

            return $this->findStandaloneRecord($key) ?? $this->makeStaleStandaloneRecord((string) $key);
        }

        $this->cachedSelectedRecord = null;

        return $this->getSelectedRecord();
    }
}
