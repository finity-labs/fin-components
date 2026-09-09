<?php

declare(strict_types=1);

namespace FinityLabs\FinModalTableSelect\Livewire;

use Filament\Forms\Components\TableSelect\Livewire\TableSelectLivewireComponent;
use Filament\Tables\Columns\Column;
use Filament\Tables\Table;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Str;

/**
 * The modal table for standaloneRecords() mode: the stock TableSelect
 * component, with the array payload the field passed through the table
 * arguments applied as a records() data source. Without the payload it
 * behaves exactly like the stock component.
 */
class StandaloneRecordsTableSelectComponent extends TableSelectLivewireComponent
{
    public const RECORDS_ARGUMENT = 'finStandaloneRecords';

    public const KEY_ATTRIBUTE_ARGUMENT = 'finStandaloneRecordsKey';

    public function table(Table $table): Table
    {
        $table = parent::table($table);

        $arguments = $this->getTableArguments();
        $records = $arguments[static::RECORDS_ARGUMENT] ?? null;

        if (! is_array($records)) {
            return $table;
        }

        $keyAttribute = (string) ($arguments[static::KEY_ATTRIBUTE_ARGUMENT] ?? 'key');

        return $table->records(
            fn (?string $search, array $sort, int|string $page, int|string $recordsPerPage): LengthAwarePaginator => $this->paginateStandaloneRecords($table, $records, $keyAttribute, $search, $sort, (int) $page, $recordsPerPage),
        );
    }

    /**
     * One page of array rows keyed by record key, searched over the globally
     * searchable columns and sorted by the clicked sortable column.
     *
     * @param  array<int, mixed>  $records  Livewire-hydrated, so the shape is re-checked row by row.
     * @param  array{0: string|null, 1: string|null}  $sort
     */
    protected function paginateStandaloneRecords(
        Table $table,
        array $records,
        string $keyAttribute,
        ?string $search,
        array $sort,
        int $page,
        int|string $recordsPerPage,
    ): LengthAwarePaginator {
        $rows = [];

        foreach ($records as $position => $record) {
            if (! is_array($record)) {
                continue;
            }

            $rows[(string) (data_get($record, $keyAttribute) ?? $position)] = $record;
        }

        if (filled($search)) {
            $searchable = [];

            foreach ($table->getColumns() as $column) {
                if ($column->isGloballySearchable()) {
                    $searchable[] = $column->getName();
                }
            }

            $rows = array_filter($rows, function (array $record) use ($search, $searchable): bool {
                foreach ($searchable as $name) {
                    if (Str::contains((string) data_get($record, $name), $search, ignoreCase: true)) {
                        return true;
                    }
                }

                return false;
            });
        }

        [$sortColumn, $sortDirection] = $sort + [null, null];

        if (($sortColumn !== null) && $this->isStandaloneSortAllowed($table, $sortColumn)) {
            $direction = $sortDirection === 'desc' ? -1 : 1;

            uasort($rows, fn (array $a, array $b): int => $direction * strnatcasecmp(
                (string) data_get($a, $sortColumn),
                (string) data_get($b, $sortColumn),
            ));
        }

        $perPage = $recordsPerPage === 'all' ? max(1, count($rows)) : (int) $recordsPerPage;

        return new LengthAwarePaginator(
            array_slice($rows, ($page - 1) * $perPage, $perPage, preserve_keys: true),
            count($rows),
            $perPage,
            $page,
        );
    }

    protected function isStandaloneSortAllowed(Table $table, string $columnName): bool
    {
        $column = $table->getColumn($columnName);

        return ($column instanceof Column) && $column->isSortable();
    }
}
