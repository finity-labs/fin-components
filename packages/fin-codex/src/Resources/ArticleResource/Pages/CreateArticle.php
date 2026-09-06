<?php

declare(strict_types=1);

namespace FinityLabs\FinCodex\Resources\ArticleResource\Pages;

use Filament\Actions\Action;
use Filament\Resources\Pages\CreateRecord;
use FinityLabs\FinCodex\Editor\ArticleWriter;
use FinityLabs\FinCodex\Editor\MediaRecorder;
use FinityLabs\FinCodex\Panel\Concerns\ResolvesPanelUser;
use FinityLabs\FinCodex\Resources\ArticleResource;
use FinityLabs\FinCodex\Resources\ArticleResource\Actions\PreviewAction;
use FinityLabs\FinCodex\Resources\ArticleResource\Schemas\ContextsRepeater;
use FinityLabs\FinCodex\Resources\ArticleResource\Schemas\TranslationTabs;
use FinityLabs\LinCodex\Models\Article;
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
    use ResolvesPanelUser;

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

    /**
     * The contexts repeater keeps `key` and `url` apart while the admin is
     * picking; the writer wants one key per row, in drag order.
     *
     * @param  array<string, mixed>  $data
     *
     * @return array<string, mixed>
     */
    protected function mutateFormDataBeforeCreate(array $data): array
    {
        $rows = $data['contexts'] ?? [];
        $data['contexts'] = ContextsRepeater::dehydrate(is_array($rows) ? array_values($rows) : []);

        return $data;
    }

    /**
     * Images uploaded while the article did not exist yet have no article id
     * on their codex_media row; the record they belong to is only known now.
     * Rows no body mentions stay orphans, and nothing is deleted here.
     */
    protected function afterCreate(): void
    {
        $record = $this->getRecord();

        if ($record instanceof Article) {
            app(MediaRecorder::class)->linkOrphans($record);
        }
    }

    protected function afterFill(): void
    {
        $this->activeLocale ??= TranslationTabs::languages()['default'];
    }

    protected function getRedirectUrl(): string
    {
        return self::getResource()::getUrl('edit', ['record' => $this->getRecord()]);
    }

    /**
     * Preview only. Convert and delete need a record, so they live on the
     * edit page the create redirects to.
     *
     * @return array<Action>
     */
    protected function getHeaderActions(): array
    {
        return [PreviewAction::make()];
    }

    /** The panel user's id, or null for a panel without an authenticated user. Public: the header actions attribute their writes to it. */
    public function userId(): ?int
    {
        return $this->panelUserId();
    }
}
