<?php

declare(strict_types=1);

namespace FinityLabs\LinCodex\Tests;

use FinityLabs\LinCodex\LinCodexServiceProvider;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Orchestra\Testbench\TestCase as Orchestra;
use Spatie\LaravelSettings\LaravelSettingsServiceProvider;

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
        $app['config']->set('database.connections.testing', [
            'driver' => 'sqlite',
            'database' => ':memory:',
            'prefix' => '',
            'foreign_key_constraints' => true,
        ]);
    }

    /**
     * Runs after the providers boot: the host tables the package depends on
     * come first, then the package schema in dependency order, then the
     * settings seed.
     */
    protected function defineDatabaseMigrations(): void
    {
        $this->createUsersTable((string) config('lin-codex.users_table', 'users'));
        $this->createSettingsTable();

        foreach (self::PACKAGE_MIGRATIONS as $file) {
            $this->migration($file)->up();
        }

        (include dirname(__DIR__).'/database/settings/create_codex_settings.php')->up();
    }

    /**
     * Load one of the package's schema migrations by file name (without .php).
     */
    public function migration(string $file): Migration
    {
        return include dirname(__DIR__).'/database/migrations/'.$file.'.php';
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
