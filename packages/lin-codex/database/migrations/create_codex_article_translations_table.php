<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        $schema = Schema::connection($this->getConnection());
        $articles = config('lin-codex.table_names.articles') ?? 'codex_articles';
        $translations = config('lin-codex.table_names.article_translations') ?? 'codex_article_translations';
        $driver = $schema->getConnection()->getDriverName();

        $schema->create($translations, function (Blueprint $table) use ($articles, $driver): void {
            $table->id();
            $table->foreignId('article_id')->constrained($articles)->cascadeOnDelete();
            $table->string('locale', 10);
            $table->string('title');
            $table->text('excerpt')->nullable();
            $table->longText('body');
            $table->longText('search_text')->nullable();
            $table->timestamps();

            $table->unique(['article_id', 'locale']);

            // The base grammar throws on drivers without full-text support (SQLite), so the
            // index must be driver-branched. PostgreSQL uses the 'simple' configuration
            // because one index serves every locale; per-language indexes are a v2 item.
            if (in_array($driver, ['mysql', 'mariadb'], true)) {
                $table->fullText('search_text');
            }

            if ($driver === 'pgsql') {
                $table->fullText('search_text')->language('simple');
            }
        });
    }

    public function down(): void
    {
        Schema::connection($this->getConnection())
            ->dropIfExists(config('lin-codex.table_names.article_translations') ?? 'codex_article_translations');
    }
};
