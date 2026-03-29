@php
    use FinityLabs\FinComponents\Components\ModalTableSelect\Enums\DisplayMode;

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
                    'fi-fo-modal-table-select',
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
            {{-- Custom display modes: table, infolist, or form --}}
            @php
                $hasValue = filled($isMultiple ? $state : ($state ?? null));
            @endphp

            @if ($hasValue)
                <div>
                    @if ($displayMode === DisplayMode::Table)
                        @include('fin-components::components.modal-table-select.partials.selected-table')
                    @elseif ($displayMode === DisplayMode::Infolist)
                        @include('fin-components::components.modal-table-select.partials.selected-infolist')
                    @elseif ($displayMode === DisplayMode::Form)
                        @include('fin-components::components.modal-table-select.partials.selected-form')
                    @endif
                </div>
            @else
                <div>
                    <p class="text-sm text-gray-500 dark:text-gray-400 italic">
                        {{ $getTableEmptyMessage() }}
                    </p>
                </div>
            @endif
        @else
            {{-- Default display: inherit parent behavior --}}
            @if (((! $isMultiple) && filled($optionLabel = $getOptionLabel())) ||
                 ($isMultiple && filled($optionLabels = $getOptionLabels())))
                @if ($isMultiple && $hasBadges)
                    <div class="fi-fo-modal-table-select-badges-ctn">
                        @foreach ($optionLabels as $optionLabel)
                            @if ($hasBadges)
                                <x-filament::badge :color="$badgeColor">
                                    {{ $optionLabel }}
                                </x-filament::badge>
                            @else
                                {{ $optionLabel }}
                            @endif
                        @endforeach
                    </div>
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
            @elseif (filled($placeholder = $getPlaceholder()))
                <div class="fi-fo-modal-table-select-placeholder">
                    {{ $placeholder }}
                </div>
            @endif
        @endif

        @if (! $isDisabled)
            <div>
                {{ $getAction('select') }}
            </div>
        @endif
    </div>
</x-dynamic-component>
