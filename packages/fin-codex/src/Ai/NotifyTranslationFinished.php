<?php

declare(strict_types=1);

namespace FinityLabs\FinCodex\Ai;

use BadMethodCallException;
use Filament\Actions\Action;
use Filament\Facades\Filament;
use Filament\Notifications\Notification;
use FinityLabs\FinCodex\Editor\ArticleTitle;
use FinityLabs\FinCodex\FinCodexPlugin;
use FinityLabs\LinCodex\Ai\AiReason;
use FinityLabs\LinCodex\Events\ArticleTranslated;
use FinityLabs\LinCodex\Locale\LocaleResolver;
use FinityLabs\LinCodex\Models\Article;
use FinityLabs\LinCodex\Translation\TranslationReport;
use Illuminate\Contracts\Auth\Authenticatable;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use Throwable;

/**
 * The panel's answer to a finished translation run: one stored notification
 * for the admin who queued it, naming the article and the languages that
 * arrived and the ones that did not, with a button back to the article.
 *
 * A plain listener, not a queued one. lin-codex fires ArticleTranslated at
 * the very end of its own queued job, so this already runs on the worker; a
 * second queue hop would only delay the bell and add a job that can fail on
 * its own. The work here is one query and one insert.
 *
 * The user is resolved through the panel guard's own provider - the same
 * provider Filament's bell authenticates with when it later reads the rows -
 * so the row is written against exactly the model the panel will query. The
 * event carries ids and no models, so a user deleted between dispatch and
 * work simply means nobody is told.
 *
 * Delivery is inline, through the notifiable itself, rather than through the
 * builder's own database send: that one enqueues Filament's queued database
 * notification, so on any driver but sync it returns before the row exists
 * and a failure would land in the host's failed jobs, outside the catch
 * below. Inline delivery writes the same row - format filament, persistent -
 * inside this call on every driver, which is what makes the catch true.
 *
 * Nothing here looks for the notifications table and nothing walks the
 * panels (locked). Filament and Laravel already cope with a host that never
 * ran the notifications migration; all this listener adds is a log line with
 * enough context to say which article, which admin and which languages the
 * lost notification was about. A host without the table keeps its finished
 * translations either way.
 *
 * A report with failures also writes a warning line, whether or not the
 * notification could be stored: a failed language is something the host's log
 * should carry even when the admin's bell says the same thing (locked).
 *
 * The title is a fixed label saying what happened - translated, failed, or
 * nothing to do - and never the article's own title. A bell row that reads
 * "Create an account" out of context looks like news about creating an
 * account; the article belongs in the first sentence of the body, where the
 * surrounding words say what is being reported about it.
 *
 * The notification renders under the application locale, because a worker has
 * no panel and therefore no panel language. A deleted article is named by its
 * id inside that first sentence and gets no button, since there is no page
 * left to open.
 */
final class NotifyTranslationFinished
{
    public function handle(ArticleTranslated $event): void
    {
        if ($event->report->hasFailures()) {
            Log::warning('fin-codex: AI translation failed for some languages', [
                'article_id' => $event->articleId,
                'user_id' => $event->userId,
                'failed' => $event->report->failedLocales(),
            ]);
        }

        if ($event->userId === null) {
            return;
        }

        $user = $this->user($event->userId);

        if ($user === null) {
            return;
        }

        $article = Article::query()->with('translations')->find($event->articleId);

        $notification = $this->build($event, $article);

        try {
            if (! method_exists($user, 'notifyNow')) {
                throw new BadMethodCallException(sprintf('%s cannot be notified: it does not use the Notifiable trait.', $user::class));
            }

            $user->notifyNow($notification->toDatabase());
        } catch (Throwable $e) {
            Log::error('fin-codex: could not store the translation notification', [
                'article_id' => $event->articleId,
                'user_id' => $event->userId,
                'locales' => array_keys($event->report->toArray()),
                'report' => $event->report->toArray(),
                'exception' => $e,
            ]);
        }
    }

