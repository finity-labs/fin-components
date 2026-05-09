<?php

declare(strict_types=1);

namespace FinityLabs\FinSentinel\Events;

use Illuminate\Foundation\Events\Dispatchable;

class SentinelDebug
{
    use Dispatchable;

    public function __construct(
        public readonly mixed $data,
        public readonly ?string $subject = null,
    ) {}
}
