@php
    $record = $field->getSelectedRecord();
    $formSchema = $field->getFormSchema();
    $columns = $field->getFormColumns();
@endphp

@if ($record && $formSchema)
    <div class="fi-fo rounded-xl bg-white p-6 shadow-sm ring-1 ring-gray-950/5 dark:bg-gray-900 dark:ring-white/10">
        @php
            $livewire = $field->getLivewire();
            $stateKey = 'finSelectedForm_' . md5($field->getStatePath());

            // Recursively extract all Field instances from the schema tree
            // (handles fields nested inside Grid, Section, etc.)
            $extractFields = function (array $components) use (&$extractFields): array {
                $fields = [];
                foreach ($components as $component) {
                    if (! is_object($component)) {
                        // childComponents is keyed ['default' => [...]] — recurse into sub-arrays
                        if (is_array($component)) {
                            $fields = array_merge($fields, $extractFields($component));
                        }
                        continue;
                    }
                    if ($component instanceof \Filament\Forms\Components\Field) {
                        $fields[] = $component;
                    }
                    // Read raw childComponents property to avoid needing mount
                    try {
                        $ref = new \ReflectionProperty($component, 'childComponents');
                        $children = $ref->getValue($component);
                        if (is_array($children) && count($children)) {
                            $fields = array_merge($fields, $extractFields($children));
                        }
                    } catch (\ReflectionException) {
                        // Component doesn't have childComponents
                    }
                }
                return $fields;
            };

            // Build state from record, resolving each field by name (supports dot notation)
            $formState = $record->toArray();
            foreach ($extractFields($formSchema) as $fieldComponent) {
                $name = $fieldComponent->getName();
                data_set($formState, $name, data_get($record, $name));
            }

            $livewire->data[$stateKey] = $formState;

            // Disable only Field instances — layout components (Grid, Section)
            // don't support the full Field interface
            $disabledSchema = collect($formSchema)->map(function ($component) {
                if ($component instanceof \Filament\Forms\Components\Field) {
                    return $component->disabled();
                }
                return $component;
            })->all();

            $form = \Filament\Schemas\Schema::make($livewire)
                ->schema($disabledSchema)
                ->statePath("data.{$stateKey}")
                ->model($record);
        @endphp

        {{ $form }}
    </div>
@else
    <div class="fi-fo rounded-xl bg-white px-6 py-12 shadow-sm ring-1 ring-gray-950/5 dark:bg-gray-900 dark:ring-white/10">
        <p class="text-center text-sm text-gray-500 dark:text-gray-400">
            {{ $field->getTableEmptyMessage() }}
        </p>
    </div>
@endif
