<?php

use Filament\Facades\Filament;
use FinityLabs\FinCodex\Editor\ContextPicker;
use FinityLabs\FinCodex\Resources\ArticleResource\Pages\CreateArticle;
use FinityLabs\FinCodex\Tests\Fixtures\Resources\AdminHelpArticleResource;
use FinityLabs\FinCodex\Tests\Fixtures\Resources\UserResource;
use FinityLabs\FinCodex\Tests\Fixtures\User;
use FinityLabs\LinCodex\Enums\ContextType;
use FinityLabs\LinCodex\Models\Article;
use FinityLabs\LinCodex\Models\ArticleContext;
use Livewire\Features\SupportTesting\Testable;
use Livewire\Livewire;

/*
 * COV-02: closing a gap from the coverage row.
 *
 * The prefill travels as a query string, so the link is shareable, survives a
 * refresh and the back button, and is assertable without reaching into session
 * state. Every row here builds that URL with the resource's own getUrl(), the
 * builder the coverage page uses, and hands its query string to the mount —
 * so a parameter renamed on one side reddens these rows rather than silently
 * doing nothing in a browser.
 *
 * Helpers are prefixed finCodexActions*: finCodexCoverage* belongs to
 * CoverageReportTest, finCodexPage* to HelpCoverageTest, finCodexSurface* to
 * WarningsSurfaceTest, and Pest helpers are global.
 */

/** A fixture user signed in on the admin panel. */
function finCodexActionsUser(string $name = 'Admin'): User
{
    $user = User::create(['name' => $name, 'email' => strtolower($name).'@example.com']);

    test()->usesPanel('admin', $user);

    return $user;
}

/**
 * The create page as the coverage page's "Write article" opens it: the URL is
 * built by the resource, then its query string is what the mount sees.
 *
 * @param  array<string, string>  $prefill
 */
function finCodexActionsCreate(array $prefill = []): Testable
{
    $url = AdminHelpArticleResource::getUrl('create', $prefill);
    parse_str((string) parse_url($url, PHP_URL_QUERY), $query);

    return Livewire::withQueryParams($query)->test(CreateArticle::class);
}

/**
 * The raw form state of a mounted create page — the repeater rows as they
 * are, uuid keys and all.
 *
 * @return array<string, mixed>
 */
function finCodexActionsState(Testable $page): array
{
    return $page->instance()->form->getRawState();
}

/** The context rows of one article as "{panel|*}:{type}:{key}" strings, in order. */
function finCodexActionsContexts(Article $article): array
{
    return ArticleContext::query()
        ->where('article_id', $article->id)
        ->orderBy('sort_order')
        ->get()
        ->map(fn (ArticleContext $context): string => ($context->panel_id ?? ContextPicker::ANY_PANEL).':'.$context->type->key().':'.$context->key)
        ->all();
}

/*
 * -----------------------------------------------------------------------
 * The create form, opened from a coverage row.
 * -----------------------------------------------------------------------
 */

it('opens with the page\'s own context and title already filled', function (): void {
    finCodexActionsUser();

    $state = finCodexActionsState(finCodexActionsCreate([
        'context_type' => ContextType::PageClass->key(),
        'context_key' => UserResource::class,
        'panel' => 'admin',
        'title' => 'Users',
    ]));

    $rows = array_values($state['contexts']);

    expect($rows)->toHaveCount(1)
        ->and($rows[0]['panel_id'])->toBe('admin')
        ->and($rows[0]['type'])->toBe(ContextType::PageClass->key())
        ->and($rows[0]['key'])->toBe(UserResource::class)
        ->and($state['translations']['en']['title'])->toBe('Users');
});

