<?php

use Filament\Pages\Dashboard;
use FinityLabs\FinCodex\Policies\ArticlePolicy;
use FinityLabs\FinCodex\Scope\PanelScopeGate;
use FinityLabs\FinCodex\Tests\Fixtures\Policies\DenyAllArticlePolicy;
use FinityLabs\FinCodex\Tests\Fixtures\Policies\ViewAllPanelsArticlePolicy;
use FinityLabs\FinCodex\Tests\Fixtures\User;
use FinityLabs\LinCodex\Auth\ArticleGate;
use FinityLabs\LinCodex\Auth\Viewer;
use FinityLabs\LinCodex\Contracts\ContentSource;
use FinityLabs\LinCodex\Data\ArticleData;
use FinityLabs\LinCodex\Data\ContextData;
use FinityLabs\LinCodex\Enums\ArticleFormat;
use FinityLabs\LinCodex\Enums\ContextType;
use FinityLabs\LinCodex\Enums\Visibility;
use FinityLabs\LinCodex\Models\Article;
use Illuminate\Contracts\Auth\Authenticatable;
use Illuminate\Support\Facades\Gate;

/*
 * SCOPE-01's verdicts, driven through the core rather than through the hook:
 * every row reads the decorated ContentSource and filters it with lin-codex's
 * own ArticleGate, which is what the drawer, the hints, search and the reader
 * all do. The hook is installed straight into lin-codex.auth.gate here; that
 * a booting panel installs it is PanelScopeBootTest's subject.
 *
 * One panel per test method: FilamentManager boots only the first panel of a
 * PHP request cycle, so a row that needs two panels is two rows. Articles are
 * seeded before the first read, so no memo needs dropping.
 */

/** An inner hook a host configured as a class name rather than a closure. */
class FinCodexInnerScopeHook
{
    public function __invoke(Viewer $viewer, ArticleData $article): bool
    {
        return $article->slug !== 'intro';
    }
}

/**
 * A policy that refuses to be asked: the guest rows prove the question is
 * never put for a viewer with no user, and the signed-in row proves it is
 * put for one that has.
 */
class FinCodexAskedArticlePolicy extends ArticlePolicy
{
    public function viewAllPanels(Authenticatable $user): bool
    {
        throw new RuntimeException('asked');
    }
}

/** Put the hook in the core's slot without booting a panel. */
function finCodexInstallScope(): void
{
    config()->set('lin-codex.auth.gate', PanelScopeGate::class);
}

function finCodexScopeUser(string $email = 'scope@example.com'): User
{
    return User::create(['name' => 'Scope', 'email' => $email]);
}

/**
 * The slugs the core admits for one viewer, sorted: the source's map through
 * ArticleGate::filter(), which is every read path's shared answer.
 *
 * @return list<string>
 */
function finCodexSeen(Viewer $viewer): array
{
    $seen = array_keys(app(ArticleGate::class)->filter(app(ContentSource::class)->all(), $viewer));

    sort($seen);

    return $seen;
}

/** One published, public article, optionally carrying one stored context. */
function finCodexScopeArticle(string $slug, ?ContextType $type = null, string $key = '', ?string $panelId = null): Article
{
    $factory = Article::factory()->public()->published()->withTranslation('en', [
        'title' => ucfirst(str_replace(['-', '/'], ' ', $slug)),
        'body' => 'Body of '.$slug.'.',
    ]);

    if ($type !== null) {
        $factory = $factory->withContext($type, $key, $panelId);
    }

    return $factory->create(['slug' => $slug]);
}

/**
 * A published, public section with no body of its own: the navigation shell
 * the container rule is about. A section that carries a body is an article
 * like any other and finCodexScopeArticle() seeds one.
 */
function finCodexScopeShell(string $slug, string $body = ''): Article
{
    return Article::factory()->public()->published()->withTranslation('en', [
        'title' => ucfirst(str_replace(['-', '/'], ' ', $slug)),
        'body' => $body,
    ])->create(['slug' => $slug]);
}

/**
 * The six-article set the panel rule is read off: one general, one resolving
 * into admin by route, one into staff by route, one pinned to staff by
 * prefix, one on a plain Laravel route and one on a class no panel has.
 */
