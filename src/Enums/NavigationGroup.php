<?php

declare(strict_types=1);

namespace FinityLabs\FinSentinel\Enums;

use Filament\Support\Contracts\HasLabel;

enum NavigationGroup: string implements HasLabel
{
    case Sentinel = 'sentinel';

    public function getLabel(): string
    {
        return match ($this) {
            self::Sentinel => 'Sentinel',
        };
    }
}
