@php
    $schema = $field->makeSelectedTableSchema();

    $hasFooterCount = $field->getHasTableFooterCount();
    $isCollapsible = $field->getIsTableCollapsible();
    $hasChrome = $hasFooterCount || $isCollapsible;

    $count = $field->getSelectedRecords()->count();
    $countLabel = trans_choice('fin-modal-table-select::modal-table-select.count', $count, ['count' => $count]);
@endphp

@if ($schema)
    {{-- The collapse chevron lives on the field's label line as a hint action
         (see ModalTableSelect::getCollapseToggleAction); it flips the Alpine
         `open` flag declared on the field wrapper, which this body reacts to. --}}
    <div @class([
        'fi-fo-modal-table-select-table w-full min-w-0',
        'overflow-hidden rounded-xl bg-white shadow-sm ring-1 ring-gray-950/5 dark:bg-gray-900 dark:ring-white/10' => $hasChrome,
    ])>
        <div @if ($isCollapsible) x-show="open" x-collapse @endif>
            {{ $schema }}

            @if ($hasFooterCount)
                <div class="border-t border-gray-200 px-3 py-2 sm:px-4 dark:border-white/10">
                    <p class="text-sm text-gray-500 dark:text-gray-400">
                        {{ $countLabel }}
                    </p>
                </div>
            @endif
        </div>
    </div>
@else
    <div>
        <p class="text-sm text-gray-500 italic dark:text-gray-400">
            {{ $field->getTableEmptyMessage() }}
        </p>
    </div>
@endif
