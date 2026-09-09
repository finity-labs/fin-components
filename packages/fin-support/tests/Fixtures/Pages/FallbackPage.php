<?php

declare(strict_types=1);

namespace FinityLabs\FinSupport\Tests\Fixtures\Pages;

/**
 * A page with its own last word, the way a plugin option answers: the seam
 * fin-sentinel's variant used to hard-code.
 */
final class FallbackPage extends GatedPage
{
    public static bool $fallback = true;

    protected static ?string $slug = 'fallback';

    protected static function canAccessFallback(): bool
    {
        return self::$fallback;
    }
}
