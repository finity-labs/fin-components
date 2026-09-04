<?php

declare(strict_types=1);

namespace FinityLabs\LinCodex\Enums;

enum ArticleFormat: string
{
    case Markdown = 'markdown';
    case Html = 'html';

    public function label(): string
    {
        return (string) __('lin-codex::lin-codex.enums.article_format.'.$this->value);
    }
}