function finCodexSeedScopeSet(): void
{
    finCodexScopeArticle('intro');
    finCodexScopeArticle('admin-guide', ContextType::Route, 'filament.admin.pages.dashboard');
    finCodexScopeArticle('staff-guide', ContextType::Route, 'filament.staff.pages.dashboard');
    finCodexScopeArticle('prefixed-staff', ContextType::PageClass, Dashboard::class, 'staff');
    finCodexScopeArticle('plain-page', ContextType::Route, 'shop.index');
    finCodexScopeArticle('unknown-class', ContextType::PageClass, 'App\\Nowhere');
}

it('changes nothing when no panel is current, even with the hook installed', function (): void {
    finCodexSeedScopeSet();
    finCodexInstallScope();
    $user = finCodexScopeUser();

    expect(finCodexSeen(Viewer::authenticated($user, 'web')))
        ->toBe(['admin-guide', 'intro', 'plain-page', 'prefixed-staff', 'staff-guide', 'unknown-class']);
});

it('shows general articles and the current panel\'s own, and hides the rest', function (): void {
    finCodexSeedScopeSet();
    finCodexInstallScope();
    $user = finCodexScopeUser();
    $this->usesPanel('admin', $user);

    expect(finCodexSeen(Viewer::authenticated($user, 'web')))->toBe(['admin-guide', 'intro']);
});

it('shows an article bound to a class the admin panel registers', function (): void {
    finCodexScopeArticle('intro');
    finCodexScopeArticle('shared-page', ContextType::PageClass, Dashboard::class);
    finCodexInstallScope();
    $user = finCodexScopeUser();
    $this->usesPanel('admin', $user);

    expect(finCodexSeen(Viewer::authenticated($user, 'web')))->toBe(['intro', 'shared-page']);
});

it('shows the same panel-less class article on the staff panel', function (): void {
    finCodexScopeArticle('intro');
    finCodexScopeArticle('shared-page', ContextType::PageClass, Dashboard::class);
    finCodexInstallScope();
    $user = finCodexScopeUser();
    $this->usesPanel('staff', $user);

    expect(finCodexSeen(Viewer::authenticated($user, 'staff')))->toBe(['intro', 'shared-page']);
});

it('shows a two-panel article on admin', function (): void {
    Article::factory()->public()->published()->withTranslation('en', ['title' => 'Both', 'body' => 'Both body.'])
        ->withContext(ContextType::Route, 'filament.admin.pages.dashboard', 'admin')
        ->withContext(ContextType::Route, 'filament.staff.pages.dashboard', 'staff')
        ->create(['slug' => 'both-panels']);
    finCodexInstallScope();
    $user = finCodexScopeUser();
    $this->usesPanel('admin', $user);

    expect(finCodexSeen(Viewer::authenticated($user, 'web')))->toBe(['both-panels']);
});

it('shows the same two-panel article on staff', function (): void {
    Article::factory()->public()->published()->withTranslation('en', ['title' => 'Both', 'body' => 'Both body.'])
        ->withContext(ContextType::Route, 'filament.admin.pages.dashboard', 'admin')
        ->withContext(ContextType::Route, 'filament.staff.pages.dashboard', 'staff')
        ->create(['slug' => 'both-panels']);
    finCodexInstallScope();
    $user = finCodexScopeUser();
    $this->usesPanel('staff', $user);

    expect(finCodexSeen(Viewer::authenticated($user, 'staff')))->toBe(['both-panels']);
});

it('scopes url contexts by the panel path, and treats a wildcard pattern as outside', function (): void {
    finCodexScopeArticle('admin-url', ContextType::Url, '/admin/reports');
    finCodexScopeArticle('staff-url', ContextType::Url, '/staff/reports');
    finCodexScopeArticle('wild-url', ContextType::Url, '/**');
    finCodexInstallScope();
    $user = finCodexScopeUser();
    $this->usesPanel('admin', $user);

    expect(finCodexSeen(Viewer::authenticated($user, 'web')))->toBe(['admin-url']);
});

/*
 * users is declared by the fixture UserResource for admin (the '*' entry) and
 * for staff (its own entry), so the decorator gives the article one panel-
 * prefixed context per panel and no stored context is needed. The resource is
 * registered in neither the portal nor the plain panel, so the article belongs
 * to no panel there.
 */
it('shows a HasHelp-declared article on the panel that declares it', function (): void {
    finCodexScopeArticle('intro');
    finCodexScopeArticle('users');
    finCodexInstallScope();
    $user = finCodexScopeUser();
    $this->usesPanel('admin', $user);

    expect(finCodexSeen(Viewer::authenticated($user, 'web')))->toBe(['intro', 'users']);
});

