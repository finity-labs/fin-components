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

            // Set record data on livewire so form fields can read it
            foreach ($record->toArray() as $key => $value) {
                data_set($livewire, "{$uniqueKey}.{$key}", $value);
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
