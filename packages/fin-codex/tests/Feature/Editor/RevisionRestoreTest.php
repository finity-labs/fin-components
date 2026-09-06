<?php

use Filament\Actions\Action;
use FinityLabs\FinCodex\Resources\ArticleResource\Pages\EditArticle;
use FinityLabs\FinCodex\Resources\ArticleResource\RelationManagers\RevisionsRelationManager;
use FinityLabs\FinCodex\Tests\Fixtures\User;
use FinityLabs\LinCodex\Enums\ArticleFormat;
use FinityLabs\LinCodex\Models\Article;
use FinityLabs\LinCodex\Models\ArticleRevision;
use FinityLabs\LinCodex\Models\ArticleTranslation;
use Livewire\Features\SupportTesting\Testable;
use Livewire\Livewire;

/*
 * REV-02: reading a past version the way a reader would see it, and putting
 * it back with one confirmed click that is itself undoable.
 *
 * Every row mounts the relation manager directly — it is lazy, so the edit
 * page carries a placeholder and no table markup at all — and drives the row
 * actions with mountTableAction()/callTableAction(), which hand Filament the
 * record key the browser would send.
 *
 * The restore rows never assert against a write of ours: RevisionManager::
 * restore() is the only writer here, and what is proven is that fin-codex
 * hands it the right revision and the right user.
 */

/** A fixture user signed in on the panel guard. */
function finCodexRestoreUser(string $name = 'Restorer', string $email = 'restorer@example.com'): User
{
    return User::create(['name' => $name, 'email' => $email]);
}

/** An article with one complete English translation, the way the editor leaves it. */
function finCodexRestoreArticle(string $slug = 'users', string $title = 'Users'): Article
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
 * pageClass is mandatory: RelationManager::getPageClass() returns a `string`
 * backed by a `?string` property, so a mount without it TypeErrors.
 */
function finCodexRestoreManager(Article $article): Testable
{
    return Livewire::test(RevisionsRelationManager::class, [
        'ownerRecord' => $article,
        'pageClass' => EditArticle::class,
    ]);
}

/** A body using the three lin-codex syntaxes the panel's Markdown editor knows nothing about. */
function finCodexRestoreBody(): string
{
    return "> [!WARNING] Before you delete\n> Their articles stay published.\n\n"
        .":::steps\n1. Open the users page\n\n2. Click **Add**\n:::\n\n"
        .'![Users list](/storage/codex/users.png "The users page")';
}

/** Mount a row action and hand back the mounted instance. */
function finCodexRestoreAction(Testable $component, string $name, ArticleRevision $revision): Action
{
    $component->mountTableAction($name, $revision);

    $action = $component->instance()->getMountedAction();

    expect($action)->toBeInstanceOf(Action::class);

    return $action;
}

/** The rendered modal body of the preview action for one revision. */
function finCodexRestoreModal(Testable $component, ArticleRevision $revision): string
{
    $content = finCodexRestoreAction($component, 'preview', $revision)->getModalContent();

    expect($content)->not->toBeNull();

    return $content->toHtml();
}

it('renders a revision through the core renderer, not through a Markdown parser', function (): void {
    enableRevisions(true);
    $user = finCodexRestoreUser();
    $this->usesPanel('admin', $user);

    $article = finCodexRestoreArticle();
    $revision = ArticleRevision::factory()->create([
        'article_id' => $article->id,
        'locale' => 'en',
        'title' => 'How users work',
        'body' => finCodexRestoreBody(),
        'user_id' => $user->id,
    ]);

    $html = finCodexRestoreModal(finCodexRestoreManager($article), $revision);

    expect($html)
        ->toContain('data-fin-codex-revision-preview')
        ->toContain('codex-root')
        ->toContain('How users work')
        ->toContain('codex-callout codex-callout--warning')
        ->toContain('Before you delete')
        ->toContain('<ol class="codex-steps">')
        ->toContain('codex-step__number')
        ->toContain('<figure class="codex-figure">')
        ->toContain('<figcaption>The users page</figcaption>')
        ->not->toContain('[!WARNING]')
        ->not->toContain(':::steps');
});

it('names the language and the time in the preview heading', function (): void {
    enableRevisions(true);
    $user = finCodexRestoreUser();
    $this->usesPanel('admin', $user);

    $article = finCodexRestoreArticle();
    $revision = ArticleRevision::factory()->create([
        'article_id' => $article->id,
        'locale' => 'de',
        'title' => 'Benutzer',
        'body' => 'Wie Benutzer funktionieren.',
        'user_id' => $user->id,
    ]);

    $heading = finCodexRestoreAction(finCodexRestoreManager($article), 'preview', $revision)->getModalHeading();

    expect((string) $heading)
        ->toContain('DE')
        ->toContain($revision->created_at->toDayDateTimeString());
});

it('binds the theme wrapper like the drawer', function (): void {
    enableRevisions(true);
    $user = finCodexRestoreUser();
    $this->usesPanel('admin', $user);

    $article = finCodexRestoreArticle();
    $revision = ArticleRevision::factory()->create([
        'article_id' => $article->id,
        'locale' => 'en',
        'title' => 'Users',
        'body' => 'How users work.',
        'user_id' => $user->id,
    ]);

    expect(finCodexRestoreModal(finCodexRestoreManager($article), $revision))
        ->toContain('x-bind:class="{ light: $store.theme === \'light\' }"')
        ->not->toContain('class="light"');

    // The portal panel has darkMode(false), so the wrapper is light before
    // Alpine ever runs.
    $this->usesPanel('portal', finCodexRestoreUser('Portal', 'portal@example.com'));

    expect(finCodexRestoreModal(finCodexRestoreManager($article), $revision))
        ->toContain('class="light"')
        ->toContain('x-bind:class="{ light: $store.theme === \'light\' }"');
});

it('renders an HTML revision through the sanitizer even when the article is now Markdown', function (): void {
    enableRevisions(true);
    $user = finCodexRestoreUser();
    $this->usesPanel('admin', $user);

    // The article is Markdown today; the revision remembers the format its
    // body was written in, and that is the one the preview must use.
    $article = finCodexRestoreArticle();
    expect($article->format)->toBe(ArticleFormat::Markdown);

    $revision = ArticleRevision::factory()->create([
        'article_id' => $article->id,
        'locale' => 'en',
        'title' => 'Legacy',
        'body' => '<h2>Hi</h2><script>alert(1)</script><p>x</p>',
        'format' => ArticleFormat::Html,
        'user_id' => $user->id,
    ]);

    expect(finCodexRestoreModal(finCodexRestoreManager($article), $revision))
        ->toContain('<h2 id="hi"')
        ->toContain('Hi')
        ->not->toContain('<script')
        ->not->toContain('alert(1)');
});

it('writes nothing when a revision is previewed', function (): void {
    enableRevisions(true);
    $user = finCodexRestoreUser();
    $this->usesPanel('admin', $user);

    $article = finCodexRestoreArticle();
    $revision = ArticleRevision::factory()->create([
        'article_id' => $article->id,
        'locale' => 'en',
        'title' => 'An older title',
        'body' => 'An older body.',
        'user_id' => $user->id,
    ]);

    finCodexRestoreModal(finCodexRestoreManager($article), $revision);

    $translation = ArticleTranslation::query()->where('article_id', $article->id)->where('locale', 'en')->sole();

    expect(ArticleRevision::query()->count())->toBe(1)
        ->and($translation->title)->toBe('Users')
        ->and($translation->body)->toBe('Users body');
});
