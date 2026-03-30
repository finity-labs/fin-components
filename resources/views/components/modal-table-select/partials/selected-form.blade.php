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

            // Resolve each field value from the record and store in
            // the livewire component's data array where Schema can find it
            $formState = [];
            foreach ($formSchema as $formField) {
                $name = $formField->getName();
                data_set($formState, $name, data_get($record, $name));
            }

            // Store on the livewire component's public data property
            $livewire->data[$stateKey] = $formState;

            $form = \Filament\Schemas\Schema::make($livewire)
                ->schema(
                    collect($formSchema)
                        ->map(fn ($formField) => $formField->disabled())
                        ->all()
                )
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
