<?php

declare(strict_types=1);

use FinityLabs\FinAvatar\Http\Controllers\AvatarController;
use Illuminate\Support\Facades\Route;

Route::get('/fin-avatar/render', AvatarController::class)
    ->name('fin-avatar.render')
    ->middleware('web'); // 'web' is safe here, add throttling if needed
