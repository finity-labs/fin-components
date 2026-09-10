<?php

declare(strict_types=1);

namespace FinityLabs\FinCodex\Resources\ArticleResource\Actions;

use Filament\Actions\Action;
use FinityLabs\LinCodex\Translation\MissingTranslations;

/**
 * Translate missing, the row's second action beside Edit.
 *
 * It offers the languages this article still lacks, pre-checked, and queues
 * lin-codex's TranslateArticle job for the ones the admin leaves ticked; the
 * work runs in the background and the list's flags show the result on the
 * next load. Nothing is translated inline here, unlike the tab's Translate
 * with AI.
 *
 * Three gates, and Filament ANDs them:
 *
 * - availability. The table asks AiAvailabilityCheck once per build and hands
 *   the answer down as a bool, so every unavailable state takes the button
 *   away and the Help settings page is where the admin reads why.
 * - the ability. update on the row's article, through ArticleAbility and the
 *   closure form of authorize, never the string form: Filament unshifts the
 *   action's own record as the gate subject, so only a Closure is gated
 *   against the row it sits on (Phase 8's finding).
 * - something to translate into. MissingTranslations::for() on the loaded
 *   relation (the table eager-loads translations, so it costs no query); an
 *   article that lacks nothing shows no button.
 *
 * The body lands with the row-action plan; until then the action is hidden
 * and the list renders exactly as it did before the slot existed.
 */
final class TranslateMissingAction
{
    /**
     * @param  list<array{code: string, display: string, 'flag-icon': string}>  $languages
     */
    public static function make(bool $available, MissingTranslations $missing, array $languages, string $default): Action
    {
        return Action::make('translate_missing')
            ->visible(false);
    }
}