it('shows a HasHelp-declared article on the second panel that declares it', function (): void {
    finCodexScopeArticle('intro');
    finCodexScopeArticle('users');
    finCodexInstallScope();
    $user = finCodexScopeUser();
    $this->usesPanel('staff', $user);

    expect(finCodexSeen(Viewer::authenticated($user, 'staff')))->toBe(['intro', 'users']);
});

it('hides a HasHelp-declared article on a panel that declares nothing', function (): void {
    finCodexScopeArticle('intro');
    finCodexScopeArticle('users');
    finCodexInstallScope();
    $user = finCodexScopeUser();
    $this->usesPanel('portal', $user);

    expect(finCodexSeen(Viewer::authenticated($user, 'web')))->toBe(['intro']);
});

/*
 * A section that carries contexts is scoped like an article and the core's
 * ancestor rule takes its subtree with it. The slug is deliberately not one
 * the fixture resource declares, which would give the section a context in
 * both panels and prove nothing.
 */
it('lets a panel-bound section take its general child on its own panel', function (): void {
    finCodexScopeArticle('manuals', ContextType::PageClass, Dashboard::class, 'admin');
    finCodexScopeArticle('manuals/roles');
    finCodexInstallScope();
    $user = finCodexScopeUser();
    $this->usesPanel('admin', $user);

    expect(finCodexSeen(Viewer::authenticated($user, 'web')))->toBe(['manuals', 'manuals/roles']);
});

it('hides a panel-bound section and its general child on another panel', function (): void {
    finCodexScopeArticle('manuals', ContextType::PageClass, Dashboard::class, 'admin');
    finCodexScopeArticle('manuals/roles');
    finCodexInstallScope();
    $user = finCodexScopeUser();
    $this->usesPanel('staff', $user);

    expect(finCodexSeen(Viewer::authenticated($user, 'staff')))->toBe([]);
});

it('hides a general container whose only descendant belongs to another panel', function (): void {
    finCodexScopeShell('guides');
    finCodexScopeArticle('guides/staff-only', ContextType::Route, 'filament.staff.pages.dashboard');
    finCodexInstallScope();
    $user = finCodexScopeUser();
    $this->usesPanel('admin', $user);

    expect(finCodexSeen(Viewer::authenticated($user, 'web')))->toBe([]);
});

/*
 * The container rule asks for a section with nothing of its own to read, not
 * for any section at all: the sources set isSection for every database article
 * that has children, so a general section an editor wrote a page into must
 * survive a panel where none of its descendants does, body included. The three
 * rows below are the whole rule — a body keeps it, an empty body and a
 * whitespace-only body do not, and a section written only in another language
 * is read there and keeps its place too.
 */
it('keeps a general section that has a body of its own when no descendant survives', function (): void {
    finCodexScopeArticle('guides');
    finCodexScopeArticle('guides/staff-only', ContextType::Route, 'filament.staff.pages.dashboard');
    finCodexInstallScope();
    $user = finCodexScopeUser();
    $this->usesPanel('admin', $user);

    expect(finCodexSeen(Viewer::authenticated($user, 'web')))->toBe(['guides']);
});

it('treats a whitespace-only section body as no body at all', function (): void {
    finCodexScopeShell('guides', "  \n\t ");
    finCodexScopeArticle('guides/staff-only', ContextType::Route, 'filament.staff.pages.dashboard');
    finCodexInstallScope();
    $user = finCodexScopeUser();
    $this->usesPanel('admin', $user);

    expect(finCodexSeen(Viewer::authenticated($user, 'web')))->toBe([]);
});

it('reads the body of a section that has no default-locale translation', function (): void {
    Article::factory()->public()->published()
        ->withTranslation('de', ['title' => 'Handbücher', 'body' => 'Nur auf Deutsch.'])
        ->create(['slug' => 'guides']);
    finCodexScopeArticle('guides/staff-only', ContextType::Route, 'filament.staff.pages.dashboard');
    finCodexInstallScope();
    $user = finCodexScopeUser();
    $this->usesPanel('admin', $user);

    expect(finCodexSeen(Viewer::authenticated($user, 'web')))->toBe(['guides']);
});

