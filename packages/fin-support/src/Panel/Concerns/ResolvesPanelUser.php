<?php

declare(strict_types=1);

namespace FinityLabs\FinSupport\Panel\Concerns;

use Filament\Facades\Filament;

/**
 * The panel user's id, narrowed to ?int.
 *
 * Filament::auth()->id() is mixed (a uuid-keyed host is legal); a package
 * that stores it in an int column narrows it here, once, and stores null
 * for anything that is not numeric.
 */
trait ResolvesPanelUser
{
    protected function panelUserId(): ?int
    {
        $id = Filament::auth()->id();

        return is_numeric($id) ? (int) $id : null;
    }
}
