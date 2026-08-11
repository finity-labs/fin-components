@php
    $records = $field->getSelectedRecords();
    $limit = $field->getDisplayLimit();
    $count = $records->count();
    $hasOverflow = ($limit !== null) && ($count > $limit);
    $isRemovable = $field->getIsCardsRemovable() && ! $field->isDisabled();
    $removeAction = $field->getAction('removeSelectedItem');
    $columns = max(1, $field->getCardColumns());
@endphp

@if ($records->isNotEmpty())
    <div
        class="fi-fo-modal-table-select-cards grid w-full gap-3"
        style="grid-template-columns: repeat({{ $columns }}, minmax(0, 1fr));"
        @if ($hasOverflow) x-data="{ expanded: false }" @endif
    >
        @foreach ($records as $index => $record)
            <div
                class="relative overflow-hidden rounded-xl bg-white shadow-sm ring-1 ring-gray-950/5 dark:bg-gray-900 dark:ring-white/10"
                @if ($hasOverflow && $index >= $limit) x-show="expanded" x-cloak @endif
            >
                @php
                    $image = $field->getCardImage($record);
                    $description = $field->getCardDescription($record);
                @endphp

                @if (filled($image))
                    <img src="{{ $image }}" alt="" class="aspect-video w-full object-cover" />
                @endif

                <div class="p-3">
                    <p class="truncate text-sm font-medium text-gray-950 dark:text-white">
                        {{ $field->getCardTitle($record) }}
                    </p>

                    @if (filled($description))
                        <p class="mt-1 line-clamp-2 text-sm text-gray-500 dark:text-gray-400">
                            {{ $description }}
                        </p>
                    @endif
                </div>

                @if ($isRemovable && $removeAction)
                    <div class="absolute end-1 top-1 rounded-full bg-white/80 dark:bg-gray-900/80">
                        {{ $removeAction(['recordKey' => $record->getKey()]) }}
                    </div>
                @endif
            </div>
        @endforeach

        @if ($hasOverflow)
            <div class="flex items-center">
                <button
                    type="button"
                    x-on:click="expanded = ! expanded"
                    class="text-sm font-medium text-gray-500 hover:text-gray-700 dark:text-gray-400 dark:hover:text-gray-200"
                >
                    <span x-show="! expanded">
                        {{ trans_choice('fin-modal-table-select::modal-table-select.more', $count - $limit, ['count' => $count - $limit]) }}
                    </span>
                    <span x-show="expanded" x-cloak>
                        {{ __('fin-modal-table-select::modal-table-select.less') }}
                    </span>
                </button>
            </div>
        @endif
    </div>
@else
    <div>
        <p class="text-sm text-gray-500 italic dark:text-gray-400">
            {{ $field->getTableEmptyMessage() }}
        </p>
    </div>
@endif
