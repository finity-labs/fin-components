<?php

declare(strict_types=1);

namespace FinityLabs\LinCodex\Enums;

enum RevisionReason: string
{
    case Manual = 'manual';
    case Import = 'import';
    case AiRewrite = 'ai_rewrite';

    public function label(): string
    {
        return (string) __('lin-codex::lin-codex.enums.revision_reason.'.$this->value);
    }
}
