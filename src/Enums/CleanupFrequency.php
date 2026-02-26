<?php

declare(strict_types=1);

namespace FinityLabs\FinMail\Enums;

use Filament\Support\Contracts\HasLabel;

enum CleanupFrequency: int implements HasLabel
{
    case Daily = 1;
    case Weekly = 2;
    case Monthly = 3;

    public function getLabel(): ?string
    {
        return __('fin-mail::fin-mail.enums.cleanup_frequency.'.$this->value);
    }

    public function cronMethod(): string
    {
        return match ($this) {
            self::Daily => 'daily',
            self::Weekly => 'weekly',
            self::Monthly => 'monthly',
        };
    }
}