it('keeps every default the plain create page has', function (): void {
    finCodexActionsUser();

    $plain = finCodexActionsState(finCodexActionsCreate());
    $prefilled = finCodexActionsState(finCodexActionsCreate([
        'context_type' => ContextType::PageClass->key(),
        'context_key' => UserResource::class,
        'panel' => 'admin',
        'title' => 'Users',
    ]));

    // Against the plain page, never against literals: Schema::fill() only
    // hydrates a component's default when it is handed null, so filling
    // straight from the prefill array would drop these three silently.
    expect($prefilled['format'])->toBe($plain['format'])
        ->and($prefilled['is_published'])->toBe($plain['is_published'])
        ->and($prefilled['visibility'])->toBe($plain['visibility'])
        ->and($plain['format'])->not->toBeNull();
});

it('saves in one press, with the context nobody had to touch', function (): void {
    finCodexActionsUser();

    finCodexActionsCreate([
        'context_type' => ContextType::PageClass->key(),
        'context_key' => UserResource::class,
        'panel' => 'admin',
        'title' => 'Users',
    ])
        ->set('data.slug', 'users-guide')
        ->set('data.translations.en.body', 'How users work.')
        ->call('create')
        ->assertHasNoFormErrors();

    $article = Article::query()->where('slug', 'users-guide')->firstOrFail();

    // A Select carries an implicit Rule::in() over its options, so a key the
    // picker does not offer would fail validation right here.
    expect(finCodexActionsContexts($article))->toBe(['admin:class:'.UserResource::class]);
});

it('prefills a standalone route the same way', function (): void {
    finCodexActionsUser();

    $state = finCodexActionsState(finCodexActionsCreate([
        'context_type' => ContextType::Route->key(),
        'context_key' => 'filament.admin.pages.dashboard',
        'panel' => 'admin',
        'title' => 'Dashboard',
    ]));

    $rows = array_values($state['contexts']);

    expect($rows)->toHaveCount(1)
        ->and($rows[0]['panel_id'])->toBe('admin')
        ->and($rows[0]['type'])->toBe(ContextType::Route->key())
        ->and($rows[0]['key'])->toBe('filament.admin.pages.dashboard')
        ->and($state['translations']['en']['title'])->toBe('Dashboard');
});

it('drops a key the picker does not offer instead of opening a form that cannot be saved', function (): void {
    finCodexActionsUser();

    $page = finCodexActionsCreate([
        'context_type' => ContextType::PageClass->key(),
        'context_key' => 'App\\Nope\\Missing',
        'panel' => 'admin',
        'title' => 'Missing',
    ]);

    $state = finCodexActionsState($page);

    expect($state['contexts'])->toBe([])
        ->and($state['translations']['en']['title'])->toBeNull();

    $page->assertNotified(__('fin-codex::fin-codex.coverage.prefill.dropped'));

    // And it is an ordinary empty form, not a broken one.
    $page->set('data.slug', 'anything')
        ->set('data.translations.en.title', 'Anything')
        ->set('data.translations.en.body', 'Body.')
        ->call('create')
        ->assertHasNoFormErrors();

    expect(Article::query()->where('slug', 'anything')->exists())->toBeTrue();
});

it('widens an unknown panel to any panel rather than refusing the prefill', function (): void {
    finCodexActionsUser();

    $rows = array_values(finCodexActionsState(finCodexActionsCreate([
        'context_type' => ContextType::PageClass->key(),
        'context_key' => UserResource::class,
        'panel' => 'nosuchpanel',
        'title' => 'Users',
    ]))['contexts']);

    expect($rows)->toHaveCount(1)
        ->and($rows[0]['panel_id'])->toBe(ContextPicker::ANY_PANEL)
        ->and($rows[0]['key'])->toBe(UserResource::class);
});

it('changes nothing at all without a query string', function (): void {
    finCodexActionsUser();

    expect(finCodexActionsState(finCodexActionsCreate()))
        ->toBe(finCodexActionsState(finCodexActionsCreate()))
        ->and(finCodexActionsState(finCodexActionsCreate())['contexts'])->toBe([])
        ->and(finCodexActionsState(finCodexActionsCreate())['translations']['en']['title'])->toBeNull()
        ->and(Filament::getCurrentPanel()?->getId())->toBe('admin');
});
