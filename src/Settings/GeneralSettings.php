<?php

declare(strict_types=1);

namespace FinityLabs\FinMail\Settings;

use Spatie\LaravelSettings\Settings;

class GeneralSettings extends Settings
{
    public string $default_from_address;

    public string $default_from_name;

    public array $additional_senders;

    public string $default_locale;

    public array $languages;

    public array $categories;

    public static function group(): string
    {
        return 'fin-mail';
    }

    /**
     * @return array<string, mixed>
     */
    public static function defaults(): array
    {
        return [
            'default_from_address' => config('mail.from.address', 'hello@example.com'),
            'default_from_name' => config('mail.from.name', 'Example'),
            'additional_senders' => [],
            'default_locale' => config('app.locale', 'en'),
            'languages' => [
                ['code' => 'en', 'display' => 'English', 'flag-icon' => 'gb'],
            ],
            'categories' => [
                'transactional' => 'Transactional',
                'marketing' => 'Marketing',
                'system' => 'System',
                'notification' => 'Notification',
            ],
        ];
    }
}
