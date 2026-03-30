@php
    $record = $field->getSelectedRecord();
    $formSchema = $field->getFormSchema();
    $columns = $field->getFormColumns();
@endphp

@if ($record && $formSchema)
    <div class="fi-fo rounded-xl bg-white p-6 shadow-sm ring-1 ring-gray-950/5 dark:bg-gray-900 dark:ring-white/10">
        @php
            $livewire = $field->getLivewire();
            $uniqueKey = 'finSelectedForm_' . $field->getStatePath();

            // Resolve each field's value from the record using data_get
            // which handles camelCase relationships and dot notation
            foreach ($formSchema as $formField) {
                $name = $formField->getName();
                $value = data_get($record, $name);
                data_set($livewire, "{$uniqueKey}.{$name}", $value);
            }

            $form = \Filament\Schemas\Schema::make($livewire)
                ->schema(
                    collect($formSchema)
                        ->map(fn ($formField) => $formField->disabled())
                        ->all()
                )
                ->columns($columns)
                ->statePath($uniqueKey)
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
