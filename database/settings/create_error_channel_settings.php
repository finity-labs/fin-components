<?php

declare(strict_types=1);

use Illuminate\Auth\AuthenticationException;
use Illuminate\Validation\ValidationException;
use Spatie\LaravelSettings\Migrations\SettingsMigration;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

return new class extends SettingsMigration
{
    public function up(): void
    {
        $this->migrator->add('fin-sentinel.error_recipients', []);
        $this->migrator->add('fin-sentinel.error_enabled', true);
        $this->migrator->add('fin-sentinel.error_throttle_minutes', 15);
        $this->migrator->add('fin-sentinel.ignored_exceptions', [
            NotFoundHttpException::class,
            ValidationException::class,
            AuthenticationException::class,
        ]);
    }
};
