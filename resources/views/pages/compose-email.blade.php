<x-filament-panels::page>
    <form wire:submit.prevent="send">
        {{ $this->form }}

        <div class="mt-6 flex items-center gap-3">
            <x-filament::button
                type="button"
                wire:click="send"
                icon="heroicon-o-paper-airplane"
            >
                {{ __('fin-mail::fin-mail.compose.actions.send') }}
            </x-filament::button>

            <x-filament::button
                type="button"
                wire:click="preview"
                color="gray"
                icon="heroicon-o-eye"
            >
                {{ __('fin-mail::fin-mail.compose.actions.preview') }}
            </x-filament::button>

            <x-filament::button
                tag="a"
                :href="$this->getResource()::getUrl('edit', ['record' => $this->record])"
                color="gray"
                icon="heroicon-o-pencil"
            >
                {{ __('fin-mail::fin-mail.template.tabs.content') }}
            </x-filament::button>
        </div>
    </form>

    {{-- Preview Modal --}}
    <x-filament::modal id="email-preview" width="4xl" :heading="__('fin-mail::fin-mail.template.actions.preview')">
        <div class="bg-gray-100 dark:bg-gray-800 p-4 rounded-lg">
            <div class="bg-white dark:bg-gray-900 mx-auto shadow-lg rounded-lg overflow-hidden" style="max-width: 600px;">
                <div class="p-6" id="email-preview-content">
                    @php
                        $body = $this->data['body'] ?? '';
                        if (is_array($body)) {
                            $body = $body[app()->getLocale()] ?? reset($body) ?: '';
                        }
                    @endphp
                    {!! $body !!}
                </div>
            </div>
        </div>
    </x-filament::modal>
</x-filament-panels::page>