it('shows that same container and child on the panel the child belongs to', function (): void {
    finCodexScopeShell('guides');
    finCodexScopeArticle('guides/staff-only', ContextType::Route, 'filament.staff.pages.dashboard');
    finCodexInstallScope();
    $user = finCodexScopeUser();
    $this->usesPanel('staff', $user);

    expect(finCodexSeen(Viewer::authenticated($user, 'staff')))->toBe(['guides', 'guides/staff-only']);
});

it('keeps a container alive for its surviving general child', function (): void {
    finCodexScopeShell('guides');
    finCodexScopeArticle('guides/intro');
    finCodexScopeArticle('guides/staff-only', ContextType::Route, 'filament.staff.pages.dashboard');
    finCodexInstallScope();
    $user = finCodexScopeUser();
    $this->usesPanel('admin', $user);

    expect(finCodexSeen(Viewer::authenticated($user, 'web')))->toBe(['guides', 'guides/intro']);
});

it('empties a whole chain of containers when the only leaf belongs elsewhere', function (): void {
    finCodexScopeShell('a');
    finCodexScopeShell('a/b');
    finCodexScopeArticle('a/b/c', ContextType::Route, 'filament.staff.pages.dashboard');
    finCodexInstallScope();
    $user = finCodexScopeUser();
    $this->usesPanel('admin', $user);

    expect(finCodexSeen(Viewer::authenticated($user, 'web')))->toBe([]);
});

it('revives the whole chain of containers as soon as one leaf survives', function (): void {
    finCodexScopeShell('a');
    finCodexScopeShell('a/b');
    finCodexScopeArticle('a/b/c', ContextType::Route, 'filament.staff.pages.dashboard');
    finCodexScopeArticle('a/b/d');
    finCodexInstallScope();
    $user = finCodexScopeUser();
    $this->usesPanel('admin', $user);

    expect(finCodexSeen(Viewer::authenticated($user, 'web')))->toBe(['a', 'a/b', 'a/b/d']);
});

/*
 * The file tree: intro, the users index.md section and users/roles, none of
 * them carrying front-matter contexts. users is a declared slug, so on admin
 * the decorator scopes the section into the panel and the general child rides
 * along; on the portal panel nothing declares it and the section takes the
 * child with it.
 */
it('shows the fixture file section and its child on admin', function (): void {
    useFixtureDocs();
    finCodexInstallScope();
    $user = finCodexScopeUser();
    $this->usesPanel('admin', $user);

    expect(finCodexSeen(Viewer::authenticated($user, 'web')))->toBe(['intro', 'users', 'users/roles']);
});

it('hides the fixture file section and its child on a panel that declares neither', function (): void {
    useFixtureDocs();
    finCodexInstallScope();
    $user = finCodexScopeUser();
    $this->usesPanel('portal', $user);

    expect(finCodexSeen(Viewer::authenticated($user, 'web')))->toBe(['intro']);
});

it('lifts the scope for a viewer the policy allows, and for nobody else', function (string $policy, array $expected): void {
    finCodexSeedScopeSet();
    finCodexInstallScope();
    $user = finCodexScopeUser();
    $this->usesPanel('admin', $user);

    // After usesPanel(): the plugin's boot registers the shipped policy again.
    Gate::policy(Article::class, $policy);

    expect(finCodexSeen(Viewer::authenticated($user, 'web')))->toBe($expected);
})->with([
    'a host that grants viewAllPanels' => [
        ViewAllPanelsArticlePolicy::class,
        ['admin-guide', 'intro', 'plain-page', 'prefixed-staff', 'staff-guide', 'unknown-class'],
    ],
    'a host policy without the method' => [DenyAllArticlePolicy::class, ['admin-guide', 'intro']],
    'the shipped policy' => [ArticlePolicy::class, ['admin-guide', 'intro']],
]);

it('never asks the policy about a guest', function (): void {
    finCodexSeedScopeSet();
    finCodexInstallScope();
    $this->usesPanel('admin');

    Gate::policy(Article::class, FinCodexAskedArticlePolicy::class);

    expect(finCodexSeen(Viewer::guest('web')))->toBe(['admin-guide', 'intro']);
});

it('asks the policy about a viewer that has a user', function (): void {
    finCodexSeedScopeSet();
    finCodexInstallScope();
    $user = finCodexScopeUser();
    $this->usesPanel('admin', $user);

    Gate::policy(Article::class, FinCodexAskedArticlePolicy::class);

    expect(fn (): array => finCodexSeen(Viewer::authenticated($user, 'web')))
        ->toThrow(RuntimeException::class, 'asked');
});

