<div class="space-y-3">
    @forelse($versions as $version)
        <div class="flex items-center justify-between p-3 bg-gray-50 dark:bg-gray-800 rounded-lg">
            <div class="flex-1">
                <div class="flex items-center gap-2">
                    <span class="text-sm font-medium">Version {{ $version->version }}</span>
                    <span class="text-xs text-gray-500">
                        {{ $version->created_at->format('M d, Y H:i') }}
                    </span>
                </div>
                <div class="text-xs text-gray-500 mt-1">
                    Subject: <span class="text-gray-700 dark:text-gray-300">{{ \Illuminate\Support\Str::limit($version->subject, 60) }}</span>
                </div>
                @if($version->createdBy)
                    <div class="text-xs text-gray-400 mt-0.5">
                        by {{ $version->createdBy->name }}
                    </div>
                @endif
            </div>

            <div class="flex items-center gap-2">
                <button
                    type="button"
                    class="text-xs px-2 py-1 rounded bg-gray-200 dark:bg-gray-700 hover:bg-gray-300 dark:hover:bg-gray-600 transition"
                    wire:click="$dispatch('restore-version', { version: {{ $version->version }} })"
                    wire:confirm="Are you sure you want to restore version {{ $version->version }}? The current content will be saved as a new version first."
                >
                    Restore
                </button>
            </div>
        </div>
    @empty
        <p class="text-gray-500 text-sm text-center py-4">No version history available.</p>
    @endforelse
</div>
