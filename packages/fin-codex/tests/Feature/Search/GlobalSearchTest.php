<?php

use Filament\Facades\Filament;
use Filament\GlobalSearch\GlobalSearchResult;
use Filament\GlobalSearch\GlobalSearchResults;
use FinityLabs\FinCodex\Resources\ArticleResource;
use FinityLabs\FinCodex\Tests\Fixtures\Resources\AdminHelpArticleResource;
use FinityLabs\FinCodex\Tests\Fixtures\User;
use FinityLabs\LinCodex\Models\Article;

/*
 * GS-01 and GS-02. Help reaches the panel's own search field through one
 * path only — the plugin's wrapper around whatever provider the panel had,
 * asking lin-codex's Searcher, which gates before it reads. The Eloquent
 * path the resource would otherwise open is closed here, so "global search
 * off" genuinely means no help results at all.
 */

function finCodexSearchUser(string $email = 'searcher@example.test'): User
{
    return User::create(['name' => 'Searcher', 'email' => $email]);
}

/**
 * Three articles whose slugs and titles all match "users": a public one, an
 * unpublished one and an Authenticated-visibility one. The last two are the
 * rows that light up if anything queries the model instead of the gate.
 */
function finCodexSearchSeedUsers(): void
{
    Article::factory()->public()->published()
        ->withTranslation('en', ['title' => 'Users overview', 'body' => 'How users work in this panel.'])
        ->create(['slug' => 'users-overview']);

    Article::factory()->public()->unpublished()
        ->withTranslation('en', ['title' => 'Users draft', 'body' => 'An unfinished note about users.'])
        ->create(['slug' => 'users-draft']);

    Article::factory()->authenticated()->published()
        ->withTranslation('en', ['title' => 'Users internal', 'body' => 'Internal guidance about users.'])
        ->create(['slug' => 'users-internal']);
}

/**
 * The category names in insertion order.
 *
 * @return list<string>
 */
function finCodexSearchCategoryNames(?GlobalSearchResults $results): array
{
    return $results === null ? [] : array_map(strval(...), $results->getCategories()->keys()->all());
}

/**
 * Every result of every category, flattened.
 *
 * @return list<GlobalSearchResult>
 */
function finCodexSearchAllResults(?GlobalSearchResults $results): array
{
    $flat = [];

    foreach (($results?->getCategories() ?? []) as $category) {
        foreach ($category as $result) {
            $flat[] = $result;
        }
    }

    return $flat;
}

/**
 * One category's results as a list.
 *
 * @return list<GlobalSearchResult>
 */
function finCodexSearchCategory(?GlobalSearchResults $results, string $name): array
{
    $category = $results?->getCategories()->get($name);

    return $category === null ? [] : array_values(is_array($category) ? $category : $category->all());
}

it('never offers the article resource to a default global search provider', function (): void {
    $this->usesPanel('admin', finCodexSearchUser());

    expect(ArticleResource::canGloballySearch())->toBeFalse()
        ->and(AdminHelpArticleResource::canGloballySearch())->toBeFalse();
});

/*
 * The admin panel's plugin has globalSearch(false), so nothing of ours runs
 * here at all. What the panel's own provider answers must therefore contain
 * no help whatsoever — not the resource's plural label as a category, and no
 * row linking at the editor.
 */
it('produces no help category on a panel with the option off', function (): void {
    finCodexSearchSeedUsers();

    $this->usesPanel('admin', finCodexSearchUser());

    $results = Filament::getGlobalSearchProvider()?->getResults('users');

    expect(finCodexSearchCategoryNames($results))
        ->not->toContain(AdminHelpArticleResource::getPluralModelLabel())
        ->not->toContain((string) __('fin-codex::fin-codex.search.category'));

    foreach (finCodexSearchAllResults($results) as $result) {
        expect($result->url)->not->toContain('/admin/help-articles');
    }
});

/*
 * The same fact framed as the leak it would be: an Authenticated article
 * seeded while nobody is signed in is invisible to the panel's search.
 */
it('never exposes an authenticated article through the panel search to a guest', function (): void {
    finCodexSearchSeedUsers();

    $this->usesPanel('admin');

    expect(auth('web')->check())->toBeFalse();

    $titles = array_map(
        static fn (GlobalSearchResult $result): string => (string) $result->title,
        finCodexSearchAllResults(Filament::getGlobalSearchProvider()?->getResults('users')),
    );

    expect($titles)->not->toContain('users-internal')
        ->not->toContain('Users internal')
        ->not->toContain('users-draft')
        ->not->toContain('Users draft')
        ->not->toContain('users-overview');
});
