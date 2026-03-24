<?php

declare(strict_types=1);

namespace FinityLabs\FinSentinel\Services;

class DataScrubber
{
    /**
     * Scrub sensitive values from request parameters.
     *
     * @param  array<string, mixed>  $data
     * @return array<string, mixed>
     */
    public function scrubParams(array $data): array
    {
        /** @var string[] $keys */
        $keys = config('fin-sentinel.scrub.params', []);

        return $this->scrub($data, $keys);
    }

    /**
     * Scrub sensitive values from HTTP headers.
     *
     * @param  array<string, mixed>  $headers
     * @return array<string, mixed>
     */
    public function scrubHeaders(array $headers): array
    {
        /** @var string[] $keys */
        $keys = config('fin-sentinel.scrub.headers', []);

        return $this->scrub($headers, $keys);
    }

    /**
     * Scrub sensitive values from environment variables.
     *
     * @param  array<string, mixed>  $env
     * @return array<string, mixed>
     */
    public function scrubEnv(array $env): array
    {
        /** @var string[] $keys */
        $keys = config('fin-sentinel.scrub.env', []);

        return $this->scrub($env, $keys);
    }

    /**
     * Scrub sensitive values from stack trace arguments.
     *
     * @param  array<string, mixed>  $args
     * @return array<string, mixed>
     */
    public function scrubTraceArgs(array $args): array
    {
        /** @var string[] $keys */
        $keys = config('fin-sentinel.scrub.trace_args', []);

        return $this->scrub($args, $keys);
    }

    /**
     * Recursively scrub matching keys from data, replacing values with [REDACTED].
     *
     * @param  array<string, mixed>  $data
     * @param  string[]  $keys
     * @return array<string, mixed>
     */
    private function scrub(array $data, array $keys): array
    {
        $normalizedKeys = array_map('strtolower', $keys);
        $result = [];

        foreach ($data as $key => $value) {
            if (in_array(strtolower((string) $key), $normalizedKeys, true)) {
                $result[$key] = '[REDACTED]';
            } elseif (is_array($value)) {
                $result[$key] = $this->scrub($value, $keys);
            } else {
                $result[$key] = $value;
            }
        }

        return $result;
    }
}
