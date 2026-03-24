<?php

declare(strict_types=1);

namespace FinityLabs\FinSentinel\Services;

use FinityLabs\FinSentinel\Support\DebugBuilder;

class DebugService
{
    public function debug(mixed $data, ?string $subject = null): DebugBuilder
    {
        return new DebugBuilder($data, $subject);
    }
}
