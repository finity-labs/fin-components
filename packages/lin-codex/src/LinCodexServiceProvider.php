<?php

declare(strict_types=1);

namespace FinityLabs\LinCodex;

use FinityLabs\LinCodex\Contracts\ContentSource;
use FinityLabs\LinCodex\Rendering\ArticleRenderer;
use FinityLabs\LinCodex\Rendering\Html\HtmlPipeline;
use FinityLabs\LinCodex\Rendering\Html\SanitizerFactory;
use FinityLabs\LinCodex\Rendering\Markdown\MarkdownPipeline;
use FinityLabs\LinCodex\Sources\CompositeSource;
use FinityLabs\LinCodex\Sources\DatabaseSource;
use FinityLabs\LinCodex\Sources\FilesystemSource;
use FinityLabs\LinCodex\View\PageHelpResolver;
use Illuminate\Contracts\Container\Container;
use InvalidArgumentException;
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
            ->hasRoute('web')
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

        $this->app->singleton(FilesystemSource::class);
        $this->app->singleton(DatabaseSource::class);
        $this->app->singleton(CompositeSource::class);

        /*
         * The active source is chosen when the contract is first resolved,
         * not at boot, so a config()->set() followed by forgetInstance()
         * takes effect in tests. Nothing here touches the schema: a
         * files-only install that never ran the migrations sets
         * lin-codex.source to "filesystem" (see the config comment).
         */
        $this->app->singleton(ContentSource::class, static function (Container $app): ContentSource {
            $source = (string) config('lin-codex.source', 'composite');

            $class = match ($source) {
                'filesystem' => FilesystemSource::class,
                'database' => DatabaseSource::class,
                'composite' => CompositeSource::class,
                default => $source,
            };

            if (! is_a($class, ContentSource::class, true)) {
                throw new InvalidArgumentException(sprintf('lin-codex.source "%s" is not a ContentSource implementation.', $source));
            }

            /** @var ContentSource $instance */
            $instance = $app->make($class);

            return $instance;
        });

        $this->app->scoped(PageHelpResolver::class);
    }

    /**
     * The React and the Vue help drawer stubs, published into
     * resources/js/codex under the lin-codex-react and lin-codex-vue tags.
     * The two sets are alternatives and share codex.ts and types.ts, so
     * publishing both leaves both component pairs next to one client.
     * Registered only in console because publishes() is a console concern;
     * package-tools has no directory-publish helper, so this is the same
     * primitive it uses for its own views and migrations.
     */
    public function packageBooted(): void
    {
        if (! $this->app->runningInConsole()) {
            return;
        }

        $stubs = $this->package->basePath('/../resources/stubs');

        $this->publishes([$stubs.'/react' => resource_path('js/codex')], 'lin-codex-react');
        $this->publishes([$stubs.'/vue' => resource_path('js/codex')], 'lin-codex-vue');
    }
}
