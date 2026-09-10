<?php

use FinityLabs\FinCodex\Resources\ArticleResource\Pages\ListArticles;
use FinityLabs\FinCodex\Resources\ArticleResource\Schemas\TranslationTabs;
use FinityLabs\FinCodex\Tests\Fixtures\FakeAiClient;
use FinityLabs\FinCodex\Tests\Fixtures\Policies\DenyAllArticlePolicy;
use FinityLabs\FinCodex\Tests\Fixtures\User;
use FinityLabs\LinCodex\Models\Article;
use FinityLabs\LinCodex\Models\ArticleTranslation;
use FinityLabs\LinCodex\Settings\CodexSettings;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;
use Livewire\Livewire;

/*
 * AIBULK-01 and the row half of AIBULK-04: Translate missing, the action
 * beside Edit on the article list.
 *
 * Every row drives ListArticles, so what is asserted is what the admin gets:
 * the button is resolved out of the live table and pressed through the page.
 * The seam is the fake AiClient bound by finCodexFakeAi() plus the stored AI
 * settings, which is the whole availability wiring.
 *
 * A hidden TABLE row action still resolves - the opposite of the schema
 * actions on the language tabs - so every negative row asserts it is hidden
 * rather than that it does not exist.
 *
 * The queue is faked in every row that presses the button: the harness runs
 * on the sync driver, so an unfaked press would translate inline through the
 * job and prove something else entirely. The sync chain is 11-06's subject.
 */

/** A fixture user signed in on the admin panel. */
function finCodexMissingUser(string $name = 'Missing'): User
{
    return User::create(['name' => $name, 'email' => strtolower($name).'@example.com']);
}

/**
 * The languages this file's articles are written in. Its own helper rather
 * than a borrowed one: a Pest helper is loaded with its file, so a helper
 * from another test file is undefined the moment this one runs alone.
 *
 * @param  list<string>  $codes
 */
function finCodexMissingUseLanguages(array $codes = ['en', 'de', 'hu'], string $default = 'en'): void
{
    $settings = app(CodexSettings::class);
    $settings->languages = array_map([CodexSettings::class, 'languageEntry'], $codes);
    $settings->default_locale = $default;
    $settings->save();
}

/**
 * One translation row, with a filled title and body unless overridden.
 *
 * @param  array<string, mixed>  $fields
 */
function finCodexMissingTranslate(Article $article, string $locale, array $fields = []): ArticleTranslation
{
    return ArticleTranslation::factory()->create(array_merge([
        'article_id' => $article->id,
        'locale' => $locale,
        'title' => strtoupper($locale).' '.$article->slug,
        'body' => strtoupper($locale).' body',
    ], $fields));
}

/**
 * A Markdown article with one translation row per locale => fields pair.
 *
 * @param  array<string, array<string, mixed>>  $translations
 */
function finCodexMissingArticle(string $slug, array $translations = []): Article
{
    $article = Article::factory()->public()->published()->markdown()->create(['slug' => $slug]);

    foreach ($translations as $locale => $fields) {
        finCodexMissingTranslate($article, $locale, $fields);
    }

    return $article;
}

/** The configured display name of one language, as the settings hold it. */
function finCodexMissingDisplay(string $code): string
{
    $names = array_column(TranslationTabs::languages()['languages'], 'display', 'code');

    return $names[$code] ?? $code;
}

/**
 * How many settings queries one whole list render costs. The log is flushed
 * first so a seed written by the row before is never counted.
 */
function finCodexMissingSettingsQueries(): int
{
    DB::flushQueryLog();
    DB::enableQueryLog();

    Livewire::test(ListArticles::class);

    $queries = DB::getQueryLog();

    DB::disableQueryLog();

    return count(array_filter(
        $queries,
        static fn (array $entry): bool => str_contains((string) ($entry['query'] ?? ''), 'settings'),
    ));
}

beforeEach(function (): void {
    finCodexMissingUseLanguages();
    finCodexFakeAi();
    finCodexEnableAi();

    $this->admin = finCodexMissingUser();
    $this->usesPanel('admin', $this->admin);

    // en only: lacks de and hu.
    $this->gap = finCodexMissingArticle('gap', ['en' => []]);
    // en + de: lacks hu alone.
    $this->gapHu = finCodexMissingArticle('gap-hu', ['en' => [], 'de' => []]);
    // Nothing missing at all.
    $this->full = finCodexMissingArticle('full', ['en' => [], 'de' => [], 'hu' => []]);
    // The default language has a body but no title: nothing to translate from.
    $this->blank = finCodexMissingArticle('blank', ['en' => ['title' => '']]);
});

