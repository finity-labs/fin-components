<?php

declare(strict_types=1);

namespace FinityLabs\FinSentinel\Settings;

use Spatie\LaravelSettings\Settings;

class DebugChannelSettings extends Settings
{
    public array $debug_recipients = [];

    public bool $debug_enabled = true;

    public int $debug_throttle_minutes = 15;

    public static function group(): string
    {
        return 'fin-sentinel';
    }
}
