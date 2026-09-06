<?php

declare(strict_types=1);

namespace FinityLabs\FinCodex\Resources\ArticleResource\Pages;

use Filament\Actions\Action;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;
use FinityLabs\FinCodex\Resources\ArticleResource;

/**
 * The article list. 05-03 adds the "From files" tab beside it, which is why
 * the header keeps nothing but the create action for now.
 */
final class ListArticles extends ListRecords
{
    protected static string $resource = ArticleResource::class;

    /**
     * @return array<Action>
     */
    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make(),
        ];
    }
}
