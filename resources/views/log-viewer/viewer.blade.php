<x-filament-panels::page>
    <div class="mb-4">
        <x-filament::link :href="\FinityLabs\FinSentinel\Pages\LogFileList::getUrl()" icon="heroicon-o-arrow-left">
            {{ __('fin-sentinel::fin-sentinel.log_back_to_list') }}
        </x-filament::link>
    </div>

    {{ $this->table }}
</x-filament-panels::page>
