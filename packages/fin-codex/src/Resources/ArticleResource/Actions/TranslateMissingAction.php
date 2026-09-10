<?php

declare(strict_types=1);

namespace FinityLabs\FinCodex\Resources\ArticleResource\Actions;

use Filament\Actions\Action;
use Filament\Forms\Components\CheckboxList;
use Filament\Notifications\Notification;
use Filament\Support\Icons\Heroicon;
use FinityLabs\FinCodex\Auth\ArticleAbility;
use FinityLabs\FinCodex\Editor\ArticleTitle;
use FinityLabs\LinCodex\Models\Article;
use FinityLabs\LinCodex\Translation\MissingTranslations;

/**
 * Translate missing, the row's second action beside Edit.
 *
 * Edit's neighbour, and the one way to fill every gap of an article without
 * opening the editor at all: the modal lists the languages this article still
 * lacks, pre-checked, and the ticked ones are handed to lin-codex's queued
 * translation job. The work runs in the background and the list's flags show
 * the result on the next load; nothing is translated inline here, unlike the
 * tab's Translate with AI.
 *
 * Only missing languages are ever listed. An outdated translation - one whose
 * default-language source was edited afterwards - is a judgement call about
 * text that already exists, so it is refreshed from the editor, where the
 * admin can read both versions first.
 *
 * Three gates, and Filament ANDs them:
 *
 * - availability. The table asks AiAvailabilityCheck once per build and hands
 *   the answer down as a bool, so every unavailable state takes the button
 *   away and the Help settings page is where the admin reads why. It is a
 *   settings read, and a settings read per row would be four on a four-row
 *   page.
 * - the ability. update on the row's article, through ArticleAbility and the
 *   closure form of authorize, never the string form: Filament unshifts the
 *   action's own record as the gate subject, so only a Closure is gated
 *   against the row it sits on (Phase 8's finding).
 * - something to translate into. MissingTranslations::for() on the same one
 *   instance the table resolved for the whole build, whose candidates are
 *   memoised, so the per-row verdict costs neither a settings query nor a
 *   translations query: the list eager-loads the relation and for() reads it.
 *   An article that lacks nothing shows no button.
 *
 * The verdict the modal shows is the verdict the worker re-checks, so the two
 * agree about what "missing" means and a language filled in between is
 * skipped rather than overwritten.
 */
final class TranslateMissingAction
{
    /**
     * @param  list<array{code: string, display: string, 'flag-icon': string}>  $languages
     */
    public static function make(bool $available, MissingTranslations $missing, array $languages, string $default): Action
    {
        $displayNames = self::displayNames($languages);
        $defaultDisplay = $displayNames[$default] ?? $default;

        return Action::make('translate_missing')
            ->label(__('fin-codex::fin-codex.editor.translate_missing.label'))
            ->icon(Heroicon::OutlinedSparkles)
            ->visible(static fn (Article $record): bool => $available && $missing->for($record) !== [])
            ->authorize(static fn (Article $record): bool => ArticleAbility::allows('update', $record))
            ->modalHeading(static fn (Article $record): string => (string) __(
                'fin-codex::fin-codex.editor.translate_missing.heading',
                ['title' => ArticleTitle::ofModel($record)],
            ))
            ->modalDescription(static fn (): string => (string) __('fin-codex::fin-codex.editor.translate_missing.description'))
            ->schema(static fn (): array => [
                CheckboxList::make('locales')
                    ->label(__('fin-codex::fin-codex.editor.translate_missing.languages'))
                    ->options(static fn (Article $record): array => self::options($missing->for($record), $displayNames))
                    ->default(static fn (Article $record): array => $missing->for($record)),
            ])
            ->modalSubmitActionLabel(__('fin-codex::fin-codex.editor.translate_missing.submit'))
            ->action(static function (): void {
                Notification::make()
                    ->success()
                    ->title(__('fin-codex::fin-codex.editor.translate_missing.queued_title'))
                    ->body(__('fin-codex::fin-codex.editor.translate_missing.queued_body'))
                    ->send();
            });
    }

    /**
     * Every configured language by code, named the way the admin arranged it,
     * falling back to the code when the entry carries no display name.
     *
     * @param  list<array{code: string, display: string, 'flag-icon': string}>  $languages
     *
     * @return array<string, string>
     */
    private static function displayNames(array $languages): array
    {
        $names = [];

        foreach ($languages as $language) {
            $names[$language['code']] = $language['display'] !== '' ? $language['display'] : $language['code'];
        }

        return $names;
    }

    /**
     * The checkbox options: the missing locales only, keeping the settings
     * order for() already hands them back in.
     *
     * @param  list<string>  $missingLocales
     * @param  array<string, string>  $displayNames
     *
     * @return array<string, string>
     */
    private static function options(array $missingLocales, array $displayNames): array
    {
        $options = [];

        foreach ($missingLocales as $locale) {
            $options[$locale] = $displayNames[$locale] ?? $locale;
        }

        return $options;
    }
}
