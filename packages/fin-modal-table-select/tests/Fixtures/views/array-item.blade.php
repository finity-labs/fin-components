<div class="array-item-view">
    <span>ROUTE: {{ $record['label'] ?? $record['route'] }}</span>
    @if ($removeAction)
        {{ $removeAction }}
    @endif
</div>
