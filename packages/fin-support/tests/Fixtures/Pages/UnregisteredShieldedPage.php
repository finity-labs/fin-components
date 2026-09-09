<?php

declare(strict_types=1);

namespace FinityLabs\FinSupport\Tests\Fixtures\Pages;

/** Shield is present but has no permission for this page. */
final class UnregisteredShieldedPage extends ShieldedPage
{
    protected static ?string $slug = 'unregistered';
}
