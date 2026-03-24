<?php

declare(strict_types=1);

namespace FinityLabs\FinSentinel\Listeners;

use FinityLabs\FinSentinel\Events\SentinelDebug;
use FinityLabs\FinSentinel\FinSentinelServiceProvider;
use FinityLabs\FinSentinel\Mail\DebugMail;
use FinityLabs\FinSentinel\Services\DataScrubber;
use FinityLabs\FinSentinel\Services\DebugFormatter;
use FinityLabs\FinSentinel\Settings\DebugChannelSettings;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Mail;

class SentinelDebugListener
{
    public function __construct(private DebugChannelSettings $settings) {}

    public function handle(SentinelDebug $event): void
    {
        if (! $this->settings->debug_enabled) {
            return;
        }

        if (empty($this->settings->debug_recipients)) {
            return;
        }

        if ($this->settings->debug_throttle_enabled && $this->isThrottled($event)) {
            return;
        }

        FinSentinelServiceProvider::guardedHandle(function () use ($event) {
            $formatted = $this->scrubFormattedData(
                app(DebugFormatter::class)->format($event->data)
            );

            $debugMail = new DebugMail(
                formattedData: $formatted,
                callSite: ['file' => 'Event dispatch', 'line' => 0],
                requestContext: DebugMail::buildRequestContext(),
                environmentContext: DebugMail::buildEnvironmentContext(),
                customSubject: $event->subject,
            );

            Mail::to($this->settings->debug_recipients)->queue($debugMail);
        });
    }

    /**
     * Check if this debug event is within the throttle window.
     */
    private function isThrottled(SentinelDebug $event): bool
    {
        $key = $this->buildThrottleKey($event);

        if (Cache::has($key)) {
            return true;
        }

        Cache::put($key, true, now()->addMinutes($this->settings->debug_throttle_minutes));

        return false;
    }

    /**
     * Build a throttle cache key from the event data.
     */
    private function buildThrottleKey(SentinelDebug $event): string
    {
        $data = $event->data;

        $dataHash = match (true) {
            $data instanceof Model => $data::class . $data->getKey() . md5(json_encode($data->getAttributes())),
            $data instanceof Collection => 'collection:' . $data->count() . md5($data->take(5)->toJson()),
            is_array($data) => md5(json_encode($data, JSON_PARTIAL_OUTPUT_ON_ERROR)),
            default => md5((string) $data),
        };

        return 'fin-sentinel:debug-throttle:' . md5(($event->subject ?? '') . $dataHash);
    }

    /**
     * Scrub sensitive values from the formatted data array.
     *
     * @param  array<string, mixed>  $formatted
     * @return array<string, mixed>
     */
    private function scrubFormattedData(array $formatted): array
    {
        $scrubber = app(DataScrubber::class);
        $type = $formatted['type'] ?? '';

        return match ($type) {
            'model' => array_merge($formatted, [
                'attributes' => $scrubber->scrubParams($formatted['attributes'] ?? []),
            ]),
            'collection' => array_merge($formatted, [
                'items' => array_map(
                    fn (array $item): array => $this->scrubFormattedData($item),
                    $formatted['items'] ?? []
                ),
            ]),
            'query' => array_merge($formatted, [
                'bindings' => $scrubber->scrubParams(
                    array_combine(
                        array_map('strval', array_keys($formatted['bindings'] ?? [])),
                        array_values($formatted['bindings'] ?? [])
                    )
                ),
            ]),
            'array' => array_merge($formatted, [
                'data' => $scrubber->scrubParams($formatted['data'] ?? []),
            ]),
            default => $formatted,
        };
    }
}
