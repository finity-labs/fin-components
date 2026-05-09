@if($versions->isNotEmpty())
    <div class="fi-ta">
        <div class="fi-ta-content divide-y divide-gray-200 overflow-x-auto dark:divide-white/10 dark:border-t-white/10">
            <table class="fi-ta-table w-full table-auto divide-y divide-gray-200 text-start dark:divide-white/5">
                <thead class="bg-gray-50 dark:bg-white/5">
                    <tr>
                        <th class="fi-ta-header-cell px-3 py-3.5 sm:first-of-type:ps-6">
                            <span class="fi-ta-header-cell-label text-sm font-semibold text-gray-950 dark:text-white">#</span>
                        </th>
                        <th class="fi-ta-header-cell px-3 py-3.5">
                            <span class="fi-ta-header-cell-label text-sm font-semibold text-gray-950 dark:text-white">{{ __('fin-mail::fin-mail.template.versioning.date') }}</span>
                        </th>
                        <th class="fi-ta-header-cell px-3 py-3.5">
                            <span class="fi-ta-header-cell-label text-sm font-semibold text-gray-950 dark:text-white">{{ __('fin-mail::fin-mail.template.versioning.by') }}</span>
                        </th>
                        <th class="fi-ta-header-cell px-3 py-3.5 sm:last-of-type:pe-6"></th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-200 whitespace-nowrap dark:divide-white/5">
                    @foreach($versions as $version)
                        <tr class="fi-ta-row transition duration-75 hover:bg-gray-50 dark:hover:bg-white/5">
                            <td class="fi-ta-cell px-3 py-4 sm:first-of-type:ps-6">
                                <span class="text-sm font-medium text-gray-950 dark:text-white">{{ $version->version }}</span>
                            </td>
                            <td class="fi-ta-cell px-3 py-4">
                                <span class="text-sm text-gray-500 dark:text-gray-400">
                                    {{ ($fmt = app('fin-mail')->dateTimeFormat()) ? $version->created_at->format($fmt) : $version->created_at }}
                                </span>
                            </td>
                            <td class="fi-ta-cell px-3 py-4">
                                <span class="text-sm text-gray-500 dark:text-gray-400">
                                    {{ $version->createdBy?->name ?? '-' }}
                                </span>
                            </td>
                            <td class="fi-ta-cell px-3 py-4 sm:last-of-type:pe-6">
                                <div class="flex items-center justify-end gap-1">
                                    <x-filament::icon-button
                                        icon="heroicon-o-eye"
                                        size="sm"
                                        color="gray"
                                        :tooltip="__('fin-mail::fin-mail.template.actions.preview')"
                                        wire:click="previewVersion({{ $version->version }})"
                                    />
                                    <x-filament::icon-button
                                        icon="heroicon-o-arrow-uturn-left"
                                        size="sm"
                                        color="gray"
                                        :tooltip="__('fin-mail::fin-mail.template.versioning.restore')"
                                        wire:click="restoreVersion({{ $version->version }})"
                                        wire:confirm="{{ __('fin-mail::fin-mail.template.versioning.restore_confirm', ['version' => $version->version]) }}"
                                    />
                                </div>
                            </td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    </div>
@else
    <div class="fi-ta-empty-state px-6 py-12">
        <div class="fi-ta-empty-state-content mx-auto grid max-w-lg justify-items-center text-center">
            <div class="fi-ta-empty-state-icon-ctn mb-4 rounded-full bg-gray-100 p-3 dark:bg-gray-500/20">
                <x-filament::icon
                    icon="heroicon-o-clock"
                    class="fi-ta-empty-state-icon h-6 w-6 text-gray-500 dark:text-gray-400"
                />
            </div>
            <h4 class="fi-ta-empty-state-heading text-base font-semibold leading-6 text-gray-950 dark:text-white">
                {{ __('fin-mail::fin-mail.template.versioning.empty') }}
            </h4>
        </div>
    </div>
@endif
