<?php

declare(strict_types=1);

namespace FinityLabs\LinCodex\Tests;

use FinityLabs\LinCodex\Contracts\ContentSource;
use FinityLabs\LinCodex\LinCodexServiceProvider;
use FinityLabs\LinCodex\Rendering\ArticleRenderer;
use FinityLabs\LinCodex\Rendering\Html\HtmlPipeline;
use FinityLabs\LinCodex\Rendering\Markdown\MarkdownPipeline;
use FinityLabs\LinCodex\Sources\CompositeSource;
use FinityLabs\LinCodex\Sources\DatabaseSource;
use FinityLabs\LinCodex\Sources\FilesystemSource;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Orchestra\Testbench\TestCase as Orchestra;
use Spatie\LaravelSettings\LaravelSettingsServiceProvider;
use Symfony\Component\HtmlSanitizer\HtmlSanitizerInterface;

/**
 * Package test harness.
 *
 * The database follows DB_CONNECTION: in-memory SQLite by default, a real
 * MySQL, MariaDB or PostgreSQL server when the CI service rows or a developer
 * set the six DB_* variables. The package migrations run through
 * include()->up(), never the framework migration loader, so custom table names and the
 * driver branches are exercised exactly as a host app would. Tests are never
 * wrapped in a transaction: InnoDB full-text indexes only see committed rows,
 * so a transaction trait would make every MATCH ... AGAINST test silently
 * fall through to the LIKE path. The schema is dropped before it is created
 * and again when the application is destroyed instead.
 */
class TestCase extends Orchestra
{
    /**
     * Package schema migrations in dependency order (articles first).
     * Reused by tests that need to run down() or re-run up().
     *
     * @var list<string>
     */
    public const PACKAGE_MIGRATIONS = [
        'create_codex_articles_table',
        'create_codex_article_translations_table',
        'create_codex_article_contexts_table',
        'create_codex_article_revisions_table',
        'create_codex_media_table',
    ];

    protected function getPackageProviders($app): array
    {
        return [
            LaravelSettingsServiceProvider::class,
            LinCodexServiceProvider::class,
        ];
    }

    protected function defineEnvironment($app): void
    {
        $app['config']->set('database.default', 'testing');
        $app['config']->set('database.connections.testing', self::databaseConnectionConfig());
    }

    /**
     * The testing connection for env('DB_CONNECTION', 'sqlite'): in-memory
     * SQLite by default, a MySQL, MariaDB or PostgreSQL server when the CI
     * service rows or a developer set DB_CONNECTION and the DB_* variables.
     * phpunit.xml's <env> defaults never overwrite a variable that is already
     * in the shell, so the exported variables win.
     *
     * @return array<string, mixed>
     */
    public static function databaseConnectionConfig(): array
    {
        $driver = (string) env('DB_CONNECTION', 'sqlite');

        return match ($driver) {
            'mysql', 'mariadb' => [
                'driver' => $driver,
                'host' => (string) env('DB_HOST', '127.0.0.1'),
                'port' => (string) env('DB_PORT', '3306'),
                'database' => (string) env('DB_DATABASE', 'lin_codex'),
                'username' => (string) env('DB_USERNAME', 'root'),
                'password' => (string) env('DB_PASSWORD', ''),
                'charset' => 'utf8mb4',
                'collation' => 'utf8mb4_unicode_ci',
                'prefix' => '',
                'prefix_indexes' => true,
                'strict' => true,
                'engine' => null,
            ],
            'pgsql' => [
                'driver' => 'pgsql',
                'host' => (string) env('DB_HOST', '127.0.0.1'),
                'port' => (string) env('DB_PORT', '5432'),
                'database' => (string) env('DB_DATABASE', 'lin_codex'),
                'username' => (string) env('DB_USERNAME', 'codex'),
                'password' => (string) env('DB_PASSWORD', 'password'),
                'charset' => 'utf8',
                'prefix' => '',
                'prefix_indexes' => true,
                'search_path' => 'public',
                'sslmode' => 'prefer',
            ],
            default => [
                'driver' => 'sqlite',
                'database' => ':memory:',
                'prefix' => '',
                'foreign_key_constraints' => true,
            ],
        };
    }