it('shows Translate missing beside Edit on an article that lacks a language and hides it on a full one', function (): void {
    Livewire::test(ListArticles::class)
        ->assertTableActionVisible('translate_missing', $this->gap)
        ->assertTableActionVisible('translate_missing', $this->gapHu)
        ->assertTableActionHidden('translate_missing', $this->full)
        ->assertTableActionExists('edit', record: $this->gap)
        ->assertTableActionsExistInOrder(['edit', 'translate_missing']);
});

it('pre-checks exactly the missing languages, in settings order', function (): void {
    Livewire::test(ListArticles::class)
        ->mountTableAction('translate_missing', $this->gap)
        ->assertTableActionDataSet(['locales' => ['de', 'hu']]);

    Livewire::test(ListArticles::class)
        ->mountTableAction('translate_missing', $this->gapHu)
        ->assertTableActionDataSet(['locales' => ['hu']]);

    // Settings order, not alphabetical: reorder the languages and the pick
    // follows. A fresh component, because the table is built once per mount.
    finCodexMissingUseLanguages(['en', 'hu', 'de']);

    Livewire::test(ListArticles::class)
        ->mountTableAction('translate_missing', $this->gap)
        ->assertTableActionDataSet(['locales' => ['hu', 'de']]);
});

it('lists the missing languages by their configured display names and says the work is queued', function (): void {
    $component = Livewire::test(ListArticles::class)
        ->mountTableAction('translate_missing', $this->gap);

    $action = $component->instance()->getMountedAction();
    $checkboxes = $component->instance()->getSchema('mountedActionSchema0')?->getComponent('locales');

    expect($action)->not->toBeNull()
        ->and($action->getModalDescription())
        ->toBe(__('fin-codex::fin-codex.editor.translate_missing.description'))
        ->and((string) $action->getModalHeading())->toContain('EN gap')
        ->and($checkboxes)->not->toBeNull()
        ->and($checkboxes->getOptions())->toBe([
            'de' => finCodexMissingDisplay('de'),
            'hu' => finCodexMissingDisplay('hu'),
        ]);
});

it('hides it for each unavailable state', function (Closure $state): void {
    $state();

    Livewire::test(ListArticles::class)
        ->assertTableActionHidden('translate_missing', $this->gap)
        ->assertTableActionExists('edit', record: $this->gap);
})->with([
    'sdk_missing' => [fn (): FakeAiClient => finCodexFakeAi(new FakeAiClient(installed: false))],
    'disabled' => [function (): void {
        finCodexFakeAi();
        finCodexEnableAi(['enabled' => false]);
    }],
    'no_key' => [function (): void {
        finCodexFakeAi();
        finCodexEnableAi(['provider' => null]);
    }],
    // Last, because spatie loads the group on the first write: enabling AI on
    // rows that were just deleted throws instead of enabling anything.
    'not_migrated' => [function (): void {
        finCodexFakeAi();
        finCodexAiUnseed();
    }],
]);

it('takes it away from a user the policy refuses', function (): void {
    // The policy is swapped after the mount: a deny-all policy in force from
    // the start 403s the list page itself, so the mount would fail for the
    // wrong reason.
    $component = Livewire::test(ListArticles::class)
        ->assertTableActionVisible('translate_missing', $this->gap);

    Gate::policy(Article::class, DenyAllArticlePolicy::class);

    $component->assertTableActionHidden('translate_missing', $this->gap);
});

it('issues the settings reads once per table build, not per row', function (): void {
    foreach (['row-a', 'row-b', 'row-c', 'row-d'] as $slug) {
        finCodexMissingArticle($slug, ['en' => []]);
    }

    Livewire::test(ListArticles::class)->assertTableActionVisible('translate_missing', $this->gap);

    $on = finCodexMissingSettingsQueries();

    finCodexEnableAi(['enabled' => false]);

    $off = finCodexMissingSettingsQueries();

    // The memoised candidates() costs two settings reads on the first row and
    // nothing after; without lin-codex's memo four extra rows would cost eight.
    expect($on)->toBeLessThanOrEqual($off + 3);
});
