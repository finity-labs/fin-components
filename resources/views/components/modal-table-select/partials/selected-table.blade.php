@php
    $records = $field->getRecords();
    $columns = $field->getTableColumns();
    $isStriped = $field->getIsTableStriped();
    $isDisabled = $field->isDisabled();
    $statePath = $field->getStatePath();
@endphp

<div class="fi-ta rounded-xl bg-white shadow-sm ring-1 ring-gray-950/5 dark:bg-gray-900 dark:ring-white/10">
    @if ($records->isNotEmpty())
        <div class="fi-ta-content divide-y divide-gray-200 overflow-x-auto dark:divide-white/10 dark:border-t-white/10">
            <table class="fi-ta-table w-full table-auto divide-y divide-gray-200 text-start dark:divide-white/10">
                <thead class="divide-y divide-gray-200 dark:divide-white/10">
                    <tr class="bg-gray-50 dark:bg-white/5">
                        @foreach ($columns as $column)
                            <th class="fi-ta-header-cell px-3 py-3.5 sm:first-of-type:ps-6 sm:last-of-type:pe-6">
                                <span class="fi-ta-header-cell-label text-sm font-semibold text-gray-950 dark:text-white">
                                    {{ $column->getLabel() }}
                                </span>
                            </th>
                        @endforeach

                        @if (! $isDisabled)
                            <th class="fi-ta-header-cell w-1 px-3 py-3.5 sm:last-of-type:pe-6">
                                <span class="sr-only">
                                    {{ __('fin-modal-table-select::modal-table-select.actions') }}
                                </span>
                            </th>
                        @endif
                    </tr>
                </thead>

                <tbody class="divide-y divide-gray-200 whitespace-nowrap dark:divide-white/10">
                    @foreach ($records as $index => $record)
                        <tr @class([
                            'fi-ta-row',
                            'bg-gray-50/50 dark:bg-white/[0.02]' => $isStriped && $index % 2 === 1,
                        ])>
                            @foreach ($columns as $column)
                                @php
                                    $cell = $field->resolveColumnCell($column, $record);
                                @endphp

                                <td class="fi-ta-cell px-3 py-4 text-sm text-gray-950 sm:first-of-type:ps-6 sm:last-of-type:pe-6 dark:text-white">
                                    @if ($cell['isBadge'])
                                        <x-filament::badge :color="$cell['color']">
                                            {{ $cell['label'] }}
                                        </x-filament::badge>
                                    @else
                                        {{ $cell['label'] }}
                                    @endif
                                </td>
                            @endforeach

                            @if (! $isDisabled)
                                <td class="fi-ta-cell px-3 py-4 sm:last-of-type:pe-6">
                                    <div class="flex items-center justify-end">
                                        <button
                                            type="button"
                                            x-on:click="
                                                $wire.set('{{ $statePath }}', @js(
                                                    collect($field->getState())
                                                        ->filter(fn ($v) => (string) $v !== (string) $record->getKey())
                                                        ->values()
                                                        ->all()
                                                ))
                                            "
                                            class="fi-icon-btn relative flex items-center justify-center rounded-lg outline-none transition duration-75 focus-visible:ring-2 fi-color-gray fi-icon-btn-size-sm -m-1.5 h-8 w-8 text-gray-400 hover:text-gray-500 focus-visible:ring-primary-600 dark:text-gray-500 dark:hover:text-gray-400 dark:focus-visible:ring-primary-500"
                                            title="{{ __('fin-modal-table-select::modal-table-select.remove') }}"
                                        >
                                            <x-filament::icon
                                                icon="heroicon-m-x-mark"
                                                class="h-5 w-5"
                                            />
                                        </button>
                                    </div>
                                </td>
                            @endif
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>

        <div class="fi-ta-footer px-3 py-2 sm:px-6">
            <p class="text-sm text-gray-500 dark:text-gray-400">
                {{ trans_choice('fin-modal-table-select::modal-table-select.count', $records->count(), ['count' => $records->count()]) }}
            </p>
        </div>
    @else
        <div class="fi-ta-empty-state px-6 py-12">
            <div class="fi-ta-empty-state-content mx-auto grid max-w-lg justify-items-center text-center">
                <div class="fi-ta-empty-state-icon-ctn mb-4 rounded-full bg-gray-100 p-3 dark:bg-gray-500/20">
                    <x-filament::icon
                        icon="heroicon-o-x-mark"
                        class="fi-ta-empty-state-icon h-6 w-6 text-gray-500 dark:text-gray-400"
                    />
                </div>

                <h4 class="fi-ta-empty-state-heading text-base font-semibold leading-6 text-gray-950 dark:text-white">
                    {{ $field->getTableEmptyMessage() }}
                </h4>
            </div>
        </div>
    @endif
</div>
