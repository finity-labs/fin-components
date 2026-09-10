<?php

use Filament\Notifications\DatabaseNotification;
use FinityLabs\FinCodex\Ai\NotifyTranslationFinished;
use FinityLabs\FinCodex\Editor\ArticleTitle;
use FinityLabs\FinCodex\Tests\Fixtures\User;
use FinityLabs\LinCodex\Ai\AiReason;
use FinityLabs\LinCodex\Events\ArticleTranslated;
use FinityLabs\LinCodex\Locale\LocaleResolver;
use FinityLabs\LinCodex\Models\Article;
use FinityLabs\LinCodex\Models\ArticleTranslation;
use FinityLabs\LinCodex\Settings\CodexSettings;
use FinityLabs\LinCodex\Translation\TranslationReport;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Queue;

/*
 * AIBULK-03: the admin who queued a translation run is told how it went.
 *
 * Every row dispatches ArticleTranslated by hand, with the real dispatcher and
 * never Event::fake(), because the listener IS what is under test here: faking
 * the dispatcher would assert that the event was sent, not that a row landed
 * in the notifications table. The end-to-end chain - row action, queued job,
 * event, listener, badge - is 11-06's.
 */

/** A fixture user, the admin whose id the event carries. */
function finCodexNotifyUser(string $name = 'Notify'): User
{
    return User::create(['name' => $name, 'email' => strtolower($name).'@example.com']);
}

/**
 * The languages this file's reports name. Its own helper rather than a
 * borrowed one: a Pest helper is loaded with its file, so a helper from
 * another test file is undefined the moment this one runs alone.
 *
 * @param  list<string>  $codes
 */
function finCodexNotifyUseLanguages(array $codes = ['en', 'de', 'hu', 'ro'], string $default = 'en'): void
{
    $settings = app(CodexSettings::class);
    $settings->languages = array_map([CodexSettings::class, 'languageEntry'], $codes);
    $settings->default_locale = $default;
    $settings->save();
}

/** An article with one English translation, the way the editor leaves it. */
function finCodexNotifyArticle(string $title = 'Users'): Article
{
    $article = Article::factory()->markdown()->create(['slug' => 'users']);

    ArticleTranslation::factory()->create([
        'article_id' => $article->id,
        'locale' => 'en',
        'title' => $title,
        'excerpt' => 'Manage users.',
        'body' => 'How users work.',
    ]);

    return $article;
}

/**
 * A report recorded in the order the job would have worked through it:
 * translated first, then failed, then skipped.
 *
 * @param  list<string>  $translated
 * @param  array<string, string>  $failed  locale => AiReason key
 * @param  list<string>  $skipped
 */
function finCodexNotifyReport(array $translated = [], array $failed = [], array $skipped = []): TranslationReport
{
    $report = new TranslationReport;

    foreach ($translated as $locale) {
        $report->translated($locale, 1, 2);
    }

    foreach ($failed as $locale => $reason) {
        $report->failed($locale, $reason);
    }

    foreach ($skipped as $locale) {
        $report->skipped($locale);
    }

    return $report;
}

/** Fire the event the job fires, with the real dispatcher. */
function finCodexNotifyFire(Article|int $article, ?int $userId, TranslationReport $report): void
{
    event(new ArticleTranslated($article instanceof Article ? $article->id : $article, $userId, $report));
}

/** The display name lin-codex renders a locale under. */
function finCodexNotifyName(string $code): string
{
    return app(LocaleResolver::class)->displayName($code);
}

beforeEach(function (): void {
    finCodexNotifyUseLanguages();
});

