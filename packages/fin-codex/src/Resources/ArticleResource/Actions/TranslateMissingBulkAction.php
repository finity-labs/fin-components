<?php

declare(strict_types=1);

namespace FinityLabs\FinCodex\Resources\ArticleResource\Actions;

use Filament\Actions\BulkAction;
use Filament\Forms\Components\CheckboxList;
use Filament\Notifications\Notification;
use Filament\Support\Icons\Heroicon;
use FinityLabs\LinCodex\Translation\MissingTranslations;
use Illuminate\Database\Eloquent\Collection as EloquentCollection;

/**
 * Translate missing, the table's first and only bulk action.
 *
 * It is the list's answer to "fill every gap in the selection": tick the
 * articles, press once, and every language any of them still lacks is queued.
 * No editor is opened and nothing is translated inline - that is the tab's
 * Translate with AI - so the modal is a language picker and the work happens
 * in the background.
 *
 * "Every language pre-checked" is not "every language written". The picker
 * offers every configured non-default language, all ticked, and each article
 * then receives only what IT lacks among the ticked ones; an article that
 * lacks none of them is left alone. That is why the same press can be right
 * for a selection whose articles are missing completely different languages.
 *
 * Two reads, no query per row:
 *
 * - the candidates come from the languages the table already loaded once per
 *   build, so the modal costs no settings query of its own. (The row action's
 *   modal lists one article's gaps instead; these are deliberately different
 *   lists.)
 * - the gaps come from MissingTranslations::for() on the eager-loaded
 *   translations relation. The selection is fetched through the table's own
 *   query, so with('translations') has already run for the whole selection.
 *
 * Availability is a plain bool handed down from the table: a bulk action has
 * no per-record gate to hang it on, and hiding it also takes the checkbox
 * column away, so with AI unavailable the list renders exactly as it did
 * before this slot existed.
 *
 * The per-article ability check lives inside the loop, through ArticleAbility
 * and the closure form of authorize, rather than in Filament's
 * individual-record authorization: that helper filters the records away before
 * the loop ever sees them and reports through a notification of its own, which
 * would take the not-permitted count out of the summary and replace the
 * wording with Filament's. The summary is the whole point, so the loop keeps
 * the count.
 *
 * No delete action sits beside it, on the row or in bulk, for the reason the
 * table's own docblock gives: deleting an article has consequences the admin
 * must see first, so it lives on the edit page behind its confirmation modal.
 *
 * There is no cap on the selection size. One job per article is the unit of
 * work, and the queue is what a queue is for.
 *
 * The loop, the three counts and the summary land with the bulk plan's second
 * task; the picker is here now.
 */
final class TranslateMissingBulkAction
{
    /**
     * @param  list<array{code: string, display: string, 'flag-icon': string}>  $languages
     */
    public static function make(bool $available, MissingTranslations $missing, array $languages, string $default): BulkAction
    {
        $candidates = self::candidates($languages, $default);

        return BulkAction::make('translate_missing')
            ->label(__('fin-codex::fin-codex.editor.translate_missing.bulk_label'))
            ->icon(Heroicon::OutlinedSparkles)
            ->visible($available)
            ->modalHeading(__('fin-codex::fin-codex.editor.translate_missing.bulk_heading'))
            ->modalDescription(static fn (EloquentCollection $records): string => trans_choice(
                'fin-codex::fin-codex.editor.translate_missing.bulk_description',
                $records->count(),
                ['count' => $records->count()],
            ))
            ->schema([
                CheckboxList::make('locales')
                    ->label(__('fin-codex::fin-codex.editor.translate_missing.languages'))
                    ->options($candidates)
                    ->default(array_keys($candidates))
                    ->required()
                    ->validationMessages(['required' => __('fin-codex::fin-codex.editor.translate_missing.pick_one')]),
            ])
            ->modalSubmitActionLabel(__('fin-codex::fin-codex.editor.translate_missing.submit'))
            ->action(static function (): void {
                Notification::make()
                    ->success()
                    ->title(__('fin-codex::fin-codex.editor.translate_missing.queued_title'))
                    ->send();
            })
            ->deselectRecordsAfterCompletion();
    }

    /**
     * Every configured language except the default one, in settings order, as
     * code => display. A language whose display name was never filled in falls
     * back to its code, so the picker never renders an empty label.
     *
     * @param  list<array{code: string, display: string, 'flag-icon': string}>  $languages
     *
     * @return array<string, string>
     */
    private static function candidates(array $languages, string $default): array
    {
        $candidates = [];

        foreach ($languages as $language) {
            if ($language['code'] === $default) {
                continue;
            }

            $candidates[$language['code']] = $language['display'] !== '' ? $language['display'] : $language['code'];
        }

        return $candidates;
    }
}
