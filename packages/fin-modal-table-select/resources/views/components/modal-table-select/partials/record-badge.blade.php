@php
    $badgeIcon = $field->getBadgeIconForRecord($record);
@endphp

<x-filament::badge :color="$field->getBadgeColorForRecord($record)" :icon="$badgeIcon">
    {{ $field->getRecordDisplayLabel($record) }}
</x-filament::badge>
