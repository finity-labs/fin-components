<?php

declare(strict_types=1);

namespace FinityLabs\FinModalTableSelect\Components;

use Closure;
use Filament\Forms\Components\Hidden;
use Filament\Infolists\Components\TextEntry;
use Filament\Schemas\Components\Group;
use Filament\Schemas\Components\Utilities\Get;

/**
 * A read-only display target for fillsFields().
 *
 * Infolist entries are not stateful, so they cannot receive a fill on their
 * own — the value has to live in form state. FilledEntry pairs a Hidden field
 * (holds and dehydrates the value) with a TextEntry that reads it live, so
 * "pick a company, show its tax number as an entry, save it with the form"
 * is one line.
 */
final class FilledEntry
{
    public static function make(
        string $name,
        string|Closure|null $label = null,
        ?Closure $modifyEntryUsing = null,
    ): Group {
        $entry = TextEntry::make("{$name}_display")
            ->label($label ?? ucfirst(str_replace(['_', '-', '.'], ' ', $name)))
            ->state(fn (Get $get): mixed => $get($name));

        if ($modifyEntryUsing !== null) {
            $entry = $modifyEntryUsing($entry) ?? $entry;
        }

        return Group::make([
            Hidden::make($name),
            $entry,
        ]);
    }
}
