<?php

declare(strict_types=1);

namespace FinityLabs\FinSupport\Tests\Fixtures\Pages;

use FinityLabs\FinSupport\Tests\Fixtures\ShieldStub;

/**
 * The page as it behaves on a host that HAS Shield: only the trait's two
 * seams are overridden, "is Shield here?" and "what does Shield call this
 * page's permission?". Everything exercised — canAccess() and
 * shouldRegisterNavigation() — is the trait's own code. Not memoised into
 * the trait's static property, which subclasses would share.
 */
class ShieldedPage extends GatedPage
{
    protected static ?string $slug = 'shielded';

    protected static function isShieldAvailable(): bool
    {
        return true;
    }

    protected static function getPagePermission(): ?string
    {
        $page = ShieldStub::getPages()[static::class] ?? null;

        return $page ? array_key_first($page['permissions']) : null;
    }
}
