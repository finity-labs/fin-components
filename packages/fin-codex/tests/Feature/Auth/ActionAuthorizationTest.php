<?php

use FinityLabs\FinCodex\Resources\ArticleResource\Pages\EditArticle;
use FinityLabs\FinCodex\Resources\ArticleResource\RelationManagers\RevisionsRelationManager;
use FinityLabs\FinCodex\Tests\Fixtures\Policies\DenyAllArticlePolicy;
use FinityLabs\FinCodex\Tests\Fixtures\User;
use FinityLabs\LinCodex\Enums\ArticleFormat;
use FinityLabs\LinCodex\Models\Article;
use FinityLabs\LinCodex\Models\ArticleRevision;
use FinityLabs\LinCodex\Models\ArticleTranslation;
use Illuminate\Contracts\Auth\Authenticatable;
use Illuminate\Support\Facades\Gate;
use Livewire\Features\SupportTesting\Testable;
use Livewire\Livewire;

/*
 * AUTH-01's second half: the actions Filament does not gate for us.
 *
 * Filament's own comment in CanBeAuthorized is explicit — "Actions do not have
 * automatic policy-based authorization" — so revision restore, HTML conversion
 * and the two file-article imports are open to every panel user until an
 * ->authorize() lands on them. Each row here therefore comes in a pair: the
 * button is there for a user the policy allows, and gone for one it refuses.
 * An "it is visible" row on its own would pass just as well with no check at
 * all.
 *
 * Every gate is the Closure form. Filament unshifts the action's OWN record as
 * the gate subject, so ->authorize('restore') inside the revisions manager
 * would ask about an ArticleRevision — a model with no policy anywhere in this
 * package — and the button would vanish for everybody. The row named for it is
 * the one that reddens if somebody ever shortens the closure to a string.
 *
 * The deny rows swap the policy AFTER the component is mounted. That is not a
 * convenience: with a deny-all policy in force from the start, Filament's own
 * resource gate 403s the edit page before any action is built, so the mount
 * would fail for the wrong reason. Swapping mid-flight is also the realistic
 * story — a permission revoked while the admin has the page open.
 */

/**
 * The policy a host really writes: the five standard abilities, granted, and
 * not one word of our vocabulary. restore and convert must survive it through
 * ArticleAbility's update fallback, and import through the create one.
 */
class FinCodexGatedStandardPolicy
{
    public function viewAny(Authenticatable $user): bool
    {
        return true;
    }

    public function view(Authenticatable $user, Article $article): bool
    {
        return true;
    }

    public function create(Authenticatable $user): bool
    {
        return true;
    }

    public function update(Authenticatable $user, Article $article): bool
    {
        return true;
    }

    public function delete(Authenticatable $user, Article $article): bool
    {
        return true;
    }
}

/**
 * A host that lets this user write new articles but not touch existing ones.
 *
 * It is the only shape that reaches the coverage page's attach re-check: the
 * button asks `create` at class level, because the article to be changed does
 * not exist until the modal's Select is submitted.
 */
class FinCodexGatedNoUpdatePolicy extends FinCodexGatedStandardPolicy
{
    public function update(Authenticatable $user, Article $article): bool
    {
        return false;
    }
}

/** A fixture user signed in on the admin panel's guard. */
function finCodexGatedUser(string $email = 'gated@example.com'): User
{
    $user = User::create(['name' => 'Gated', 'email' => $email]);

    test()->usesPanel('admin', $user);

    return $user;
}

/** An article with one complete English translation, the way the editor leaves it. */
function finCodexGatedArticle(string $slug = 'users', string $title = 'Users'): Article
{
    $article = Article::factory()->create(['slug' => $slug]);

    ArticleTranslation::factory()->create([
        'article_id' => $article->id,
        'locale' => 'en',
        'title' => $title,
        'body' => $title.' body',
    ]);

    return $article;
}

/**
 * Rewrite the English translation, which is what makes the core record a
 * revision of the text that was there before. Nothing hand-writes one.
 */
function finCodexGatedRewrite(Article $article, string $title): ArticleRevision
{
    ArticleTranslation::query()
        ->where('article_id', $article->id)
        ->where('locale', 'en')
        ->sole()
        ->fill(['title' => $title, 'body' => $title.' body'])
        ->save();

    return ArticleRevision::query()
        ->where('article_id', $article->id)
        ->orderByDesc('id')
        ->sole();
}

/**
 * The revisions tab, mounted on its own. pageClass is mandatory:
 * RelationManager::getPageClass() returns a string backed by a ?string
 * property, so a mount without it TypeErrors.
 */
function finCodexGatedManager(Article $article): Testable
{
    return Livewire::test(RevisionsRelationManager::class, [
        'ownerRecord' => $article,
        'pageClass' => EditArticle::class,
    ]);
}

/** A published, public HTML article — the only kind convert offers itself on. */
function finCodexGatedHtmlArticle(string $slug = 'legacy'): Article
{
    return Article::factory()->html()->public()->published()
        ->withTranslation('en', ['title' => 'Hi', 'body' => '<h2>Hi</h2><p>Text.</p>'])
        ->create(['slug' => $slug])
        ->fresh();
}

/** Register a policy for the article model for the rest of the test. */
function finCodexGatedPolicy(string $policy): void
{
    Gate::policy(Article::class, $policy);
}

/** The English title and body as they stand in the database. */
function finCodexGatedText(Article $article): array
{
    $translation = ArticleTranslation::query()
        ->where('article_id', $article->id)
        ->where('locale', 'en')
        ->sole();

    return [$translation->title, $translation->body];
}

