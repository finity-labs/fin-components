@php
    $record = $field->getSelectedRecord();
    $schema = $field->getFormSchema();
    $columns = $field->getFormColumns();
@endphp

@if ($record && $schema)
    <div class="fi-fo rounded-xl bg-white p-6 shadow-sm ring-1 ring-gray-950/5 dark:bg-gray-900 dark:ring-white/10">
        @php
            $form = \Filament\Schemas\Schema::make($field->getLivewire())
                ->schema(
                    collect($schema)
                        ->map(fn ($formField) => $formField->disabled())
                        ->all()
                )
                ->columns($columns)
                ->model($record)
                ->statePath(null)
                ->fill($record->toArray());
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
