<?php

declare(strict_types=1);

use Spatie\LaravelSettings\Migrations\SettingsMigration;

return new class extends SettingsMigration
{
    public function up(): void
    {
        $this->migrator->add('fin-sentinel.error_throttle_exceptions', true);
        $this->migrator->add('fin-sentinel.error_throttle_log_messages', true);
    }
};
