@php
    $record = $field->getSelectedRecord();
    $schema = $field->getInfolistSchema();
    $columns = $field->getInfolistColumns();
@endphp

@if ($record && $schema)
    <div class="fi-in rounded-xl bg-white p-6 shadow-sm ring-1 ring-gray-950/5 dark:bg-gray-900 dark:ring-white/10">
        @php
            $infolist = \Filament\Infolists\Infolist::make()
                ->record($record)
                ->schema($schema)
                ->columns($columns);
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
