<?php

declare(strict_types=1);

use Spatie\LaravelSettings\Migrations\SettingsMigration;

return new class extends SettingsMigration
{
    public function up(): void
    {
        $this->migrator->add('fin-sentinel.error_recipients', []);
        $this->migrator->add('fin-sentinel.error_enabled', true);
        $this->migrator->add('fin-sentinel.error_throttle_minutes', 15);
        $this->migrator->add('fin-sentinel.ignored_exceptions', [
            \Symfony\Component\HttpKernel\Exception\NotFoundHttpException::class,
            \Illuminate\Validation\ValidationException::class,
            \Illuminate\Auth\AuthenticationException::class,
        ]);
    }
};
