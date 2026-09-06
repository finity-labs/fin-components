<?php

use FinityLabs\FinCodex\Editor\ArticleWriter;
use FinityLabs\FinCodex\Tests\Fixtures\Resources\UserResource;
use FinityLabs\FinCodex\Tests\Fixtures\User;
use FinityLabs\LinCodex\Enums\ArticleFormat;
use FinityLabs\LinCodex\Enums\ContextType;
use FinityLabs\LinCodex\Enums\RevisionReason;
use FinityLabs\LinCodex\Enums\Visibility;
use FinityLabs\LinCodex\Models\Article;
use FinityLabs\LinCodex\Models\ArticleContext;
use FinityLabs\LinCodex\Models\ArticleRevision;
use FinityLabs\LinCodex\Models\ArticleTranslation;
use FinityLabs\LinCodex\Settings\CodexSettings;
use Illuminate\Database\Eloquent\MassAssignmentException;
use Illuminate\Database\Eloquent\Model;

/*
 * ArticleWriter is the one write path of the editor: article, translations
 * and contexts in one transaction under RevisionManager::attributing() with
 * the panel user. These rows prove the create and update shapes at the model
 * level, under the strict models the whole suite runs with.
 */

function finCodexWriter(): ArticleWriter
{
    return app(ArticleWriter::class);
}

function finCodexWriterUser(string $name = 'Author'): User
{
    return User::create(['name' => $name, 'email' => strtolower($name).'@example.com']);
}

/**
 * The full create shape with $overrides merged in (array_replace_recursive,
 * so a nested translation key can be changed on its own).
 *
 * @param  array<string, mixed>  $overrides
 *
 * @return array<string, mixed>
 */
function finCodexWriterData(array $overrides = []): array
{
    return array_replace_recursive([
        'slug' => 'users',
        'icon' => 'heroicon-o-users',
        'sort_order' => 3,
        'format' => ArticleFormat::Markdown->value,
        'visibility' => Visibility::Public->value,
        'is_published' => true,
        'keywords' => ['people', 'accounts'],
        'related' => ['billing'],
        'translations' => [
            'en' => ['title' => 'Users', 'excerpt' => 'Manage users.', 'body' => 'How users work.'],
            'de' => ['title' => null, 'excerpt' => null, 'body' => null],
        ],
        'contexts' => [
            ['panel_id' => 'admin', 'type' => 'class', 'key' => UserResource::class],
            ['panel_id' => '*', 'type' => 'url', 'key' => '/admin/*'],
        ],
    ], $overrides);
}

it('creates the article, its complete translations and its contexts in order', function (): void {
    $user = finCodexWriterUser();

    $article = finCodexWriter()->create(finCodexWriterData(), $user->id)->fresh();

    expect($article->slug)->toBe('users')
        ->and($article->icon)->toBe('heroicon-o-users')
        ->and($article->sort_order)->toBe(3)
        ->and($article->format)->toBe(ArticleFormat::Markdown)
        ->and($article->visibility)->toBe(Visibility::Public)
        ->and($article->is_published)->toBeTrue()
        ->and($article->keywords)->toBe(['people', 'accounts'])
        ->and($article->related)->toBe(['billing'])
        ->and($article->created_by)->toBe($user->id)
        ->and($article->updated_by)->toBe($user->id)
        ->and($article->parent_id)->toBeNull();

    $translations = ArticleTranslation::query()->where('article_id', $article->id)->get();

    expect($translations)->toHaveCount(1)
        ->and($translations[0]->locale)->toBe('en')
        ->and($translations[0]->title)->toBe('Users')
        ->and($translations[0]->excerpt)->toBe('Manage users.')
        ->and($translations[0]->body)->toBe('How users work.')
        ->and($translations[0]->search_text)->not->toBeNull()
        ->and($translations[0]->search_text)->toContain('users')
        ->and($translations[0]->search_text)->toContain('people');

    $contexts = ArticleContext::query()->where('article_id', $article->id)->orderBy('sort_order')->get();

    expect($contexts)->toHaveCount(2)
        ->and($contexts[0]->sort_order)->toBe(0)
        ->and($contexts[0]->panel_id)->toBe('admin')
        ->and($contexts[0]->type)->toBe(ContextType::PageClass)
        ->and($contexts[0]->key)->toBe(UserResource::class)
        ->and($contexts[1]->sort_order)->toBe(1)
        ->and($contexts[1]->panel_id)->toBeNull()
        ->and($contexts[1]->type)->toBe(ContextType::Url)
        ->and($contexts[1]->key)->toBe('/admin/*')
        ->and(ArticleRevision::query()->count())->toBe(0);
});