/*
 * -----------------------------------------------------------------------
 * Revision restore.
 * -----------------------------------------------------------------------
 */

it('offers the revision restore to a user the shipped policy allows', function (): void {
    enableRevisions(true);
    finCodexGatedUser();

    $article = finCodexGatedArticle('users', 'v1');
    $revision = finCodexGatedRewrite($article, 'v2');

    finCodexGatedManager($article)->assertTableActionVisible('restore', $revision);
});

it('takes the revision restore away from a user the policy refuses', function (): void {
    enableRevisions(true);
    finCodexGatedUser();

    $article = finCodexGatedArticle('users', 'v1');
    $revision = finCodexGatedRewrite($article, 'v2');

    $manager = finCodexGatedManager($article);

    finCodexGatedPolicy(DenyAllArticlePolicy::class);

    $manager->assertTableActionHidden('restore', $revision);
});

it('writes no revision when a refused restore is called anyway', function (): void {
    enableRevisions(true);
    finCodexGatedUser();

    $article = finCodexGatedArticle('users', 'v1');
    $revision = finCodexGatedRewrite($article, 'v2');
    $revisions = ArticleRevision::query()->count();

    $manager = finCodexGatedManager($article);

    finCodexGatedPolicy(DenyAllArticlePolicy::class);

    // Not callTableAction(): that asserts the action is visible first, which is
    // the thing being denied. This is the forged press — mount, then call —
    // and Filament answers it by unmounting and doing nothing.
    $manager->mountTableAction('restore', $revision)->callMountedTableAction();

    expect(finCodexGatedText($article))->toBe(['v2', 'v2 body'])
        ->and(ArticleRevision::query()->count())->toBe($revisions);
});

it('keeps the revision restore for a host policy that only knows update', function (): void {
    enableRevisions(true);
    finCodexGatedUser();

    $article = finCodexGatedArticle('users', 'v1');
    $revision = finCodexGatedRewrite($article, 'v2');

    finCodexGatedPolicy(FinCodexGatedStandardPolicy::class);

    finCodexGatedManager($article)
        ->assertTableActionVisible('restore', $revision)
        ->callTableAction('restore', $revision)
        ->assertHasNoTableActionErrors();

    expect(finCodexGatedText($article))->toBe(['v1', 'v1 body']);
});

/*
 * The row that reddens the moment somebody shortens the closure to
 * ->authorize('restore'): Filament would then ask the Gate about the
 * ArticleRevision the row carries, and this package registers no policy for
 * that model at all.
 */
it('asks about the article, not about the revision row it is standing on', function (): void {
    enableRevisions(true);
    finCodexGatedUser();

    $article = finCodexGatedArticle('users', 'v1');
    $revision = finCodexGatedRewrite($article, 'v2');

    expect(Gate::getPolicyFor(ArticleRevision::class))->toBeNull()
        ->and(Gate::getPolicyFor(Article::class))->not->toBeNull();

    finCodexGatedManager($article)->assertTableActionVisible('restore', $revision);
});

/*
 * -----------------------------------------------------------------------
 * HTML conversion.
 * -----------------------------------------------------------------------
 */

it('offers the conversion to a user the shipped policy allows', function (): void {
    finCodexGatedUser();

    $article = finCodexGatedHtmlArticle();

    Livewire::test(EditArticle::class, ['record' => $article->getRouteKey()])
        ->assertActionVisible('convert');
});

it('takes the conversion away from a user the policy refuses', function (): void {
    finCodexGatedUser();

    $article = finCodexGatedHtmlArticle();

    $page = Livewire::test(EditArticle::class, ['record' => $article->getRouteKey()]);

    finCodexGatedPolicy(DenyAllArticlePolicy::class);

    $page->assertActionHidden('convert');
});

it('converts nothing when a refused conversion is called anyway', function (): void {
    finCodexGatedUser();

    $article = finCodexGatedHtmlArticle();

    // Mounted while the modal was still legitimately reachable, so the press
    // below is the forged one.
    $page = Livewire::test(EditArticle::class, ['record' => $article->getRouteKey()])
        ->mountAction('convert');

    finCodexGatedPolicy(DenyAllArticlePolicy::class);

    // Straight onto the component already in memory, deliberately. A fresh
    // Livewire request would 403 on EditRecord::authorizeAccess() before any
    // action was built, and prove the resource gate rather than this one.
    $page->instance()->callMountedAction();

    expect($article->fresh()->format)->toBe(ArticleFormat::Html)
        ->and(ArticleRevision::query()->count())->toBe(0);
});

it('keeps the conversion for a host policy that only knows update', function (): void {
    finCodexGatedUser();

    $article = finCodexGatedHtmlArticle();

    finCodexGatedPolicy(FinCodexGatedStandardPolicy::class);

    Livewire::test(EditArticle::class, ['record' => $article->getRouteKey()])
        ->assertActionVisible('convert');
});

/*
 * The format check and the ability check compose: Filament ANDs every reason
 * an action can be hidden, so a Markdown article stays without a convert
 * button however permissive the policy is.
 */
it('still hides the conversion on a Markdown article the policy allows', function (): void {
    finCodexGatedUser();

    $article = Article::factory()->public()->published()
        ->withTranslation('en', ['title' => 'Users', 'body' => 'How users work.'])
        ->create(['slug' => 'users']);

    Livewire::test(EditArticle::class, ['record' => $article->getRouteKey()])
        ->assertActionHidden('convert');
});
