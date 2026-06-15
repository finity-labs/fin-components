@php
    use Filament\Infolists\Components\RepeatableEntry;
    use Filament\Schemas\Schema;

    $records = $field->getRecords();
    $entries = $field->getTableSchema();
    $headers = $field->getTableColumns();
    $livewire = $field->getLivewire();
@endphp

@if ($records->isNotEmpty() && filled($entries))
    @php
        $entryName = 'finSelectedTable_' . md5($field->getStatePath());

        // Recursively pull Entry instances (name + getState) out of the schema
        // tree so we can build per-row state. Using data_get against the record
        // keeps relationships (dot notation) and enum instances intact so
        // badge()/date()/durationHours() resolve correctly.
        $extractEntries = function (array $components) use (&$extractEntries): array {
            $found = [];

            foreach ($components as $component) {
                if (is_array($component)) {
                    $found = array_merge($found, $extractEntries($component));

                    continue;
                }

                if (! is_object($component)) {
                    continue;
                }

                if (method_exists($component, 'getName') && method_exists($component, 'getState')) {
                    $found[] = $component;
                }

                try {
                    $ref = new \ReflectionProperty($component, 'childComponents');
                    $children = $ref->getValue($component);

                    if (is_array($children) && count($children)) {
                        $found = array_merge($found, $extractEntries($children));
                    }
                } catch (\ReflectionException) {
                }
            }

            return $found;
        };

        $entryComponents = $extractEntries($entries);

        $items = $records->map(function ($record) use ($entryComponents) {
            $item = [];

            foreach ($entryComponents as $entry) {
                $name = $entry->getName();
                data_set($item, $name, data_get($record, $name));
            }

            return $item;
        })->values()->all();

        $repeatable = RepeatableEntry::make($entryName)
            ->table($headers)
            ->schema($entries)
            ->contained(false)
            ->hiddenLabel();

        $schema = Schema::make($livewire)
            ->schema([$repeatable])
            ->constantState([$entryName => $items]);
    @endphp

    <div class="fi-fo-modal-table-select-table w-full min-w-0">
        {{ $schema }}
    </div>
@else
    <div>
        <p class="text-sm text-gray-500 italic dark:text-gray-400">
            {{ $field->getTableEmptyMessage() }}
        </p>
    </div>
@endif
