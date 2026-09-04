<?php

declare(strict_types=1);

namespace FinityLabs\LinCodex\Enums;

enum Visibility: string
{
    case Public = 'public';
    case Authenticated = 'authenticated';

    public function label(): string
    {
        return (string) __('lin-codex::lin-codex.enums.visibility.'.$this->value);
    }
}
