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

            // Recursively extract field names from schema (skip layout components)
            $extractFieldNames = function (array $components) use (&$extractFieldNames): array {
                $names = [];
                foreach ($components as $component) {
                    if (method_exists($component, 'getName') && method_exists($component, 'getState')) {
                        $names[] = $component->getName();
                    }
                    if (method_exists($component, 'getChildComponents')) {
                        $names = array_merge($names, $extractFieldNames($component->getChildComponents()));
                    }
                }
                return $names;
            };

            $formState = [];
            foreach ($extractFieldNames($formSchema) as $name) {
                data_set($formState, $name, data_get($record, $name));
            }

            $livewire->data[$stateKey] = $formState;

            // Disable all fields recursively
            $disableFields = function (array $components) use (&$disableFields): array {
                return collect($components)->map(function ($component) use ($disableFields) {
                    if (method_exists($component, 'disabled')) {
                        $component->disabled();
                    }
                    if (method_exists($component, 'schema') && method_exists($component, 'getChildComponents')) {
                        $children = $component->getChildComponents();
                        if (! empty($children)) {
                            $component->schema($disableFields($children));
                        }
                    }
                    return $component;
                })->all();
            };

            $form = \Filament\Schemas\Schema::make($livewire)
                ->schema($disableFields($formSchema))
                ->columns($columns)
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
