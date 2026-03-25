<div class="space-y-6">
    {{-- Header bar --}}
    <div class="flex items-center justify-between rounded-lg bg-gray-50 px-4 py-3 dark:bg-gray-900">
        <div class="flex items-center gap-3">
            <x-filament::badge :color="$entry['level_color']" size="lg">
                {{ $entry['level'] }}
            </x-filament::badge>
            <span class="font-mono text-sm text-gray-500 dark:text-gray-400">
                {{ $entry['timestamp'] }}
            </span>
        </div>
        <span class="text-xs text-gray-400 dark:text-gray-500">
            {{ __('fin-sentinel::fin-sentinel.log_entry_line') }}: {{ $entry['start_line'] }}
        </span>
    </div>

    {{-- Message --}}
    <div class="rounded-lg border border-gray-200 dark:border-gray-700 overflow-hidden">
        <div class="bg-gray-100 px-5 py-2 dark:bg-gray-800">
            <span class="text-lg font-semibold uppercase tracking-wider text-gray-500 dark:text-gray-400">
                {{ __('fin-sentinel::fin-sentinel.log_column_message') }}
            </span>
        </div>
        <div class="p-4">
            <div class="font-mono text-sm leading-relaxed whitespace-pre-wrap wrap-break-word text-gray-800 dark:text-gray-200">{!! nl2br(e($entry['message'])) !!}</div>
        </div>
    </div>

    {{-- Stack trace --}}
    @if ($entry['has_stack_trace'])
        <div class="rounded-lg border border-gray-200 dark:border-gray-700 overflow-hidden">
            <div class="bg-gray-100 px-5 py-2 dark:bg-gray-800 flex items-center justify-between">
                <span class="text-lg font-semibold uppercase tracking-wider text-gray-500 dark:text-gray-400">
                    {{ __('fin-sentinel::fin-sentinel.error_section_trace') }}
                </span>
                @php
                    $lines = explode("\n", $entry['stack_trace']);
                    $frames = [];
                    $currentFrame = null;

                    foreach ($lines as $line) {
                        if (preg_match('/^#\d+\s/', $line)) {
                            if ($currentFrame !== null) {
                                $frames[] = $currentFrame;
                            }
                            $currentFrame = ['header' => $line, 'isVendor' => str_contains($line, '/vendor/')];
                        } elseif ($currentFrame !== null) {
                            $currentFrame['header'] .= "\n" . $line;
                        } else {
                            $frames[] = ['header' => $line, 'isVendor' => false, 'context' => true];
                        }
                    }

                    if ($currentFrame !== null) {
                        $frames[] = $currentFrame;
                    }

                    $frameCount = count(array_filter($frames, fn ($f) => !isset($f['context'])));
                @endphp
                <span class="text-xs text-gray-400 dark:text-gray-500">
                    {{ $frameCount }} {{ trans_choice('fin-sentinel::fin-sentinel.log_trace_frames', $frameCount) }}
                </span>
            </div>
            <div class="max-h-128 overflow-y-auto divide-y divide-gray-100 dark:divide-gray-800">
                @foreach ($frames as $frame)
                    @if (isset($frame['context']))
                        <div class="px-5 py-2 bg-amber-50 dark:bg-amber-950/30">
                            <pre class="font-mono text-lg text-amber-800 dark:text-amber-200 whitespace-pre-wrap break-all">{{ $frame['header'] }}</pre>
                        </div>
                    @else
                        <div
                            x-data="{ expanded: {{ $frame['isVendor'] ? 'false' : 'true' }} }"
                            class="{{ $frame['isVendor'] ? 'opacity-50 hover:opacity-100 transition-opacity' : '' }}"
                        >
                            <button
                                type="button"
                                @click="expanded = !expanded"
                                class="w-full text-left px-4 py-2 hover:bg-gray-50 dark:hover:bg-gray-900/50 transition-colors"
                            >
                                <div class="flex items-start gap-2">
                                    <svg
                                        class="mt-0.5 h-3.5 w-3.5 text-gray-400 transition-transform duration-150 shrink-0"
                                        :class="{ 'rotate-90': expanded }"
                                        xmlns="http://www.w3.org/2000/svg"
                                        viewBox="0 0 20 20"
                                        fill="currentColor"
                                    >
                                        <path fill-rule="evenodd" d="M7.21 14.77a.75.75 0 01.02-1.06L11.168 10 7.23 6.29a.75.75 0 111.04-1.08l4.5 4.25a.75.75 0 010 1.08l-4.5 4.25a.75.75 0 01-1.06-.02z" clip-rule="evenodd" />
                                    </svg>
                                    @php
                                        $headerText = $frame['header'];
                                        $highlighted = preg_replace(
                                            '/([\/\w\-\.]+\.php)(\((\d+)\))?/',
                                            '<span class="text-primary-600 dark:text-primary-400">$1</span><span class="text-amber-600 dark:text-amber-400">$2</span>',
                                            e($headerText)
                                        );
                                    @endphp
                                    <pre class="font-mono text-xs leading-relaxed whitespace-pre-wrap break-all flex-1">{!! $highlighted !!}</pre>
                                </div>
                            </button>
                            <div
                                x-show="expanded"
                                x-collapse
                                class="px-4 pb-2.5"
                            >
                                <pre class="font-mono text-xs text-gray-500 dark:text-gray-500 whitespace-pre-wrap break-all pl-5.5 leading-relaxed">{{ $frame['header'] }}</pre>
                            </div>
                        </div>
                    @endif
                @endforeach
            </div>
        </div>
    @endif

    {{-- Actions --}}
    <div class="flex gap-2 pt-1">
        @if ($entry['has_stack_trace'])
            <div x-data="{ copied: false }">
                <x-filament::button
                    color="gray"
                    size="sm"
                    icon="heroicon-o-clipboard"
                    x-on:click="
                        navigator.clipboard.writeText(@js($entry['stack_trace']));
                        copied = true;
                        setTimeout(() => copied = false, 2000);
                    "
                >
                    <span x-show="!copied">{{ __('fin-sentinel::fin-sentinel.log_copy_trace') }}</span>
                    <span x-show="copied" x-cloak>{{ __('fin-sentinel::fin-sentinel.log_copied') }}</span>
                </x-filament::button>
            </div>
        @endif

        <div x-data="{ copied: false }">
            @php
                $fullEntry = $entry['message'];
                if ($entry['has_stack_trace']) {
                    $fullEntry .= "\n\n" . $entry['stack_trace'];
                }
            @endphp
            <x-filament::button
                color="gray"
                size="sm"
                icon="heroicon-o-clipboard-document"
                x-on:click="
                    navigator.clipboard.writeText(@js($fullEntry));
                    copied = true;
                    setTimeout(() => copied = false, 2000);
                "
            >
                <span x-show="!copied">{{ __('fin-sentinel::fin-sentinel.log_copy_entry') }}</span>
                <span x-show="copied" x-cloak>{{ __('fin-sentinel::fin-sentinel.log_copied') }}</span>
            </x-filament::button>
        </div>
    </div>
</div>
