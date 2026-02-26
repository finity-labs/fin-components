<?php

declare(strict_types=1);

namespace FinityLabs\FinMail\Settings;

use Spatie\LaravelSettings\Settings;

class AttachmentSettings extends Settings
{
    public int $max_size_mb;

    public array $allowed_types;

    public static function group(): string
    {
        return 'fin-mail-attachments';
    }

    /**
     * @return array<string, mixed>
     */
    public static function defaults(): array
    {
        return [
            'max_size_mb' => 10,
            'allowed_types' => ['pdf', 'doc', 'docx', 'xls', 'xlsx', 'csv', 'png', 'jpg', 'jpeg', 'zip'],
        ];
    }
}
