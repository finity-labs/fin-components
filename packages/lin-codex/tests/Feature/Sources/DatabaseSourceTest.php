<?php

declare(strict_types=1);

use FinityLabs\LinCodex\Data\ArticleData;
use FinityLabs\LinCodex\Data\ContextData;
use FinityLabs\LinCodex\Data\SearchDocument;
use FinityLabs\LinCodex\Data\TreeNode;
use FinityLabs\LinCodex\Enums\ArticleFormat;
use FinityLabs\LinCodex\Enums\ContextType;
use FinityLabs\LinCodex\Enums\Visibility;
use FinityLabs\LinCodex\Models\Article;
use FinityLabs\LinCodex\Models\ArticleTranslation;
use FinityLabs\LinCodex\Sources\DatabaseSource;

/**
 * The "users" article with two translations, two contexts and one child.
 */
function linCodexSeedUsers(): Article
{
    $users = Article::factory()
        ->public()
        ->html()
        ->unpublished()
        ->state([
            'slug' => 'users',
            'sort_order' => 2,
            'icon' => 'heroicon-o-users',
            'source_path' => 'en/02-users/index.md',
        ])
        ->withTranslation('en', [
            'title' => 'Users',
            'excerpt' => 'People',
            'body' => '<h1>x</h1>',
            'search_text' => 'users people',
        ])
        ->withTranslation('de', ['title' => 'Benutzer', 'search_text' => null])
        ->withContext(ContextType::Route, 'users.index', null, 1)
        ->withContext(ContextType::PageClass, 'App\Filament\Resources\UserResource', 'admin', 0)
        ->create();

    Article::factory()->childOf($users, 'roles')->withTranslation('en', ['title' => 'Roles'])->create();

    return $users;
}

/**
 * @param  list<ArticleData>  $articles
 *
 * @return list<string>
 */
function linCodexSlugsOf(array $articles): array
{
    return array_map(fn (ArticleData $article): string => $article->slug, $articles);
}

it('returns empty results from empty tables', function (): void {
    $source = new DatabaseSource;

    expect($source->all())->toBe([])
        ->and($source->tree())->toBe([])
        ->and($source->allForSearch())->toBe([])
        ->and($source->warnings())->toBe([]);
});

it('maps a row with its translations and contexts to article data', function (): void {
    $users = linCodexSeedUsers();

    $article = (new DatabaseSource)->findBySlug('users');

    expect($article)->toBeInstanceOf(ArticleData::class)
        ->and($article->id)->toBe($users->id)
        ->and($article->slug)->toBe('users')
        ->and($article->parentSlug)->toBeNull()
        ->and($article->order)->toBe(2)
        ->and($article->icon)->toBe('heroicon-o-users')
        ->and($article->format)->toBe(ArticleFormat::Html)
        ->and($article->visibility)->toBe(Visibility::Public)
        ->and($article->published)->toBeFalse()
        ->and($article->isSection)->toBeTrue()
        ->and($article->sourcePath)->toBe('en/02-users/index.md')
        ->and($article->related)->toBe([])
        ->and($article->keywords)->toBe([])
        ->and($article->meta)->toBe([]);

    $locales = $article->locales();
    sort($locales);

    expect($locales)->toBe(['de', 'en'])
        ->and($article->translation('en')->title)->toBe('Users')
        ->and($article->translation('en')->excerpt)->toBe('People')
        ->and($article->translation('en')->body)->toBe('<h1>x</h1>')
        ->and($article->translation('en')->searchText)->toBe('users people')
        ->and($article->translation('en')->sourcePath)->toBeNull()
        ->and($article->translation('de')->title)->toBe('Benutzer')
        ->and($article->translation('de')->searchText)->toBeNull()
        ->and($article->contexts)->toEqual([
            new ContextData(ContextType::PageClass, 'App\Filament\Resources\UserResource', 'admin', 0),
            new ContextData(ContextType::Route, 'users.index', null, 1),
        ]);
});

it('derives the parent slug from the slug and marks leaves as non-sections', function (): void {
    linCodexSeedUsers();

    $source = new DatabaseSource;

    expect($source->findBySlug('users/roles')->parentSlug)->toBe('users')
        ->and($source->findBySlug('users/roles')->isSection)->toBeFalse()
        ->and($source->findBySlug('nope'))->toBeNull();
});

it('builds the tree with orphans at the root', function (): void {
    linCodexSeedUsers();
    Article::factory()->state(['slug' => 'orphan/child'])->withTranslation('en')->create();

    $roots = (new DatabaseSource)->tree();

    expect(array_map(fn (TreeNode $node): string => $node->slug, $roots))->toBe(['orphan/child', 'users']);

    [$orphan, $users] = $roots;

    expect($orphan->children)->toBe([])
        ->and(array_map(fn (TreeNode $node): string => $node->slug, $users->children))->toBe(['users/roles']);
});

it('finds articles by exact context including the panel', function (): void {
    linCodexSeedUsers();

    $source = new DatabaseSource;

    expect(linCodexSlugsOf($source->findByContext(ContextType::Route, 'users.index')))->toBe(['users'])
        ->and($source->findByContext(ContextType::Route, 'users.index', 'admin'))->toBe([])
        ->and(linCodexSlugsOf($source->findByContext(ContextType::PageClass, 'App\Filament\Resources\UserResource', 'admin')))->toBe(['users']);
});

it('emits one search document per translation ordered by slug then locale', function (): void {
    linCodexSeedUsers();

    $documents = (new DatabaseSource)->allForSearch();

    expect($documents)->toHaveCount(3)
        ->and(array_map(fn (SearchDocument $document): string => $document->slug.'#'.$document->locale, $documents))
        ->toBe(['users#de', 'users#en', 'users/roles#en'])
        ->and($documents[1]->text)->toBe('users people')
        ->and($documents[1]->title)->toBe('Users')
        ->and($documents[1]->visibility)->toBe(Visibility::Public)
        ->and($documents[1]->published)->toBeFalse();
});

it('reads fresh rows on every call instead of memoizing', function (): void {
    $users = linCodexSeedUsers();
    $source = new DatabaseSource;

    expect($source->all()['users']->translation('fr'))->toBeNull();

    ArticleTranslation::factory()->create(['article_id' => $users->id, 'locale' => 'fr', 'title' => 'Utilisateurs']);

    expect($source->all()['users']->translation('fr')->title)->toBe('Utilisateurs');
});

it('never hands out an Eloquent model and survives a serialize round trip', function (): void {
    linCodexSeedUsers();
    $source = new DatabaseSource;

    linCodexAssertNoModels($source->all());

    expect(unserialize(serialize($source->set())))->toEqual($source->set());
});
