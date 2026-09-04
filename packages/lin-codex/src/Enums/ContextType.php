<?php

declare(strict_types=1);

namespace FinityLabs\LinCodex\Enums;

enum ContextType: string
{
    case PageClass = 'class';
    case Route = 'route';
    case Url = 'url';

    public function label(): string
    {
        return (string) __('lin-codex::lin-codex.enums.context_type.'.$this->value);
    }
}
