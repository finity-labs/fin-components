<?php

declare(strict_types=1);

namespace FinityLabs\FinSentinel\Facades;

use FinityLabs\FinSentinel\Services\DebugService;
use Illuminate\Support\Facades\Facade;

/**
 * @method static \FinityLabs\FinSentinel\Support\DebugBuilder debug(mixed $data, ?string $subject = null)
 *
 * @see DebugService
 */
class FinSentinel extends Facade
{
    protected static function getFacadeAccessor(): string
    {
        return 'fin-sentinel.debug';
    }
}
