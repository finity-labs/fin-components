@php
    $record = $field->getSelectedRecord();
    $infolistSchema = $field->getInfolistSchema();
    $columns = $field->getInfolistColumns();
@endphp

@if ($record && $infolistSchema)
    <div class="fi-in rounded-xl bg-white p-6 shadow-sm ring-1 ring-gray-950/5 dark:bg-gray-900 dark:ring-white/10">
        @php
            $livewire = $field->getLivewire();
            $stateKey = 'finSelectedInfolist_' . md5($field->getStatePath());

            // Recursively extract all Field/Entry instances from the schema tree
            // (handles entries nested inside Grid, Section, etc.)
            $extractEntries = function (array $components) use (&$extractEntries): array {
                $entries = [];
                foreach ($components as $component) {
                    if (! is_object($component)) {
                        if (is_array($component)) {
                            $entries = array_merge($entries, $extractEntries($component));
                        }
                        continue;
                    }
                    if (method_exists($component, 'getName') && method_exists($component, 'getState')) {
                        $entries[] = $component;
                    }
                    try {
                        $ref = new \ReflectionProperty($component, 'childComponents');
                        $children = $ref->getValue($component);
                        if (is_array($children) && count($children)) {
                            $entries = array_merge($entries, $extractEntries($children));
                        }
                    } catch (\ReflectionException) {
                    }
                }
                return $entries;
            };

            // Build state from record, resolving each entry by name (supports dot notation)
            $infolistState = $record->toArray();
            foreach ($extractEntries($infolistSchema) as $entry) {
                $name = $entry->getName();
                data_set($infolistState, $name, data_get($record, $name));
            }

            $livewire->data[$stateKey] = $infolistState;

            $infolist = \Filament\Schemas\Schema::make($livewire)
                ->schema($infolistSchema)
                ->statePath("data.{$stateKey}")
                ->model($record);
        @endphp

        {{ $infolist }}
    </div>
@else
    <div class="fi-in rounded-xl bg-white px-6 py-12 shadow-sm ring-1 ring-gray-950/5 dark:bg-gray-900 dark:ring-white/10">
        <p class="text-center text-sm text-gray-500 dark:text-gray-400">
            {{ $field->getTableEmptyMessage() }}
        </p>
    </div>
@endif
