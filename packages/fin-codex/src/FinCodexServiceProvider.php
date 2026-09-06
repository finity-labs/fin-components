<?php

declare(strict_types=1);

namespace FinityLabs\FinCodex;

use FinityLabs\FinCodex\Help\DeclaredContexts;
use FinityLabs\FinCodex\Help\DeclaredContextsSource;
use FinityLabs\FinCodex\Panel\CurrentPage;
use FinityLabs\LinCodex\Contracts\ContentSource;
use Illuminate\Contracts\Container\Container;
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

    /**
     * CurrentPage is scoped like lin-codex's PageHelpResolver: one identity
     * per request, flushed by Octane between requests.
     *
     * The declared-help decorator rides on lin-codex's own ContentSource
     * binding through Container::extend(), so the core's lin-codex.source
     * switch keeps choosing the inner source and forgetInstance() still
     * yields a fresh, decorated instance (extenders live outside the
     * instance map). DeclaredContexts is a singleton whose registry scan is
     * lazy: the panel providers register after this one, so the scan has to
     * wait for the first read.
     */
    public function packageRegistered(): void
    {
        $this->app->scoped(CurrentPage::class);
        $this->app->singleton(DeclaredContexts::class);
        $this->app->extend(ContentSource::class, static fn (ContentSource $inner, Container $app): ContentSource => new DeclaredContextsSource($inner, $app->make(DeclaredContexts::class)));
    }
}
