<?php

declare(strict_types=1);

namespace FinityLabs\FinMail\Settings;

use Spatie\LaravelSettings\Settings;

class BrandingSettings extends Settings
{
    public ?string $logo;

    public int $logo_width;

    public int $logo_height;

    public int $content_width;

    public string $primary_color;

    public array $footer_links;

    public ?string $customer_service_email;

    public ?string $customer_service_phone;

    public static function group(): string
    {
        return 'fin-mail-branding';
    }

    /**
     * @return array<string, mixed>
     */
    public static function defaults(): array
    {
        return [
            'logo' => null,
            'logo_width' => 200,
            'logo_height' => 50,
            'content_width' => 600,
            'primary_color' => '#4F46E5',
            'footer_links' => [],
            'customer_service_email' => null,
            'customer_service_phone' => null,
        ];
    }
}