it('accepts enum cases as well as backing values', function (): void {
    $article = finCodexWriter()->create(finCodexWriterData([
        'format' => ArticleFormat::Markdown,
        'visibility' => Visibility::Authenticated,
    ]), finCodexWriterUser()->id)->fresh();

    expect($article->format)->toBe(ArticleFormat::Markdown)
        ->and($article->visibility)->toBe(Visibility::Authenticated);
});

it('ignores attributes outside the whitelist', function (): void {
    $user = finCodexWriterUser();

    $article = finCodexWriter()->create(finCodexWriterData([
        'parent_id' => 999,
        'meta' => ['x' => 1],
        'source_path' => 'hack.md',
        'created_by' => 77,
    ]), $user->id)->fresh();

    expect($article->parent_id)->toBeNull()
        ->and(blank($article->meta))->toBeTrue()
        ->and($article->source_path)->toBeNull()
        ->and($article->created_by)->toBe($user->id);
});

it('updates attributes, replaces contexts in the new order and records the revision with the user id', function (): void {
    enableRevisions(true);
    $alice = finCodexWriterUser('Alice');
    $bob = finCodexWriterUser('Bob');
    $article = finCodexWriter()->create(finCodexWriterData(), $alice->id);
    $oldContextIds = ArticleContext::query()->where('article_id', $article->id)->pluck('id')->all();

    $data = finCodexWriterData([
        'translations' => ['en' => ['title' => 'Users v2', 'body' => 'v2 body']],
    ]);
    $data['keywords'] = ['x'];
    $data['contexts'] = [
        ['panel_id' => '*', 'type' => 'url', 'key' => '/admin/*'],
        ['panel_id' => 'admin', 'type' => 'class', 'key' => UserResource::class],
        ['panel_id' => 'staff', 'type' => 'route', 'key' => 'filament.staff.pages.dashboard'],
    ];

    $article = finCodexWriter()->update($article, $data, $bob->id)->fresh();

    expect($article->updated_by)->toBe($bob->id)
        ->and($article->created_by)->toBe($alice->id)
        ->and($article->keywords)->toBe(['x']);

    $revisions = ArticleRevision::query()->where('article_id', $article->id)->get();

    expect($revisions)->toHaveCount(1)
        ->and($revisions[0]->locale)->toBe('en')
        ->and($revisions[0]->title)->toBe('Users')
        ->and($revisions[0]->body)->toBe('How users work.')
        ->and($revisions[0]->reason)->toBe(RevisionReason::Manual)
        ->and($revisions[0]->user_id)->toBe($bob->id)
        ->and($revisions[0]->format)->toBe(ArticleFormat::Markdown);

    $contexts = ArticleContext::query()->where('article_id', $article->id)->orderBy('sort_order')->get();

    expect($contexts->map(fn (ArticleContext $context): array => [$context->panel_id, $context->type, $context->key])->all())->toBe([
        [null, ContextType::Url, '/admin/*'],
        ['admin', ContextType::PageClass, UserResource::class],
        ['staff', ContextType::Route, 'filament.staff.pages.dashboard'],
    ])
        ->and($contexts->pluck('sort_order')->all())->toBe([0, 1, 2])
        ->and($contexts->pluck('id')->intersect($oldContextIds)->all())->toBe([]);

    $searchText = (string) ArticleTranslation::query()->where('article_id', $article->id)->where('locale', 'en')->value('search_text');

    expect($searchText)->toContain("\n x\n")
        ->and($searchText)->toContain('users v2')
        ->and($searchText)->not->toContain('people');
});

