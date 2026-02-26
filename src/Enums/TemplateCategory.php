<?php

declare(strict_types=1);

namespace FinityLabs\FinMail\Enums;

use Filament\Support\Contracts\HasColor;
use Filament\Support\Contracts\HasLabel;

enum TemplateCategory: int implements HasColor, HasLabel
{
    case Transactional = 1;
    case Marketing = 2;
    case System = 3;
    case Notification = 4;

    public function getLabel(): ?string
    {
        return __('fin-mail::fin-mail.enums.template_category.'.$this->value);
    }

    public function getColor(): string|array|null
    {
        return match ($this) {
            self::Transactional => 'primary',
            self::Marketing => 'success',
            self::System => 'gray',
            self::Notification => 'warning',
        };
    }
}
