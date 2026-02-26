<?php

declare(strict_types=1);

namespace FinityLabs\FinMail\Settings;

use Spatie\LaravelSettings\Settings;

class LoggingSettings extends Settings
{
    public bool $enabled;

    public bool $store_rendered_body;

    public ?int $retention_days;

    public static function group(): string
    {
        return 'fin-mail-logging';
    }

    /**
     * @return array<string, mixed>
     */
    public static function defaults(): array
    {
        return [
            'enabled' => true,
            'store_rendered_body' => true,
            'retention_days' => 90,
        ];
    }
}
