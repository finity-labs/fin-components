<?php

declare(strict_types=1);

namespace FinityLabs\FinCodex\Resources\ArticleResource\Actions;

use Filament\Actions\BulkAction;
use FinityLabs\LinCodex\Translation\MissingTranslations;

/**
 * Translate missing, the table's first bulk action.
 *
 * It offers every configured non-default language, pre-checked, and queues
 * one TranslateArticle job per selected article for the languages that
 * article lacks among the ticked ones; an article that lacks none of them
 * gets no job and is counted as needing nothing. The confirmation names the
 * selection count and says the work runs in the background, and the summary
 * after the press reports queued, needed nothing and, only when non-zero,
 * not permitted.
 *
 * Per-article authorization happens inside the loop, through ArticleAbility
 * and the closure form of authorize on the whole action, not through
 * Filament's individual-record authorization: Filament's own filtering
 * would drop the refused articles before the loop and report them in its
 * own notification, which is exactly the not-permitted count the summary
 * has to keep. The selection is fetched through the table's query, so the
 * eager-loaded translations relation is there and MissingTranslations::for()
 * reads it without a query per row.
 *
 * Availability comes down from the table as a bool computed once per build,
 * the same way the row action gets it.
 *
 * The body lands with the bulk-action plan; until then the action is hidden,
 * so the list shows no checkbox column and renders exactly as it did before
 * the slot existed.
 */
final class TranslateMissingBulkAction
{
    /**
     * @param  list<array{code: string, display: string, 'flag-icon': string}>  $languages
     */
    public static function make(bool $available, MissingTranslations $missing, array $languages, string $default): BulkAction
    {
        return BulkAction::make('translate_missing')
            ->visible(false);
    }
}
