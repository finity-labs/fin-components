<?php

declare(strict_types=1);

namespace FinityLabs\FinSupport\Tests\Fixtures\Policies;

final class ShippedThingPolicy
{
    public function viewAny(mixed $user): bool
    {
        return true;
    }
}
