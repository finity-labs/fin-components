<?php

declare(strict_types=1);

namespace FinityLabs\LinCodex\Enums;

enum FallbackBehaviour: string
{
    case ShowDefault = 'show_default';
    case Hide = 'hide';

    public function label(): string
    {
        return (string) __('lin-codex::lin-codex.enums.fallback_behaviour.'.$this->value);
    }
}
