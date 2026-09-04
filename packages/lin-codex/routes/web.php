<?php

declare(strict_types=1);

use FinityLabs\LinCodex\Http\Controllers\MediaController;
use FinityLabs\LinCodex\Sources\Filesystem\FilePath;
use Illuminate\Support\Facades\Route;

Route::get(rtrim((string) config('lin-codex.routes.media', '/codex/media'), '/').'/{locale}/{path}', MediaController::class)
    ->where(['locale' => FilePath::LOCALE_PATTERN, 'path' => '.+'])
    ->middleware(config('lin-codex.routes.middleware', ['web']))
    ->name('lin-codex.media');