    /**
     * The driver name of the testing connection: 'sqlite', 'mysql', 'mariadb'
     * or 'pgsql'. Meant for ->skip() closures on engine-specific tests.
     */
    public function databaseDriver(): string
    {
        return DB::connection()->getDriverName();
    }

    /**
     * Runs after the providers boot: any schema a previous run left behind
     * is dropped, then the host tables the package depends on come first,
     * then the package schema in dependency order, then the settings seed.
     */
    protected function defineDatabaseMigrations(): void
    {
        $this->dropPackageSchema();

        $this->createUsersTable((string) config('lin-codex.users_table', 'users'));
        $this->createSettingsTable();

        foreach (self::PACKAGE_MIGRATIONS as $file) {
            $this->migration($file)->up();
        }

        (include dirname(__DIR__).'/database/settings/create_codex_settings.php')->up();
    }

    protected function destroyDatabaseMigrations(): void
    {
        $this->dropPackageSchema();
    }

    /**
     * Drop everything defineDatabaseMigrations() creates, dependents first.
     * On in-memory SQLite this is a no-op in cost; on a persistent server it
     * is what lets every test start from an empty schema without a
     * transaction wrapper (InnoDB full-text indexes only see committed rows,
     * so a refresh or transaction trait would hide every row from
     * MATCH ... AGAINST). Runs before create as well as after destroy, so a
     * crashed run leaves nothing behind. CustomTableNamesTestCase drops its
     * kb_* names because down() and this method read the overridden config.
     */
    private function dropPackageSchema(): void
    {
        Schema::disableForeignKeyConstraints();

        foreach (array_reverse(self::PACKAGE_MIGRATIONS) as $file) {
            $this->migration($file)->down();
        }

        Schema::dropIfExists('settings');
        Schema::dropIfExists((string) config('lin-codex.users_table', 'users'));

        Schema::enableForeignKeyConstraints();
    }

    /**
     * Load one of the package's schema migrations by file name (without .php).
     */
    public function migration(string $file): Migration
    {
        return include dirname(__DIR__).'/database/migrations/'.$file.'.php';
    }

    /**
     * A renderer whose pipelines have not memoized any config yet. Use after
     * config()->set(): the singletons capture app.url, the limits and the
     * help-center prefix when first resolved.
     */
    protected function freshRenderer(): ArticleRenderer
    {
        $this->app->forgetInstance(MarkdownPipeline::class);
        $this->app->forgetInstance(HtmlPipeline::class);
        $this->app->forgetInstance(HtmlSanitizerInterface::class);
        $this->app->forgetInstance(ArticleRenderer::class);

        return $this->app->make(ArticleRenderer::class);
    }

    /**
     * Drop the resolved source singletons so the next make() reads the
     * current config (lin-codex.source, the docs paths) again.
     */
    protected function forgetSources(): void
    {
        $this->app->forgetInstance(FilesystemSource::class);
        $this->app->forgetInstance(DatabaseSource::class);
        $this->app->forgetInstance(CompositeSource::class);
        $this->app->forgetInstance(ContentSource::class);
    }

    /**
     * The content source the provider binds for the current config.
     */
    protected function freshSource(): ContentSource
    {
        $this->forgetSources();

        return $this->app->make(ContentSource::class);
    }

    /** Absolute path of a docs fixture tree under tests/Fixtures ('docs' or 'docs-override'). */
    public function fixtureDocsPath(string $name = 'docs'): string
    {
        return __DIR__.'/Fixtures/'.$name;
    }

    protected function createUsersTable(string $table): void
    {
        Schema::create($table, function (Blueprint $table): void {
            $table->id();
            $table->string('name');
            $table->string('email')->unique();
            $table->timestamps();
        });
    }

    protected function createSettingsTable(): void
    {
        Schema::create('settings', function (Blueprint $table): void {
            $table->id();
            $table->string('group');
            $table->string('name');
            $table->boolean('locked')->default(false);
            $table->json('payload');
            $table->timestamps();

            $table->unique(['group', 'name']);
        });
    }
}
