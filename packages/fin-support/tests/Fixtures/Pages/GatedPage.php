<?php

declare(strict_types=1);

namespace FinityLabs\FinSupport\Tests\Fixtures\Pages;

use Filament\Pages\Page;
use FinityLabs\FinSupport\Pages\Concerns\HasPageShieldSupport;

/**
 * A page on the Gate branch: no Shield in the class map, so canAccess()
 * follows a Gate ability named page_GatedPage, and is open without one.
 */
class GatedPage extends Page
{
    use HasPageShieldSupport;

    protected static ?string $slug = 'gated';

    protected string $view = 'fin-support-tests::blank';
}