it('runs a host closure hook first and vetoes on top of it', function (): void {
    finCodexSeedScopeSet();
    config()->set('lin-codex.auth.gate', fn (Viewer $viewer, ArticleData $article): bool => $article->slug !== 'intro');
    app(PanelScopeGate::class)->wrap(config('lin-codex.auth.gate'));
    finCodexInstallScope();
    $user = finCodexScopeUser();
    $this->usesPanel('admin', $user);

    expect(finCodexSeen(Viewer::authenticated($user, 'web')))->toBe(['admin-guide']);
});

it('resolves an inner hook given as a class name through the container', function (): void {
    finCodexSeedScopeSet();
    app(PanelScopeGate::class)->wrap(FinCodexInnerScopeHook::class);
    finCodexInstallScope();
    $user = finCodexScopeUser();
    $this->usesPanel('admin', $user);

    expect(finCodexSeen(Viewer::authenticated($user, 'web')))->toBe(['admin-guide']);
});

it('refuses an inner hook that is neither null, a class name nor a callable', function (): void {
    finCodexSeedScopeSet();
    app(PanelScopeGate::class)->wrap(42);
    finCodexInstallScope();
    $user = finCodexScopeUser();
    $this->usesPanel('admin', $user);

    expect(fn (): array => finCodexSeen(Viewer::authenticated($user, 'web')))
        ->toThrow(InvalidArgumentException::class);
});

it('never vetoes an article the source does not know', function (): void {
    finCodexSeedScopeSet();
    finCodexInstallScope();
    $user = finCodexScopeUser();
    $this->usesPanel('admin', $user);

    $stranger = new ArticleData(
        slug: 'nowhere',
        parentSlug: null,
        order: 0,
        icon: null,
        format: ArticleFormat::Markdown,
        visibility: Visibility::Public,
        published: true,
        contexts: [new ContextData(ContextType::Route, 'filament.staff.pages.dashboard')],
        related: [],
        keywords: [],
        translations: [],
    );

    expect(app(PanelScopeGate::class)(Viewer::authenticated($user, 'web'), $stranger))->toBeTrue();
});

/*
 * The verdict map is built once per request, like the decorated source's own
 * memo. Dropping the source alone is not enough — the gate keeps its map, and
 * a slug it has never seen is never vetoed — so forgetHelpMemo() is the helper
 * a test seeding mid-request reaches for, exactly as for CoverageReport.
 */
it('keeps its verdict map for the request and drops it with the memo', function (): void {
    finCodexSeedScopeSet();
    finCodexInstallScope();
    $user = finCodexScopeUser();
    $this->usesPanel('admin', $user);
    $viewer = Viewer::authenticated($user, 'web');

    expect(finCodexSeen($viewer))->toBe(['admin-guide', 'intro']);

    finCodexScopeArticle('late-staff', ContextType::Route, 'filament.staff.pages.dashboard');

    expect(finCodexSeen($viewer))->toBe(['admin-guide', 'intro', 'late-staff']);

    forgetHelpMemo();

    expect(finCodexSeen($viewer))->toBe(['admin-guide', 'intro']);
});

it('leaves the published rule to the core', function (): void {
    finCodexScopeArticle('intro');
    Article::factory()->public()->unpublished()->withTranslation('en', ['title' => 'Draft', 'body' => 'Draft body.'])
        ->withContext(ContextType::Route, 'filament.admin.pages.dashboard')
        ->create(['slug' => 'admin-draft']);
    finCodexInstallScope();
    $user = finCodexScopeUser();
    $this->usesPanel('admin', $user);

    expect(finCodexSeen(Viewer::authenticated($user, 'web')))->toBe(['intro']);
});

/*
 * The container rule counts descendants by the panel rule alone. A section
 * whose only child is this panel's but unpublished stays visible and empty,
 * which is the 0.4 behaviour and is left untouched on purpose.
 */
it('counts an unpublished descendant of this panel for the container rule', function (): void {
    finCodexScopeShell('guides');
    Article::factory()->public()->unpublished()->withTranslation('en', ['title' => 'Draft', 'body' => 'Draft body.'])
        ->withContext(ContextType::Route, 'filament.admin.pages.dashboard')
        ->create(['slug' => 'guides/draft']);
    finCodexInstallScope();
    $user = finCodexScopeUser();
    $this->usesPanel('admin', $user);

    expect(finCodexSeen(Viewer::authenticated($user, 'web')))->toBe(['guides']);
});
