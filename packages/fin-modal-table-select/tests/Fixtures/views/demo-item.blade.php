<div class="demo-item-view">
    <span>ITEM: {{ $record->title }}</span>
    @if ($removeAction)
        {{ $removeAction }}
    @endif
</div>
