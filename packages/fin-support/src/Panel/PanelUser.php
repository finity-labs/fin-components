<?php

declare(strict_types=1);

namespace FinityLabs\FinSupport\Panel;

use Filament\Facades\Filament;

/**
 * The panel user's id, narrowed to int|string|null.
 *
 * Filament::auth()->id() is mixed. A host keyed by an auto-increment id hands
 * back an int, one on HasUuids or HasUlids a string, and a guard with nobody
 * signed in null. All three are passed through as they are: a package that
 * stores the id sizes its column from the user model, so the string keys
 * belong in it too. Anything else (an array, an object) becomes null.
 *
 * Concerns\ResolvesPanelUser is the same answer for a page or a Livewire
 * component; this class is for the static closures of a schema or an action,
 * where there is no $this to take a trait method from.
 */
final class PanelUser
{
    public static function id(): int|string|null
    {
        $id = Filament::auth()->id();

        if (is_int($id)) {
            return $id;
        }

        return is_string($id) && $id !== '' ? $id : null;
    }
}
