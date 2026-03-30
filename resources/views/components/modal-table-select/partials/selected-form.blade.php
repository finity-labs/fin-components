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

            // Build a flat state array from all record attributes and relations
            // so fields at any nesting level (inside Grid, Section, etc.) can resolve
            $formState = $record->toArray();

            // Also resolve dot-notation fields that toArray may miss (camelCase relations)
            foreach ($formSchema as $component) {
                if ($component instanceof \Filament\Forms\Components\Field) {
                    $name = $component->getName();
                    data_set($formState, $name, data_get($record, $name));
                }
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
