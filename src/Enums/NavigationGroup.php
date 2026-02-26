<?php

declare(strict_types=1);

namespace FinityLabs\FinMail\Enums;

use Filament\Support\Contracts\HasLabel;

enum NavigationGroup: string implements HasLabel
{
    case Email = 'email';

    public function getLabel(): ?string
    {
        return match ($this) {
            self::Email => __('fin-mail::fin-mail.navigation.group'),
        };
    }
}
