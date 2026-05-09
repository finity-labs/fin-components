<x-dynamic-component
    :component="$getEntryWrapperView()"
    :entry="$entry"
>
    <div class="fi-ta-actions flex shrink-0 items-center gap-3">
        <x-filament::link
            icon="heroicon-o-eye"
            color="gray"
            size="sm"
            tag="button"
            wire:click="previewVersion({{ $getRecord()->version }})"
        >
            {{ __('fin-mail::fin-mail.template.actions.preview') }}
        </x-filament::link>
        <x-filament::link
            icon="heroicon-o-arrow-uturn-left"
            color="gray"
            size="sm"
            tag="button"
            wire:click="restoreVersion({{ $getRecord()->version }})"
            wire:confirm="{{ __('fin-mail::fin-mail.template.versioning.restore_confirm', ['version' => $getRecord()->version]) }}"
        >
            {{ __('fin-mail::fin-mail.template.versioning.restore') }}
        </x-filament::link>
    </div>
</x-dynamic-component>
