<?php

declare(strict_types=1);

namespace FinityLabs\FinCodex\Resources\ArticleResource\Actions;

use Filament\Actions\Action;
use Filament\Notifications\Notification;
use Filament\Support\Icons\Heroicon;
use FinityLabs\FinCodex\Resources\ArticleResource\RelationManagers\RevisionsRelationManager;
use FinityLabs\LinCodex\Models\ArticleRevision;
use FinityLabs\LinCodex\Revisions\RevisionManager;

/**
 * Putting one revision back, with the sentence that makes pressing it safe.
 *
 * fin-codex writes nothing here. RevisionManager::restore() stores the text
 * that is about to be replaced as a revision of its own, labelled with the
 * core's restore reason, swaps the revision's title, body and format in under
 * withoutRevisions() so the swap itself records nothing, recreates a
 * translation that was deleted since the revision was taken, and re-indexes
 * search_text through the translation's own save hook. Repeating any of the
 * four here would leave a second, mislabelled revision behind.
 *
 * That first snapshot is why the confirmation can promise an undo: the text
 * being replaced becomes an ordinary row in the same table, one restore away.
 * The modal says so, and names which language and which moment is coming
 * back, because a history table is a wall of timestamps and pressing the
 * wrong one has to be recoverable rather than merely unlikely.
 *
 * The author is the panel user, resolved by the manager — a relation manager
 * is its own Livewire component and has no handle on the page that renders
 * it, so it cannot ask EditArticle.
 */
final class RestoreRevisionAction
{
    public static function make(): Action
    {
        return Action::make('restore')
            ->label(__('fin-codex::fin-codex.revisions.restore.label'))
            ->icon(Heroicon::OutlinedArrowUturnLeft)
            ->color('warning')
            ->iconButton()
            ->requiresConfirmation()
            ->modalHeading(static fn (ArticleRevision $record): string => (string) __(
                'fin-codex::fin-codex.revisions.restore.heading',
                [
                    'locale' => mb_strtoupper($record->locale),
                    'time' => $record->created_at?->toDayDateTimeString() ?? '',
                ],
            ))
            // A closure, not a plain __(): the sentence names the language it
            // is about, and the record is only known per row.
            ->modalDescription(static fn (ArticleRevision $record): string => (string) __(
                'fin-codex::fin-codex.revisions.restore.description',
                ['locale' => mb_strtoupper($record->locale)],
            ))
            ->modalSubmitActionLabel(__('fin-codex::fin-codex.revisions.restore.submit'))
            ->action(static function (ArticleRevision $record, RevisionsRelationManager $livewire): void {
                // We already hold the article, so hand it to the revision: the
                // core reads $revision->article on its first line, and this
                // costs no query and cannot trip a lazy-loading violation if
                // the record ever arrives from a multi-row collection.
                $record->setRelation('article', $livewire->getOwnerRecord());

                app(RevisionManager::class)->restore($record, $livewire->userId());

                Notification::make()
                    ->success()
                    ->title(__('fin-codex::fin-codex.revisions.restore.done'))
                    ->send();
            });
    }
}
