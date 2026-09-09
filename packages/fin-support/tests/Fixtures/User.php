<?php

declare(strict_types=1);

namespace FinityLabs\FinSupport\Tests\Fixtures;

use Filament\Models\Contracts\FilamentUser;
use Filament\Panel;
use Illuminate\Foundation\Auth\User as Authenticatable;

final class User extends Authenticatable implements FilamentUser
{
    protected $table = 'users';

    protected $guarded = [];

    /** @var array<string, mixed> */
    protected $attributes = ['password' => null, 'remember_token' => null];

    public function canAccessPanel(Panel $panel): bool
    {
        return true;
    }
}
