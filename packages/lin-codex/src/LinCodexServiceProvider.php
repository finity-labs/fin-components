<?php

declare(strict_types=1);

namespace FinityLabs\LinCodex;

use FinityLabs\LinCodex\Rendering\Markdown\MarkdownPipeline;
use Spatie\LaravelPackageTools\Package;
use Spatie\LaravelPackageTools\PackageServiceProvider;

class LinCodexServiceProvider extends PackageServiceProvider
{
    public static string $name = 'lin-codex';

    public function configurePackage(Package $package): void
    {
        $package
            ->name(static::$name)
            ->hasConfigFile()
            ->hasTranslations()
            ->hasMigrations([
                'create_codex_articles_table',
                'create_codex_article_translations_table',
                'create_codex_article_contexts_table',
                'create_codex_article_revisions_table',
                'create_codex_media_table',
                '../settings/create_codex_settings',
            ]);
    }

    public function packageRegistered(): void
    {
        $this->app->singleton(MarkdownPipeline::class);
    }
}
