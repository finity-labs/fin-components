<?php

declare(strict_types=1);

namespace FinityLabs\FinModalTableSelect\Concerns;

use Closure;
use Filament\Schemas\Components\Utilities\Get;
use Filament\Schemas\Components\Utilities\Set;
use FinityLabs\FinModalTableSelect\Components\ModalTableSelect;
use Illuminate\Database\Eloquent\Collection as EloquentCollection;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;

trait CanFillRepeater
{
    protected string|Closure|null $fillsRepeaterName = null;

    protected ?Closure $fillsRepeaterItemUsing = null;

    protected string|Closure $fillsRepeaterKeyAttribute = 'id';

    protected bool $isFillsRepeaterHookRegistered = false;

    /**
     * Sync the selection into a sibling Repeater whenever it changes.
     *
     * Merge semantics (the point of this feature):
     *  - newly selected records are appended as fresh rows built by $itemUsing
     *  - rows whose record is still selected are left untouched, preserving
     *    any values the user has edited (quantities, prices, ...)
     *  - rows whose record was deselected are removed
     *
     * Each row is identified by $keyAttribute (e.g. 'product_id'), compared
     * against the record's primary key. If $itemUsing does not include it in
     * the row it returns, it is added automatically.
     *
     * @param  Closure  $itemUsing  Receives the record, returns the row data array.
     */
    public function fillsRepeater(
        string|Closure $repeaterName,
        Closure $itemUsing,
        string|Closure $keyAttribute = 'id',
    ): static {
        $this->fillsRepeaterName = $repeaterName;
        $this->fillsRepeaterItemUsing = $itemUsing;
        $this->fillsRepeaterKeyAttribute = $keyAttribute;

        if (! $this->isFillsRepeaterHookRegistered) {
            $this->isFillsRepeaterHookRegistered = true;

            $this->afterStateUpdated(static function (ModalTableSelect $component, Set $set, Get $get): void {
                $component->runRepeaterFill($set, $get);
            });
        }

        return $this;
    }

    public function getFillsRepeaterKeyAttribute(): string
    {
        return $this->evaluate($this->fillsRepeaterKeyAttribute);
    }

    public function runRepeaterFill(Set $set, Get $get): void
    {
        $repeaterName = $this->evaluate($this->fillsRepeaterName);

        if (blank($repeaterName)) {
            return;
        }

        $existing = $get($repeaterName);
        $existing = is_array($existing) ? $existing : [];

        $set($repeaterName, $this->mergeRepeaterItems($existing, $this->getFreshSelectedRecords()));
    }

    /**
     * Merge the current repeater rows with the selected records. Pure apart
     * from evaluating the item closure, so it is unit-testable in isolation.
     *
     * @param  array<array-key, array<string, mixed>>  $existing
     * @param  EloquentCollection<int, Model>  $records
     *
     * @return array<array-key, array<string, mixed>>
     */
    public function mergeRepeaterItems(array $existing, EloquentCollection $records): array
    {
        $keyAttribute = $this->evaluate($this->fillsRepeaterKeyAttribute);

        $selectedKeys = $records
            ->map(fn (Model $record): string => (string) $record->getKey())
            ->all();

        $merged = [];
        $presentKeys = [];

        foreach ($existing as $itemKey => $row) {
            $rowRecordKey = (string) data_get($row, $keyAttribute);

            if (in_array($rowRecordKey, $selectedKeys, strict: true)) {
                $merged[$itemKey] = $row;
                $presentKeys[] = $rowRecordKey;
            }
        }

        foreach ($records as $record) {
            $recordKey = (string) $record->getKey();

            if (in_array($recordKey, $presentKeys, strict: true)) {
                continue;
            }

            $row = $this->evaluate($this->fillsRepeaterItemUsing, [
                'record' => $record,
            ], [
                Model::class => $record,
            ]) ?? [];

            $row[$keyAttribute] ??= $record->getKey();

            $merged[(string) Str::uuid()] = $row;
        }

        return $merged;
    }
}
