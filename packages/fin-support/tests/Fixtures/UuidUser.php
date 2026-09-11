<?php

declare(strict_types=1);

namespace FinityLabs\FinSupport\Tests\Fixtures;

use Filament\Models\Contracts\FilamentUser;
use Filament\Panel;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Foundation\Auth\User as Authenticatable;

/**
 * A user model keyed by a string UUID, as apps using HasUuids ship it.
 */
final class UuidUser extends Authenticatable implements FilamentUser
{
    use HasUuids;

    protected $table = 'users';

    protected $guarded = [];

    /** @var array<string, mixed> */
    protected $attributes = ['password' => null, 'remember_token' => null];

    public function canAccessPanel(Panel $panel): bool
    {
        return true;
    }
}
