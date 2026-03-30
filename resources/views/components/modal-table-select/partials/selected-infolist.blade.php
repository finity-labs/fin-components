@php
    $record = $field->getSelectedRecord();
    $schema = $field->getInfolistSchema();
    $columns = $field->getInfolistColumns();
@endphp

@if ($record && $schema)
    <div class="fi-in rounded-xl bg-white p-6 shadow-sm ring-1 ring-gray-950/5 dark:bg-gray-900 dark:ring-white/10">
        <div @class([
            'grid gap-6',
            match ($columns) {
                2 => 'sm:grid-cols-2',
                3 => 'sm:grid-cols-3',
                4 => 'sm:grid-cols-4',
                default => 'sm:grid-cols-1',
            },
        ])>
            @foreach ($schema as $entry)
                @php
                    $name = $entry->getName();
                    $value = data_get($record, $name);
                    $label = $entry->getLabel() ?? str($name)->replace('.', ' ')->replace('_', ' ')->headline()->toString();

                    if ($value instanceof \Filament\Support\Contracts\HasLabel) {
                        $displayValue = $value->getLabel() ?? $value->value;
                    } elseif ($value instanceof \BackedEnum) {
                        $displayValue = $value->value;
                    } elseif ($value instanceof \UnitEnum) {
                        $displayValue = $value->name;
                    } elseif (is_bool($value)) {
                        $displayValue = $value ? __('Yes') : __('No');
                    } elseif (is_array($value)) {
                        $displayValue = implode(', ', $value);
                    } else {
                        $displayValue = $value;
                    }
                @endphp

                <div class="fi-in-entry">
                    <dt class="fi-in-entry-label text-sm font-medium leading-6 text-gray-950 dark:text-white">
                        {{ $label }}
                    </dt>

                    <dd class="fi-in-entry-content mt-1 text-sm leading-6 text-gray-500 dark:text-gray-400">
                        {{ $displayValue ?? '—' }}
                    </dd>
                </div>
            @endforeach
        </div>
    </div>
@else
    <div class="fi-in rounded-xl bg-white px-6 py-12 shadow-sm ring-1 ring-gray-950/5 dark:bg-gray-900 dark:ring-white/10">
        <p class="text-center text-sm text-gray-500 dark:text-gray-400">
            {{ $field->getTableEmptyMessage() }}
        </p>
    </div>
@endif
