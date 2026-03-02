<?php

declare(strict_types=1);

namespace FinityLabs\FinMail\Policies;

use FinityLabs\FinMail\Models\SentEmail;
use Illuminate\Auth\Access\HandlesAuthorization;
use Illuminate\Foundation\Auth\User as AuthUser;

class SentEmailPolicy
{
    use HandlesAuthorization;

    public function viewAny(AuthUser $authUser): bool
    {
        return $authUser->can('ViewAny:SentEmail');
    }

    public function view(AuthUser $authUser, SentEmail $sentEmail): bool
    {
        return $authUser->can('View:SentEmail');
    }
}
