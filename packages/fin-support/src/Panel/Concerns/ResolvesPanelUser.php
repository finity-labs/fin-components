<?php

declare(strict_types=1);

namespace FinityLabs\FinSupport\Panel\Concerns;

use FinityLabs\FinSupport\Panel\PanelUser;

/**
 * The panel user's id, narrowed to int|string|null.
 *
 * Filament::auth()->id() is mixed. A host keyed by an auto-increment id hands
 * back an int, one on HasUuids or HasUlids a string, and a guard with nobody
 * signed in null. All three are passed through as they are: a package that
 * stores the id sizes its column from the user model, so the string keys
 * belong in it too. Anything else (an array, an object) becomes null.
 *
 * PanelUser::id() is the same answer for a static closure, where there is no
 * $this to take this method from.
 */
trait ResolvesPanelUser
{
    protected function panelUserId(): int|string|null
    {
        return PanelUser::id();
    }
}
