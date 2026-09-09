<?php

declare(strict_types=1);

namespace FinityLabs\FinModalTableSelect\Concerns;

use Closure;
use FinityLabs\FinModalTableSelect\Components\ModalTableSelect;
use Illuminate\Support\Arr;

trait HasStandaloneRecords
{
    /** @var array<int, array<string, mixed>>|Closure|null */
    protected array|Closure|null $standaloneRecords = null;

    protected string|Closure $standaloneRecordsKeyAttribute = 'key';

    protected string|Closure|null $standaloneRecordsTitleAttribute = null;

    /** @var array<string, array<string, mixed>>|null */
    protected ?array $cachedStandaloneRecordsIndex = null;

    /**
     * Use the component over plain arrays instead of Eloquent: $records is a
     * list of arrays (or a Closure returning one), each carrying its key under
     * $keyAttribute and its display value under $titleAttribute. The selected
     * keys are stored in the field state as strings, single or multiple().
     *
     * The closure is evaluated with Filament's closure dependency injection,
     * so it can read sibling form state: fn (Get $get) => ... — e.g. to scope
     * an application's route list by a panel select in the same Repeater row.
     *
     * The modal table runs over these records through Filament's records()
     * data source; the tableConfiguration() class only adds columns and
     * filters, never a query. Search covers the globally searchable columns
     * and sorting the sortable ones.
     *
     * @param  array<int, array<string, mixed>>|Closure  $records
     */
    public function standaloneRecords(
        array|Closure $records,
        string|Closure $keyAttribute = 'key',
        string|Closure|null $titleAttribute = null,
    ): static {
        $this->standaloneRecords = $records;
        $this->standaloneRecordsKeyAttribute = $keyAttribute;
        $this->standaloneRecordsTitleAttribute = $titleAttribute;

        $this->getOptionLabelUsing(static function (ModalTableSelect $component): ?string {
            $state = $component->getState();

            if (blank($state)) {
                return null;
            }

            $key = is_array($state) ? Arr::first($state) : $state;
            $record = $component->findStandaloneRecord($key);

            return $record !== null
                ? $component->getRecordDisplayLabel($record)
                : (string) $key;
        });

        $this->getOptionLabelsUsing(static function (ModalTableSelect $component, array $values): array {
            $labels = [];

            foreach ($values as $value) {
                $record = $component->findStandaloneRecord($value);

                // A key the records closure no longer returns still shows as
                // its raw key — and stays in the In rule Filament builds from
                // these labels, so a saved selection never fails validation.
                $labels[$value] = $record !== null
                    ? $component->getRecordDisplayLabel($record)
                    : (string) $value;
            }

            return $labels;
        });

        return $this;
    }

    public function hasStandaloneRecords(): bool
    {
        return $this->standaloneRecords !== null;
    }

    public function getStandaloneRecordsKeyAttribute(): string
    {
        return $this->evaluate($this->standaloneRecordsKeyAttribute);
    }

    public function getStandaloneRecordsTitleAttribute(): ?string
    {
        return $this->evaluate($this->standaloneRecordsTitleAttribute);
    }

    /**
     * The records list keyed by record key, memoized per request. A record
     * without a value under the key attribute falls back to its list position.
     *
     * @return array<string, array<string, mixed>>
     */
    public function getStandaloneRecordsIndex(): array
    {
        if ($this->cachedStandaloneRecordsIndex !== null) {
            return $this->cachedStandaloneRecordsIndex;
        }

        $keyAttribute = $this->getStandaloneRecordsKeyAttribute();
        $index = [];

        foreach ($this->evaluate($this->standaloneRecords) ?? [] as $position => $record) {
            if (! is_array($record)) {
                continue;
            }

            $index[(string) (data_get($record, $keyAttribute) ?? $position)] = $record;
        }

        return $this->cachedStandaloneRecordsIndex = $index;
    }

    /** @return array<string, mixed>|null */
    public function findStandaloneRecord(mixed $key): ?array
    {
        if (blank($key)) {
            return null;
        }

        return $this->getStandaloneRecordsIndex()[(string) $key] ?? null;
    }

    /**
     * A displayable stand-in for a key the records closure no longer returns:
     * the raw key under the key attribute, so displays render the key instead
     * of dropping the selection or throwing.
     *
     * @return array<string, mixed>
     */
    public function makeStaleStandaloneRecord(string $key): array
    {
        return [$this->getStandaloneRecordsKeyAttribute() => $key];
    }

    /**
     * Drop the memoized records index, so the next read re-evaluates the
     * closure — needed inside afterStateUpdated, where sibling state the
     * closure depends on may have just changed.
     */
    public function clearStandaloneRecordsCache(): void
    {
        $this->cachedStandaloneRecordsIndex = null;
    }
}
