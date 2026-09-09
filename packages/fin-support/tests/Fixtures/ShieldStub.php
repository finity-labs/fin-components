<?php

declare(strict_types=1);

namespace FinityLabs\FinSupport\Tests\Fixtures;

use FinityLabs\FinSupport\Tests\Fixtures\Pages\ShieldedPage;

/**
 * What FilamentShield::getPages() answers, in Shield 4's shape and naming
 * ('View:ShieldedPage', not Shield 3's page_ShieldedPage). A plain class,
 * deliberately not named BezhanSalleh\*: defining a real Shield class would
 * flip every later test onto the Shield branch.
 */
final class ShieldStub
{
    public const PERMISSION = 'View:ShieldedPage';

    /**
     * @return array<class-string, array{pageFqcn: class-string, permissions: array<string, string>}>
     */
    public static function getPages(): array
    {
        return [
            ShieldedPage::class => [
                'pageFqcn' => ShieldedPage::class,
                'permissions' => [self::PERMISSION => 'View'],
            ],
        ];
    }
}