it('stores one success notification naming the translated languages with a button to the edit page', function (): void {
    $user = finCodexNotifyUser();
    $article = finCodexNotifyArticle();

    $this->usesPanel('admin', $user);

    finCodexNotifyFire($article, $user->id, finCodexNotifyReport(['de', 'hu']));

    $stored = finCodexNotificationsFor($user);

    expect($stored)->toHaveCount(1);

    $row = $stored->first();

    expect($row->type)->toBe(DatabaseNotification::class)
        ->and($row->data['format'])->toBe('filament')
        ->and($row->data['status'])->toBe('success')
        ->and($row->data['title'])->toBe(ArticleTitle::ofModel($article->load('translations')))
        ->and($row->data['title'])->toBe('Users')
        ->and($row->data['body'])->toBe(__('fin-codex::fin-codex.notification.translated', [
            'languages' => finCodexNotifyName('de').', '.finCodexNotifyName('hu'),
        ]))
        ->and($row->data['duration'])->toBe('persistent')
        ->and($row->data['actions'])->toHaveCount(1)
        ->and($row->data['actions'][0]['label'])->toBe(__('fin-codex::fin-codex.notification.open'))
        ->and($row->data['actions'][0]['url'])->toEndWith('/codex-articles/'.$article->id.'/edit')
        ->and($row->read_at)->toBeNull();
});

it('turns warning and names the failed languages with their reason labels, never the skipped ones', function (): void {
    $user = finCodexNotifyUser();
    $article = finCodexNotifyArticle();

    finCodexNotifyFire($article, $user->id, finCodexNotifyReport(
        ['de'],
        ['hu' => AiReason::RATE_LIMITED],
        ['ro'],
    ));

    $row = finCodexNotificationsFor($user)->first();

    $translated = __('fin-codex::fin-codex.notification.translated', ['languages' => finCodexNotifyName('de')]);
    $failed = __('fin-codex::fin-codex.notification.failed', [
        'languages' => finCodexNotifyName('hu').' ('.AiReason::label(AiReason::RATE_LIMITED).')',
    ]);

    expect($row->data['status'])->toBe('warning')
        ->and($row->data['body'])->toBe($translated.' '.$failed)
        ->and($row->data['body'])->not->toContain(finCodexNotifyName('ro'));
});

it('says nothing was needed when every language was skipped', function (): void {
    $user = finCodexNotifyUser();
    $article = finCodexNotifyArticle();

    finCodexNotifyFire($article, $user->id, finCodexNotifyReport([], [], ['de', 'hu']));

    $row = finCodexNotificationsFor($user)->first();

    expect($row->data['status'])->toBe('success')
        ->and($row->data['body'])->toBe(__('fin-codex::fin-codex.notification.nothing_to_do'));
});

it('is registered by the service provider', function (): void {
    expect(Event::hasListeners(ArticleTranslated::class))->toBeTrue();

    $raw = Event::getRawListeners();

    expect($raw)->toHaveKey(ArticleTranslated::class)
        ->and($raw[ArticleTranslated::class])->toContain(NotifyTranslationFinished::class);
});

it('tells nobody when the user id is null or the user is gone', function (): void {
    $user = finCodexNotifyUser();
    $article = finCodexNotifyArticle();

    finCodexNotifyFire($article, null, finCodexNotifyReport(['de']));

    expect(DB::table('notifications')->count())->toBe(0);

    finCodexNotifyFire($article, $user->id + 100, finCodexNotifyReport(['de']));

    expect(DB::table('notifications')->count())->toBe(0);
});

it('names a deleted article by its id and offers no button', function (): void {
    $user = finCodexNotifyUser();
    $article = finCodexNotifyArticle();
    $id = $article->id;

    $article->delete();

    finCodexNotifyFire($id, $user->id, finCodexNotifyReport([], [
        'de' => AiReason::UNKNOWN,
        'hu' => AiReason::UNKNOWN,
    ]));

    $stored = finCodexNotificationsFor($user);

    expect($stored)->toHaveCount(1);

    $row = $stored->first();

    expect($row->data['title'])->toBe(__('fin-codex::fin-codex.notification.deleted_article', ['id' => $id]))
        ->and($row->data['actions'])->toBe([])
        ->and($row->data['status'])->toBe('warning');
});

it('stores the row inside the call on a faked queue', function (): void {
    Queue::fake();

    $user = finCodexNotifyUser();
    $article = finCodexNotifyArticle();

    finCodexNotifyFire($article, $user->id, finCodexNotifyReport(['de']));

    expect(finCodexNotificationsFor($user))->toHaveCount(1);

    Queue::assertNothingPushed();
});