it('saves a newly complete tab and deletes an emptied non-default tab', function (): void {
    enableRevisions(true);
    $user = finCodexWriterUser();
    $article = finCodexWriter()->create(finCodexWriterData(), $user->id);

    finCodexWriter()->update($article, finCodexWriterData([
        'translations' => ['de' => ['title' => 'Benutzer', 'body' => 'So funktionieren Benutzer.']],
    ]), $user->id);

    $translations = ArticleTranslation::query()->where('article_id', $article->id)->orderBy('locale')->get();

    expect($translations->pluck('locale')->all())->toBe(['de', 'en'])
        ->and($translations[0]->title)->toBe('Benutzer')
        ->and($translations[0]->body)->toBe('So funktionieren Benutzer.');

    $enBefore = $translations[1]->updated_at;
    $this->travel(5)->seconds();

    finCodexWriter()->update($article, finCodexWriterData([
        'translations' => ['de' => ['title' => '', 'body' => '']],
    ]), $user->id);

    $translations = ArticleTranslation::query()->where('article_id', $article->id)->get();

    expect($translations->pluck('locale')->all())->toBe(['en'])
        ->and($translations[0]->updated_at->equalTo($enBefore))->toBeTrue()
        ->and(ArticleRevision::query()->count())->toBe(0);
});

it('keeps an existing body when the tab carries no body key', function (): void {
    $user = finCodexWriterUser();
    $article = finCodexWriter()->create(finCodexWriterData(), $user->id);
    $before = ArticleTranslation::query()->where('article_id', $article->id)->firstOrFail()->updated_at;
    $this->travel(5)->seconds();

    $data = finCodexWriterData();
    $data['translations']['en'] = ['title' => 'Users', 'excerpt' => 'New excerpt'];

    finCodexWriter()->update($article, $data, $user->id);

    $en = ArticleTranslation::query()->where('article_id', $article->id)->firstOrFail();

    expect($en->body)->toBe('How users work.')
        ->and($en->excerpt)->toBe('New excerpt')
        ->and($en->updated_at->greaterThan($before))->toBeTrue();
});

it('refuses an incomplete default tab', function (): void {
    $user = finCodexWriterUser();

    expect(fn () => finCodexWriter()->create(finCodexWriterData(['translations' => ['en' => ['title' => '']]]), $user->id))
        ->toThrow(InvalidArgumentException::class)
        ->and(Article::query()->count())->toBe(0);

    $article = finCodexWriter()->create(finCodexWriterData(), $user->id);

    expect(fn () => finCodexWriter()->update($article, finCodexWriterData([
        'icon' => 'heroicon-o-x-mark',
        'translations' => ['en' => ['body' => null]],
    ]), $user->id))->toThrow(InvalidArgumentException::class);

    $fresh = $article->fresh();
    $en = ArticleTranslation::query()->where('article_id', $article->id)->firstOrFail();

    expect($fresh->icon)->toBe('heroicon-o-users')
        ->and($en->title)->toBe('Users')
        ->and($en->body)->toBe('How users work.');
});

it('writes nothing when any part fails', function (): void {
    $data = finCodexWriterData();
    $data['contexts'][1]['type'] = 'bogus';

    expect(fn () => finCodexWriter()->create($data, finCodexWriterUser()->id))->toThrow(ValueError::class)
        ->and(Article::query()->count())->toBe(0)
        ->and(ArticleTranslation::query()->count())->toBe(0)
        ->and(ArticleContext::query()->count())->toBe(0);
});

it('runs under strict models', function (): void {
    expect(Model::preventsSilentlyDiscardingAttributes())->toBeTrue()
        ->and(Model::preventsLazyLoading())->toBeTrue()
        ->and(Model::preventsAccessingMissingAttributes())->toBeTrue()
        ->and(fn () => (new Article)->fill(['translations' => []]))->toThrow(MassAssignmentException::class);
});

it('resolves the default locale from settings', function (): void {
    $settings = app(CodexSettings::class);
    $settings->languages = array_map([CodexSettings::class, 'languageEntry'], ['de', 'en']);
    $settings->default_locale = 'de';
    $settings->save();
    $user = finCodexWriterUser();

    $article = finCodexWriter()->create(finCodexWriterData(['translations' => [
        'de' => ['title' => 'Benutzer', 'excerpt' => null, 'body' => 'So funktionieren Benutzer.'],
        'en' => ['title' => null, 'excerpt' => null, 'body' => null],
    ]]), $user->id);

    expect(ArticleTranslation::query()->where('article_id', $article->id)->pluck('locale')->all())->toBe(['de']);

    expect(fn () => finCodexWriter()->create(finCodexWriterData([
        'slug' => 'roles',
        'translations' => ['de' => ['title' => null, 'excerpt' => null, 'body' => null]],
    ]), $user->id))->toThrow(InvalidArgumentException::class)
        ->and(Article::query()->count())->toBe(1);
});
