@php
    use FinityLabs\FinModalTableSelect\Enums\DisplayMode;
    use FinityLabs\FinModalTableSelect\Enums\ListStyle;

    $fieldWrapperView = $getFieldWrapperView();
    $extraAttributes = $getExtraAttributes();
    $id = $getId();
    $isDisabled = $isDisabled();
    $isMultiple = $isMultiple();
    $hasBadges = $hasBadges();
    $badgeColor = $getBadgeColor();
    $displayMode = $getDisplayMode();
    $isSelectionOnly = $displayMode === DisplayMode::SelectionOnly;
    $hasCustomDisplay = $hasCustomDisplay();
    $state = $getState();
    $statePath = $getStatePath();
    $listStyle = $getListStyle();
    $hasRecordBadges = $hasRecordBadges();
@endphp

<x-dynamic-component :component="$fieldWrapperView" :field="$field">
    <div
        {{
            $attributes
                ->merge([
                    'id' => $id,
                ], escape: false)
                ->merge($extraAttributes, escape: false)
                ->class([
                    'fi-fo-modal-table-select w-full',
                    'fi-fo-modal-table-select-disabled' => $isDisabled,
                    'fi-fo-modal-table-select-multiple' => $isMultiple,
                ])
        }}
    >
        @if ($isSelectionOnly)
            {{-- Selection-only mode: optional compact summary --}}
            @if (filled($state) && $getHasSelectionSummary())
                @php
                    $count = is_array($state) ? count($state) : ($state ? 1 : 0);
                @endphp

                <x-filament::badge color="gray">
                    {{ $getSelectionSummaryLabel($count) }}
                </x-filament::badge>
            @endif
        @elseif ($hasCustomDisplay)
            {{-- Custom display modes --}}
            @php
                $hasValue = filled($isMultiple ? $state : ($state ?? null));
            @endphp

            @if ($hasValue)
                <div class="w-full">
                    @if ($displayMode === DisplayMode::ItemView)
                        @include('fin-modal-table-select::components.modal-table-select.partials.selected-item-view')
                    @elseif ($displayMode === DisplayMode::Table)
                        @include('fin-modal-table-select::components.modal-table-select.partials.selected-table')
                    @elseif ($displayMode === DisplayMode::Cards)
                        @include('fin-modal-table-select::components.modal-table-select.partials.selected-cards')
                    @elseif ($displayMode === DisplayMode::Thumbnails)
                        @include('fin-modal-table-select::components.modal-table-select.partials.selected-thumbnails')
                    @elseif ($displayMode === DisplayMode::StackedList)
                        @include('fin-modal-table-select::components.modal-table-select.partials.selected-stacked-list')
                    @elseif ($displayMode === DisplayMode::Infolist)
                        @include('fin-modal-table-select::components.modal-table-select.partials.selected-infolist')
                    @endif
                </div>
            @else
                <div>
                    <p class="text-sm text-gray-500 dark:text-gray-400 italic">
                        {{ $getTableEmptyMessage() }}
                    </p>

                    @if ($getHasEmptyStateSelectButton() && ! $isDisabled)
                        <div class="mt-2">
                            {{ ($getAction('select'))([])->link()->label(__('fin-modal-table-select::modal-table-select.select')) }}
                        </div>
                    @endif
                </div>
            @endif
        @else
            {{-- Default display: badges / styled lists, inheriting parent behavior --}}
            @if ($hasRecordBadges && filled($state))
                {{-- Badges rendered from records, so color and icon can vary per item --}}
                @php
                    $badgeRecords = $getSelectedRecords();
                    $displayLimit = $getDisplayLimit();
                    $badgeCount = $badgeRecords->count();
                    $hasBadgeOverflow = ($displayLimit !== null) && ($badgeCount > $displayLimit);
                @endphp

                <div
                    class="fi-fo-modal-table-select-badges-ctn"
                    @if ($hasBadgeOverflow) x-data="{ expanded: false }" @endif
                >
                    @foreach ($badgeRecords as $badgeIndex => $badgeRecord)
                        @php
                            $badge = view()->make('fin-modal-table-select::components.modal-table-select.partials.record-badge', [
                                'field' => $field,
                                'record' => $badgeRecord,
                            ]);
                        @endphp

                        @if ($hasBadgeOverflow && $badgeIndex >= $displayLimit)
                            <span x-show="expanded" x-cloak>{{ $badge }}</span>
                        @else
                            {{ $badge }}
                        @endif
                    @endforeach

                    @if ($hasBadgeOverflow)
                        <button
                            type="button"
                            x-on:click="expanded = ! expanded"
                            class="text-sm font-medium text-gray-500 hover:text-gray-700 dark:text-gray-400 dark:hover:text-gray-200"
                        >
                            <span x-show="! expanded">
                                {{ trans_choice('fin-modal-table-select::modal-table-select.more', $badgeCount - $displayLimit, ['count' => $badgeCount - $displayLimit]) }}
                            </span>
                            <span x-show="expanded" x-cloak>
                                {{ __('fin-modal-table-select::modal-table-select.less') }}
                            </span>
                        </button>
                    @endif
                </div>
            @elseif (((! $isMultiple) && filled($optionLabel = $getOptionLabel())) ||
                 ($isMultiple && filled($optionLabels = $getOptionLabels())))
                @if ($isMultiple && $hasBadges)
                    @php
                        $displayLimit = $getDisplayLimit();
                        $badgeCount = count($optionLabels);
                        $hasBadgeOverflow = ($displayLimit !== null) && ($badgeCount > $displayLimit);
                    @endphp

                    <div
                        class="fi-fo-modal-table-select-badges-ctn"
                        @if ($hasBadgeOverflow) x-data="{ expanded: false }" @endif
                    >
                        @foreach (array_values($optionLabels) as $badgeIndex => $optionLabel)
                            @if ($hasBadgeOverflow && $badgeIndex >= $displayLimit)
                                <span x-show="expanded" x-cloak>
                                    <x-filament::badge :color="$badgeColor">
                                        {{ $optionLabel }}
                                    </x-filament::badge>
                                </span>
                            @else
                                <x-filament::badge :color="$badgeColor">
                                    {{ $optionLabel }}
                                </x-filament::badge>
                            @endif
                        @endforeach

                        @if ($hasBadgeOverflow)
                            <button
                                type="button"
                                x-on:click="expanded = ! expanded"
                                class="text-sm font-medium text-gray-500 hover:text-gray-700 dark:text-gray-400 dark:hover:text-gray-200"
                            >
                                <span x-show="! expanded">
                                    {{ trans_choice('fin-modal-table-select::modal-table-select.more', $badgeCount - $displayLimit, ['count' => $badgeCount - $displayLimit]) }}
                                </span>
                                <span x-show="expanded" x-cloak>
                                    {{ __('fin-modal-table-select::modal-table-select.less') }}
                                </span>
                            </button>
                        @endif
                    </div>
                @elseif ($isMultiple && $listStyle)
                    {{-- Text list styles: comma, dot, bullet, or line-break separated --}}
                    @if ($listStyle === ListStyle::Bullet)
                        <ul class="list-inside list-disc text-sm text-gray-950 dark:text-white">
                            @foreach ($optionLabels as $optionLabel)
                                <li>{{ $optionLabel }}</li>
                            @endforeach
                        </ul>
                    @elseif ($listStyle === ListStyle::LineBreak)
                        <div class="flex flex-col gap-1 text-sm text-gray-950 dark:text-white">
                            @foreach ($optionLabels as $optionLabel)
                                <p>{{ $optionLabel }}</p>
                            @endforeach
                        </div>
                    @else
                        @php
                            $separator = $listStyle === ListStyle::Dot ? ' · ' : ', ';
                        @endphp

                        <p class="text-sm text-gray-950 dark:text-white">
                            {{ implode($separator, array_map(strval(...), array_values($optionLabels))) }}
                        </p>
                    @endif
                @else
                    <div>
                        @if ($hasBadges)
                            <x-filament::badge :color="$badgeColor">
                                {{ $optionLabel }}
                            </x-filament::badge>
                        @elseif ($isMultiple)
                            @foreach ($optionLabels as $optionLabel)
                                {{ $optionLabel . ($loop->last ? '' : ', ') }}
                            @endforeach
                        @else
                            {{ $optionLabel }}
                        @endif
                    </div>
                @endif
            @else
                @if (filled($placeholder = $getPlaceholder()))
                    <div class="fi-fo-modal-table-select-placeholder">
                        {{ $placeholder }}
                    </div>
                @endif

                @if ($getHasEmptyStateSelectButton() && ! $isDisabled)
                    <div @class(['mt-2' => filled($getPlaceholder())])>
                        {{ ($getAction('select'))([])->link()->label(__('fin-modal-table-select::modal-table-select.select')) }}
                    </div>
                @endif
            @endif
        @endif

    </div>
</x-dynamic-component>
