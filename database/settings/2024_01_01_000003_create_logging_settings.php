<?php

declare(strict_types=1);

use Spatie\LaravelSettings\Migrations\SettingsMigration;

return new class extends SettingsMigration
{
    public function up(): void
    {
        $this->migrator->add('fin-mail-logging.enabled', true);
        $this->migrator->add('fin-mail-logging.store_rendered_body', true);
        $this->migrator->add('fin-mail-logging.retention_days', 90);
    }
};
