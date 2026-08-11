@php
    $records = $field->getSelectedRecords();
    $limit = $field->getDisplayLimit();
    $count = $records->count();
    $hasOverflow = ($limit !== null) && ($count > $limit);
    $isCircular = $field->getIsThumbnailsCircular();
    $isRemovable = $field->getIsThumbnailsRemovable() && ! $field->isDisabled();
    $removeAction = $field->getAction('removeSelectedItem');
@endphp

@if ($records->isNotEmpty())
    <div
        class="fi-fo-modal-table-select-thumbs flex flex-wrap items-center gap-2"
        @if ($hasOverflow) x-data="{ expanded: false }" @endif
    >
        @foreach ($records as $index => $record)
            <div
                class="relative"
                @if ($hasOverflow && $index >= $limit) x-show="expanded" x-cloak @endif
            >
                @php
                    $image = $field->getThumbnailImage($record);
                    $label = $field->getRecordDisplayLabel($record);
                @endphp

                @if (filled($image))
                    <img
                        src="{{ $image }}"
                        alt="{{ $label }}"
                        title="{{ $label }}"
                        @class([
                            'h-10 w-10 object-cover ring-1 ring-gray-950/10 dark:ring-white/20',
                            'rounded-full' => $isCircular,
                            'rounded-lg' => ! $isCircular,
                        ])
                    />
                @else
                    <div
                        title="{{ $label }}"
                        @class([
                            'flex h-10 w-10 items-center justify-center bg-gray-100 text-xs font-medium text-gray-600 ring-1 ring-gray-950/10 dark:bg-gray-800 dark:text-gray-300 dark:ring-white/20',
                            'rounded-full' => $isCircular,
                            'rounded-lg' => ! $isCircular,
                        ])
                    >
                        {{ mb_substr($label, 0, 2) }}
                    </div>
                @endif

                @if ($isRemovable && $removeAction)
                    <div class="absolute -end-2 -top-2">
                        {{ $removeAction(['recordKey' => $record->getKey()]) }}
                    </div>
                @endif
            </div>
        @endforeach

        @if ($hasOverflow)
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
        @endif
    </div>
@else
    <div>
        <p class="text-sm text-gray-500 italic dark:text-gray-400">
            {{ $field->getTableEmptyMessage() }}
        </p>
    </div>
@endif
