@php
    $records = $field->getSelectedRecords();
    $itemView = $field->getItemView();
    $itemViewData = $field->getItemViewData();
    $removeAction = $field->isDisabled() ? null : $field->getAction('removeSelectedItem');
@endphp

@if ($records->isNotEmpty() && filled($itemView))
    <div class="fi-fo-modal-table-select-item-views flex w-full flex-col gap-2">
        @foreach ($records as $record)
            @include($itemView, array_merge($itemViewData, [
                'record' => $record,
                'field' => $field,
                'removeAction' => $removeAction ? $removeAction(['recordKey' => $record->getKey()]) : null,
            ]))
        @endforeach
    </div>
@else
    <div>
        <p class="text-sm text-gray-500 italic dark:text-gray-400">
            {{ $field->getTableEmptyMessage() }}
        </p>
    </div>
@endif
