<?php

declare(strict_types=1);

namespace FinityLabs\FinAvatar\AvatarProviders;

use Filament\AvatarProviders\Contracts;
use Filament\Facades\Filament;
use Filament\Support\Colors\Color;
use Filament\Support\Facades\FilamentColor;
use Illuminate\Contracts\Auth\Authenticatable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;

class UiAvatarsProvider implements Contracts\AvatarProvider
{
    public function get(Model | Authenticatable $record): string
    {
        $name = Filament::getNameForDefaultAvatar($record);

        // Normalize all parts to lowercase and remove dots
        $partsToRemove = collect(config('fin-avatar.ignored_parts', []))
            ->map(fn ($part) => Str::lower(rtrim($part, '.')))
            ->all();

        $initials = str($name)
            ->trim()
            ->explode(' ')
            ->reject(fn (string $segment) => in_array(Str::lower(rtrim($segment, '.')), $partsToRemove))
            ->map(fn (string $segment): string => filled($segment) ? mb_substr($segment, 0, 1) : '')
            ->join('');

        if (empty($initials)) {
            $initials = mb_substr($name, 0, 1);
        }

        $shade = config('fin-avatar.default_bg');
        if (blank($shade)) {
            $colors = FilamentColor::getColors();
            $primary = $colors['primary'] ?? Color::Amber;
            // Get shade 600 or 500, default to Filament default
            $shade = $primary[600] ?? $primary[500] ?? FilamentColor::getColor('gray')[950] ?? Color::Gray[950];
        }

        // Handle Tailwind "rgb" strings vs Hex
        $bgCss = str_contains((string) $shade, ',') ? "rgb($shade)" : $shade;

        // 4. Return URL
        return route('fin-avatar.render', [
            'initials' => $initials,
            'bg' => $bgCss,
        ]);
    }
}
