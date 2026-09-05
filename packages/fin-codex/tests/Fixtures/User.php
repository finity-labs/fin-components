<?php

declare(strict_types=1);

namespace FinityLabs\FinCodex\Tests\Fixtures;

use Filament\Models\Contracts\FilamentUser;
use Filament\Panel;
use Illuminate\Foundation\Auth\User as Authenticatable;

/**
 * Panel-capable user for the harness. Filament's Authenticate middleware
 * answers 403 for a user that is not a FilamentUser outside app.env=local,
 * so the fixture implements the contract; no $fillable guard so ::create()
 * works with plain arrays.
 */
final class User extends Authenticatable implements FilamentUser
{
    protected $table = 'users';

    protected $guarded = [];

    public function canAccessPanel(Panel $panel): bool
    {
        return true;
    }
}
