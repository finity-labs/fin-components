<?php

declare(strict_types=1);

namespace FinityLabs\FinCodex\Tests\Fixtures\Pages;

use Filament\Pages\Page;

/**
 * A custom page on the app layout. Phase 3 identifies it by its own class,
 * unlike resource pages.
 */
final class Reports extends Page
{
    protected static ?string $slug = 'reports';
}
