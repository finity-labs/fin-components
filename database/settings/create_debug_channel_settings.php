<?php

declare(strict_types=1);

use Spatie\LaravelSettings\Migrations\SettingsMigration;

return new class extends SettingsMigration
{
    public function up(): void
    {
        $this->migrator->add('fin-sentinel.debug_recipients', []);
        $this->migrator->add('fin-sentinel.debug_enabled', true);
        $this->migrator->add('fin-sentinel.debug_throttle_minutes', 15);
    }
};
