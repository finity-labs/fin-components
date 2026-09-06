<?php

declare(strict_types=1);

namespace FinityLabs\FinCodex\Resources\ArticleResource\Pages;

use Filament\Facades\Filament;
use Filament\Resources\Pages\CreateRecord;
use FinityLabs\FinCodex\Editor\ArticleWriter;
use FinityLabs\FinCodex\Resources\ArticleResource;
use FinityLabs\FinCodex\Resources\ArticleResource\Schemas\TranslationTabs;
use Illuminate\Database\Eloquent\Model;

/**
 * Creating an article. The page validates and hands the form state to
 * ArticleWriter, which writes the row, its language tabs and its contexts in
 * one transaction attributed to the panel user; nothing is written here.
 *
 * After the create the admin lands on the edit page, where the media, the
 * revisions and the preview live.
 */
final class CreateArticle extends CreateRecord
{
    protected static string $resource = ArticleResource::class;

    /** The language tab the admin is looking at; Tabs::livewireProperty() writes it. */
    public ?string $activeLocale = null;

    /**
     * @param  array<string, mixed>  $data
     */
    protected function handleRecordCreation(array $data): Model
    {
        return app(ArticleWriter::class)->create($data, $this->userId());
    }

    protected function afterFill(): void
    {
        $this->activeLocale ??= TranslationTabs::languages()['default'];
    }

    protected function getRedirectUrl(): string
    {
        return self::getResource()::getUrl('edit', ['record' => $this->getRecord()]);
    }

    /** The panel user's id, or null for a panel without an authenticated user. */
    protected function userId(): ?int
    {
        $id = Filament::auth()->id();

        return is_numeric($id) ? (int) $id : null;
    }
}
