<?php

declare(strict_types=1);

namespace FinityLabs\LinCodex;

use FinityLabs\LinCodex\Rendering\ArticleRenderer;
use FinityLabs\LinCodex\Rendering\Html\HtmlPipeline;
use FinityLabs\LinCodex\Rendering\Html\SanitizerFactory;
use FinityLabs\LinCodex\Rendering\Markdown\MarkdownPipeline;
use Spatie\LaravelPackageTools\Package;
use Spatie\LaravelPackageTools\PackageServiceProvider;
use Symfony\Component\HtmlSanitizer\HtmlSanitizer;
use Symfony\Component\HtmlSanitizer\HtmlSanitizerInterface;

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
        $this->app->singleton(HtmlSanitizerInterface::class, static fn (): HtmlSanitizer => SanitizerFactory::make());
        $this->app->singleton(HtmlPipeline::class);
        $this->app->singleton(ArticleRenderer::class);
    }
}
