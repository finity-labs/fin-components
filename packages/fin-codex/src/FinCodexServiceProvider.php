<?php

declare(strict_types=1);

namespace FinityLabs\FinCodex;

use FinityLabs\FinCodex\Panel\CurrentPage;
use Spatie\LaravelPackageTools\Package;
use Spatie\LaravelPackageTools\PackageServiceProvider;

class FinCodexServiceProvider extends PackageServiceProvider
{
    public static string $name = 'fin-codex';

    public function configurePackage(Package $package): void
    {
        $package
            ->name(static::$name)
            ->hasTranslations()
            ->hasViews();
    }

    /** Scoped like lin-codex's PageHelpResolver: one identity per request, flushed by Octane between requests. */
    public function packageRegistered(): void
    {
        $this->app->scoped(CurrentPage::class);
    }
}
