<?php

declare(strict_types=1);

use FinityLabs\LinCodex\Http\Controllers\Api\ArticleController;
use FinityLabs\LinCodex\Http\Controllers\Api\ContextController;
use FinityLabs\LinCodex\Http\Controllers\Api\SearchController;
use FinityLabs\LinCodex\Http\Controllers\Api\TreeController;
use FinityLabs\LinCodex\Http\Controllers\MediaController;
use FinityLabs\LinCodex\Sources\Filesystem\FilePath;
use Illuminate\Support\Facades\Route;

Route::get(rtrim((string) config('lin-codex.routes.media', '/codex/media'), '/').'/{locale}/{path}', MediaController::class)
    ->where(['locale' => FilePath::LOCALE_PATTERN, 'path' => '.+'])
    ->middleware(config('lin-codex.routes.middleware', ['web']))
    ->name('lin-codex.media');

/*
 * The JSON API. It lives in this file on purpose: a routes/api.php would
 * suggest the "api" middleware group, while these endpoints run under the
 * configured group ("web" by default) so the session guard identifies the
 * viewer. The slug pattern is ".+" rather than ".*" so "articles/" never
 * reaches the controller.
 */
Route::prefix(rtrim((string) config('lin-codex.routes.api', '/codex/api'), '/'))
    ->middleware(config('lin-codex.routes.middleware', ['web']))
    ->name('lin-codex.api.')
    ->group(function (): void {
        Route::get('tree', TreeController::class)->name('tree');
        Route::get('articles/{slug}', ArticleController::class)->where('slug', '.+')->name('article');
        Route::get('search', SearchController::class)->name('search');
        Route::get('context', ContextController::class)->name('context');
    });
