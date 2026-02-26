<?php

declare(strict_types=1);

namespace FinityLabs\FinMail;

use Closure;
use Filament\Contracts\Plugin;
use Filament\Panel;
use FinityLabs\FinMail\Pages\ManageFinMailSettings;
use FinityLabs\FinMail\Resources\EmailTemplateResource\EmailTemplateResource;
use FinityLabs\FinMail\Resources\EmailThemeResource\EmailThemeResource;
use FinityLabs\FinMail\Resources\SentEmailResource\SentEmailResource;

class FinMailPlugin implements Plugin
{
    protected bool|Closure $navigationEnabled = true;

    protected bool|Closure $sentEmailsEnabled = true;

    protected bool|Closure $themesEnabled = true;

    public static function make(): static
    {
        return app(static::class);
    }

    public static function get(): static
    {
        return filament(app(static::class)->getId());
    }

    public function getId(): string
    {
        return 'fin-mail';
    }

    public function register(Panel $panel): void
    {
        $resources = [
            EmailTemplateResource::class,
        ];

        if ($this->evaluate($this->themesEnabled)) {
            $resources[] = EmailThemeResource::class;
        }

        if ($this->evaluate($this->sentEmailsEnabled)) {
            $resources[] = SentEmailResource::class;
        }

        $panel
            ->resources($resources)
            ->pages([
                ManageFinMailSettings::class,
            ]);
    }

    public function boot(Panel $panel): void
    {
        //
    }

    public function enableNavigation(bool|Closure $enabled = true): static
    {
        $this->navigationEnabled = $enabled;

        return $this;
    }

    public function enableSentEmails(bool|Closure $enabled = true): static
    {
        $this->sentEmailsEnabled = $enabled;

        return $this;
    }

    public function enableThemes(bool|Closure $enabled = true): static
    {
        $this->themesEnabled = $enabled;

        return $this;
    }

    public function isNavigationEnabled(): bool
    {
        return $this->evaluate($this->navigationEnabled);
    }

    protected function evaluate(bool|Closure $value): bool
    {
        return is_callable($value) ? $value() : $value;
    }
}
