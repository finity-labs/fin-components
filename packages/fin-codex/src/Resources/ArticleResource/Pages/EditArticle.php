<?php

declare(strict_types=1);

namespace FinityLabs\FinCodex\Resources\ArticleResource\Pages;

use Filament\Actions\Action;
use Filament\Facades\Filament;
use Filament\Resources\Pages\EditRecord;
use FinityLabs\FinCodex\Editor\ArticleWriter;
use FinityLabs\FinCodex\Resources\ArticleResource;
use FinityLabs\FinCodex\Resources\ArticleResource\Schemas\TranslationTabs;
use FinityLabs\LinCodex\Models\Article;
use FinityLabs\LinCodex\Models\ArticleTranslation;
use FinityLabs\LinCodex\Sources\FilesystemSource;
use Illuminate\Database\Eloquent\Model;

/**
 * Editing an article. Every save goes to ArticleWriter, so the article, its
 * language tabs and its contexts are written in one transaction under the
 * panel user's attribution and the revision carries that user's id.
 *
 * The header stays empty for now: preview, convert and delete arrive in
 * 05-07, delete behind the confirmation modal that lists what a delete takes
 * with it. A bare DeleteAction would hide those consequences. The subheading
 * is the standing notice for an article that shadows a file.
 */
final class EditArticle extends EditRecord
{
    protected static string $resource = ArticleResource::class;

    /** The language tab the admin is looking at; Tabs::livewireProperty() writes it. */
    public ?string $activeLocale = null;

    /**
     * A standing notice under the page title while a file of the same slug
     * exists: the composite source lets the database row hide it whole, so
     * the file is dead weight until the article is deleted. Asked of the
     * file source, never the composite, which would always answer with this
     * very article.
     *
     * Narrower than the parent's string|Htmlable|null on purpose: the notice
     * is plain text, and Filament escapes a string subheading.
     */
    public function getSubheading(): ?string
    {
        $record = $this->getRecord();

        if (! $record instanceof Article || app(FilesystemSource::class)->findBySlug($record->slug) === null) {
            return null;
        }

        return __('fin-codex::fin-codex.editor.shadowed', ['path' => (string) ($record->source_path ?? $record->slug)]);
    }

    /**
     * @param  array<string, mixed>  $data
     */
    protected function handleRecordUpdate(Model $record, array $data): Model
    {
        /** @var Article $record */
        return app(ArticleWriter::class)->update($record, $data, $this->userId());
    }

    /**
     * The form state the writer speaks: enums as their backing values, the
     * json columns as arrays, and one entry per stored translation keyed by
     * locale. The translations are read with a fresh query rather than the
     * relation, so strict models never see an unloaded relation.
     *
     * @param  array<string, mixed>  $data
     *
     * @return array<string, mixed>
     */
    protected function mutateFormDataBeforeFill(array $data): array
    {
        $record = $this->getRecord();

        if (! $record instanceof Article) {
            return $data;
        }

        $data['format'] = $record->format->value;
        $data['visibility'] = $record->visibility->value;
        $data['keywords'] = $record->keywords ?? [];
        $data['related'] = $record->related ?? [];
        $data['translations'] = $record->translations()->get()
            ->mapWithKeys(fn (ArticleTranslation $translation): array => [
                $translation->locale => [
                    'title' => $translation->title,
                    'excerpt' => $translation->excerpt,
                    'body' => $translation->body,
                ],
            ])
            ->all();

        return $data;
    }

    protected function afterFill(): void
    {
        $this->activeLocale ??= TranslationTabs::languages()['default'];
    }

    /**
     * @return array<Action>
     */
    protected function getHeaderActions(): array
    {
        return [];
    }

    /** The panel user's id, or null for a panel without an authenticated user. */
    protected function userId(): ?int
    {
        $id = Filament::auth()->id();

        return is_numeric($id) ? (int) $id : null;
    }
}
