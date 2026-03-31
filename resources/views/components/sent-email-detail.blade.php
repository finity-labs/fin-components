<div class="space-y-4">
    {{-- Metadata --}}
    <div class="grid grid-cols-2 gap-4 text-sm">
        <div>
            <span class="font-medium text-gray-500 dark:text-gray-400">{{ __('fin-mail::fin-mail.sent.preview.from') }}</span>
            <span class="ml-2">{{ $email->sender }}</span>
        </div>
        <div>
            <span class="font-medium text-gray-500 dark:text-gray-400">{{ __('fin-mail::fin-mail.sent.preview.sent') }}</span>
            <span class="ml-2">{{ $email->sent_at?->isoFormat('llll') ?? __('fin-mail::fin-mail.sent.preview.sent_not_yet') }}</span>
        </div>
        <div>
            <span class="font-medium text-gray-500 dark:text-gray-400">{{ __('fin-mail::fin-mail.sent.preview.to') }}</span>
            <span class="ml-2">{{ implode(', ', $email->to ?? []) }}</span>
        </div>
        @if($email->cc)
            <div>
                <span class="font-medium text-gray-500 dark:text-gray-400">{{ __('fin-mail::fin-mail.sent.preview.cc') }}</span>
                <span class="ml-2">{{ implode(', ', $email->cc) }}</span>
            </div>
        @endif
        <div>
            <span class="font-medium text-gray-500 dark:text-gray-400">{{ __('fin-mail::fin-mail.sent.preview.status') }}</span>
            <span class="ml-2 inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium
                {{ match($email->status->value) {
                    1 => 'bg-gray-100 text-gray-800',
                    2 => 'bg-yellow-100 text-yellow-800',
                    3 => 'bg-green-100 text-green-800',
                    4 => 'bg-red-100 text-red-800',
                    default => 'bg-gray-100 text-gray-800',
                } }}">
                {{ $email->status->getLabel() }}
            </span>
        </div>
        @if($email->template)
            <div>
                <span class="font-medium text-gray-500 dark:text-gray-400">{{ __('fin-mail::fin-mail.sent.preview.template') }}</span>
                <span class="ml-2">{{ $email->template->name }}</span>
            </div>
        @endif
    </div>

    {{-- Rendered Body --}}
    @if($email->rendered_body)
        <div class="border-t pt-4 mt-4">
            <div class="bg-gray-100 dark:bg-gray-800 p-4 rounded-lg">
                <div class="bg-white dark:bg-gray-900 mx-auto shadow-lg rounded-lg overflow-hidden" style="max-width: 600px;">
                    <div class="p-6">
                        <div class="prose dark:prose-invert max-w-none">
                            {!! $email->rendered_body !!}
                        </div>
                    </div>
                </div>
            </div>
        </div>
    @else
        <div class="border-t pt-4 mt-4">
            <p class="text-gray-500 text-sm italic">{!! __('fin-mail::fin-mail.sent.preview.no_body') !!}</p>
        </div>
    @endif

    {{-- Error Info --}}
    @if($email->status === \FinityLabs\FinMail\Enums\EmailStatus::Failed && ($email->metadata['error'] ?? null))
        <div class="bg-red-50 dark:bg-red-900/20 border border-red-200 dark:border-red-800 rounded-lg p-4 mt-4">
            <h4 class="text-sm font-medium text-red-800 dark:text-red-200">{{ __('fin-mail::fin-mail.sent.preview.error') }}</h4>
            <p class="text-sm text-red-700 dark:text-red-300 mt-1">{{ $email->metadata['error'] }}</p>
        </div>
    @endif
</div>
