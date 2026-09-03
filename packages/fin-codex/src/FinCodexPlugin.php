<?php

declare(strict_types=1);

namespace FinityLabs\FinCodex;

use Filament\Contracts\Plugin;
use Filament\Panel;

class FinCodexPlugin implements Plugin
{
    public static function make(): static
    {
        return app(static::class);
    }

    public static function get(): static
    {
        /** @var static */
        return filament(app(static::class)->getId());
    }

    public function getId(): string
    {
        return 'fin-codex';
    }

    public function register(Panel $panel): void {}

    public function boot(Panel $panel): void {}
}
