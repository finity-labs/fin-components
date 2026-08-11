@php
    $records = $field->getSelectedRecords();
    $limit = $field->getDisplayLimit();
    $count = $records->count();
    $hasOverflow = ($limit !== null) && ($count > $limit);
    $isRemovable = $field->getIsStackedListRemovable() && ! $field->isDisabled();
    $removeAction = $field->getAction('removeSelectedItem');
@endphp

@if ($records->isNotEmpty())
    <div
        class="fi-fo-modal-table-select-stacked w-full divide-y divide-gray-100 overflow-hidden rounded-xl bg-white shadow-sm ring-1 ring-gray-950/5 dark:divide-white/5 dark:bg-gray-900 dark:ring-white/10"
        @if ($hasOverflow) x-data="{ expanded: false }" @endif
    >
        @foreach ($records as $index => $record)
            <div
                class="flex items-center gap-3 px-3 py-2"
                @if ($hasOverflow && $index >= $limit) x-show="expanded" x-cloak x-collapse @endif
            >
                @php
                    $image = $field->getStackedListImage($record);
                    $secondary = $field->getStackedListSecondary($record);
                @endphp

                @if (filled($image))
                    <img src="{{ $image }}" alt="" class="h-8 w-8 shrink-0 rounded-full object-cover" />
                @endif

                <div class="min-w-0 flex-1">
                    <p class="truncate text-sm font-medium text-gray-950 dark:text-white">
                        {{ $field->getStackedListPrimary($record) }}
                    </p>

                    @if (filled($secondary))
                        <p class="truncate text-sm text-gray-500 dark:text-gray-400">
                            {{ $secondary }}
                        </p>
                    @endif
                </div>

                @if ($isRemovable && $removeAction)
                    <div class="shrink-0">
                        {{ $removeAction(['recordKey' => $record->getKey()]) }}
                    </div>
                @endif
            </div>
        @endforeach

        @if ($hasOverflow)
            <div class="px-3 py-2">
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
