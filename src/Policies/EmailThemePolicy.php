<?php

declare(strict_types=1);

namespace FinityLabs\FinMail\Policies;

use FinityLabs\FinMail\Models\EmailTheme;
use Illuminate\Auth\Access\HandlesAuthorization;
use Illuminate\Foundation\Auth\User as AuthUser;

class EmailThemePolicy
{
    use HandlesAuthorization;

    public function viewAny(AuthUser $authUser): bool
    {
        return $authUser->can('ViewAny:EmailTheme');
    }

    public function view(AuthUser $authUser, EmailTheme $emailTheme): bool
    {
        return $authUser->can('View:EmailTheme');
    }

    public function create(AuthUser $authUser): bool
    {
        return $authUser->can('Create:EmailTheme');
    }

    public function update(AuthUser $authUser, EmailTheme $emailTheme): bool
    {
        return $authUser->can('Update:EmailTheme');
    }

    public function delete(AuthUser $authUser, EmailTheme $emailTheme): bool
    {
        return $authUser->can('Delete:EmailTheme');
    }
}
