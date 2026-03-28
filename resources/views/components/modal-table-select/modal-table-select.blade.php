@php
    use FinityLabs\FinComponents\Components\ModalTableSelect\Enums\DisplayMode;

    $displayMode = $getDisplayMode();
    $isSelectionOnly = $displayMode === DisplayMode::SelectionOnly;
    $hasCustomDisplay = $hasCustomDisplay();
    $isMultiple = $isMultiple();
    $state = $getState();
    $hasValue = filled($isMultiple ? $state : ($state ?? null));
    $isDisabled = $isDisabled();
    $statePath = $getStatePath();
@endphp

<x-dynamic-component :component="$getFieldWrapperView()" :field="$field">
    <div
        x-data="{}"
        {{
            $attributes
                ->merge($getExtraAttributes(), escape: false)
                ->class(['fin-modal-table-select'])
        }}
    >
        {{-- Action button to open the selection modal --}}
        <div class="flex items-center gap-3">
            @if (! $isDisabled)
                <div>
                    {{ $getAction('select') }}
                </div>
            @endif

            @if ($isSelectionOnly)
                {{-- Selection-only mode: optional compact summary --}}
                @if ($hasValue && $getHasSelectionSummary())
                    @php
                        $count = is_array($state) ? count($state) : ($state ? 1 : 0);
                    @endphp

                    <x-filament::badge color="gray">
                        {{ $getSelectionSummaryLabel($count) }}
                    </x-filament::badge>
                @endif
            @elseif ($hasValue && ! $hasCustomDisplay)
                {{-- Default display: badges for multiple, text for single --}}
                <div class="flex flex-wrap gap-1.5">
                    @if ($isMultiple)
                        @foreach ($getOptionLabels() as $optionValue => $optionLabel)
                            <x-filament::badge
                                :color="$getBadgeColor()"
                            >
                                {{ $optionLabel }}

                                @if (! $isDisabled)
                                    <x-slot name="deleteButton" x-on:click="
                                        $wire.set('{{ $statePath }}', @js(
                                            collect($state)
                                                ->filter(fn ($v) => (string) $v !== (string) $optionValue)
                                                ->values()
                                                ->all()
                                        ))
                                    " />
                                @endif
                            </x-filament::badge>
                        @endforeach
                    @else
                        @if ($hasBadges())
                            <x-filament::badge :color="$getBadgeColor()">
                                {{ $getOptionLabels()[$state] ?? $state }}
                            </x-filament::badge>
                        @else
                            <span class="text-sm text-gray-950 dark:text-white">
                                {{ $getOptionLabels()[$state] ?? $state }}
                            </span>
                        @endif
                    @endif
                </div>
            @endif
        </div>

        {{-- Custom display area (not rendered in selection-only mode) --}}
        @if (! $isSelectionOnly)
            @if ($hasValue && $hasCustomDisplay)
                <div class="mt-3">
                    @if ($displayMode === DisplayMode::Table)
                        @include('fin-components::components.modal-table-select.partials.selected-table')
                    @elseif ($displayMode === DisplayMode::Infolist)
                        @include('fin-components::components.modal-table-select.partials.selected-infolist')
                    @elseif ($displayMode === DisplayMode::Form)
                        @include('fin-components::components.modal-table-select.partials.selected-form')
                    @endif
                </div>
            @elseif (! $hasValue && $hasCustomDisplay)
                <div class="mt-3">
                    <p class="text-sm text-gray-500 dark:text-gray-400 italic">
                        {{ $getTableEmptyMessage() }}
                    </p>
                </div>
            @endif
        @endif
    </div>
</x-dynamic-component>