    /**
     * The admin who queued the run, read through the current-or-default
     * panel's guard provider; null when the id belongs to nobody or the guard
     * names no provider at all.
     */
    private function user(int $id): ?Authenticatable
    {
        $guard = Filament::getAuthGuard();

        $provider = Auth::createUserProvider((string) config("auth.guards.{$guard}.provider"));

        return $provider?->retrieveById($id);
    }

    /**
     * Title, body, status and - for an article that still exists - the one
     * button back to its edit page. With no current panel the URL resolves
     * through the default panel and the plugin's resource override.
     */
    private function build(ArticleTranslated $event, ?Article $article): Notification
    {
        $notification = Notification::make()
            ->title($this->title($event->report))
            ->body($this->body($event->report, $this->articleName($event, $article)));

        if ($event->report->hasFailures()) {
            $notification->warning();
        } else {
            $notification->success();
        }

        if ($article !== null) {
            $notification->actions([
                Action::make('open')
                    ->label(__('fin-codex::fin-codex.notification.open'))
                    ->url(FinCodexPlugin::articleResourceClass()::getUrl('edit', ['record' => $event->articleId]))
                    ->button(),
            ]);
        }

        return $notification;
    }

    /**
     * The fixed label the row is headed with, by outcome: something arrived,
     * nothing arrived but something failed, or there was nothing to do. Three
     * strings, none of them the article's own title.
     */
    private function title(TranslationReport $report): string
    {
        if ($report->translatedLocales() !== []) {
            return (string) __('fin-codex::fin-codex.notification.title.translated');
        }

        if ($report->failedLocales() !== []) {
            return (string) __('fin-codex::fin-codex.notification.title.failed');
        }

        return (string) __('fin-codex::fin-codex.notification.title.nothing');
    }

    /**
     * What the body calls the article: its title in the panel's own rendering,
     * or - once the article is gone - its id, which is all that is left of it.
     */
    private function articleName(ArticleTranslated $event, ?Article $article): string
    {
        return $article === null
            ? (string) __('fin-codex::fin-codex.notification.deleted_article', ['id' => $event->articleId])
            : ArticleTitle::ofModel($article);
    }

    /**
     * Up to two sentences, the first of which names the article: what was
     * translated, and what failed with the reason label of each language.
     * Skipped languages are not named - the admin asked for the missing ones
     * and a language somebody filled in the meantime is not news. When neither
     * sentence applies, every requested language was already there.
     *
     * Only failures is one sentence rather than two, so the article is still
     * named where the news is: a bare "Failed: ..." under a fixed title would
     * never say which article failed.
     */
    private function body(TranslationReport $report, string $name): string
    {
        $resolver = app(LocaleResolver::class);

        $translated = $report->translatedLocales();
        $failed = $report->failedLocales();

        if ($translated === [] && $failed === []) {
            return (string) __('fin-codex::fin-codex.notification.body.nothing_to_do', ['title' => $name]);
        }

        $pairs = [];

        foreach ($failed as $locale => $reason) {
            $pairs[] = $resolver->displayName($locale).' ('.AiReason::label($reason).')';
        }

        if ($translated === []) {
            return (string) __('fin-codex::fin-codex.notification.body.failed_only', [
                'title' => $name,
                'languages' => implode(', ', $pairs),
            ]);
        }

        $names = array_map(static fn (string $locale): string => $resolver->displayName($locale), $translated);

        $sentences = [(string) __('fin-codex::fin-codex.notification.body.translated', [
            'title' => $name,
            'languages' => implode(', ', $names),
        ])];

        if ($pairs !== []) {
            $sentences[] = (string) __('fin-codex::fin-codex.notification.body.also_failed', [
                'languages' => implode(', ', $pairs),
            ]);
        }

        return implode(' ', $sentences);
    }
}
