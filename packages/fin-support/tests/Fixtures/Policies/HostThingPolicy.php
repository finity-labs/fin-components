<?php

declare(strict_types=1);

namespace FinityLabs\FinSupport\Tests\Fixtures\Policies;

final class HostThingPolicy
{
    public function viewAny(mixed $user): bool
    {
        return false;
    }
}
