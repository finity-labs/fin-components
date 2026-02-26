<?php

declare(strict_types=1);

namespace FinityLabs\FinMail\Enums;

use Filament\Support\Contracts\HasColor;
use Filament\Support\Contracts\HasIcon;
use Filament\Support\Contracts\HasLabel;

enum EmailStatus: int implements HasColor, HasIcon, HasLabel
{
    case Draft = 1;
    case Queued = 2;
    case Sent = 3;
    case Failed = 4;

    public function getLabel(): ?string
    {
        return __('fin-mail::fin-mail.enums.email_status.'.$this->value);
    }

    public function getColor(): string|array|null
    {
        return match ($this) {
            self::Draft => 'gray',
            self::Queued => 'warning',
            self::Sent => 'success',
            self::Failed => 'danger',
        };
    }

    public function getIcon(): ?string
    {
        return match ($this) {
            self::Draft => 'heroicon-o-pencil-square',
            self::Queued => 'heroicon-o-clock',
            self::Sent => 'heroicon-o-check-circle',
            self::Failed => 'heroicon-o-x-circle',
        };
    }
}
