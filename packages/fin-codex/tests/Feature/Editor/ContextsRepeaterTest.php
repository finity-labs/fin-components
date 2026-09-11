<?php

use Filament\Auth\Pages\Login;
use Filament\Forms\Components\Repeater;
use FinityLabs\FinCodex\Editor\ContextPicker;
use FinityLabs\FinCodex\Editor\PageClassPickerTable;
use FinityLabs\FinCodex\Editor\RoutePickerTable;
use FinityLabs\FinCodex\Resources\ArticleResource\Pages\CreateArticle;
use FinityLabs\FinCodex\Resources\ArticleResource\Pages\EditArticle;
use FinityLabs\FinCodex\Resources\ArticleResource\Schemas\ContextsRepeater;
use FinityLabs\FinCodex\Tests\Fixtures\Resources\AdminHelpArticleResource;
use FinityLabs\FinCodex\Tests\Fixtures\Resources\StaffHelpArticleResource;
use FinityLabs\FinCodex\Tests\Fixtures\Resources\UserResource;
use FinityLabs\FinCodex\Tests\Fixtures\User;
use FinityLabs\FinModalTableSelect\Components\ModalTableSelect;
use FinityLabs\LinCodex\Contracts\ContentSource;
use FinityLabs\LinCodex\Data\ArticleData;
use FinityLabs\LinCodex\Enums\ArticleFormat;
use FinityLabs\LinCodex\Enums\ContextType;
use FinityLabs\LinCodex\Enums\Visibility;
use FinityLabs\LinCodex\Models\Article;
use FinityLabs\LinCodex\Models\ArticleContext;
use Livewire\Features\SupportTesting\Testable;
use Livewire\Livewire;

/*
 * EDIT-04 through the pages. Every row drives CreateArticle or EditArticle,
 * so the proof covers the whole path the admin walks: the cascading selects,
 * the drag order Filament hands back, the mutators that dehydrate the rows,
 * ArticleWriter's one transaction, and finally the core resolving the
 * article from the context that was picked. usesPanel() comes first
 * everywhere; the writer stores whoever the panel names.
 */

function finCodexContextsUser(string $name = 'Contexts'): User
{
    return User::create(['name' => $name, 'email' => strtolower($name).'@example.com']);
}

/**
 * A complete create-form state carrying the given repeater rows.
 *
 * @param  list<array<string, mixed>>  $rows
 *
 * @return array<string, mixed>
 */
function finCodexContextsState(array $rows, string $slug = 'billing'): array
{
    return [
        'slug' => $slug,
        'icon' => null,
        'sort_order' => 0,
        'format' => ArticleFormat::Markdown->value,
        'is_published' => true,
        'visibility' => Visibility::Public->value,
        'keywords' => [],
        'related' => [],
        'translations' => ['en' => ['title' => 'Billing', 'excerpt' => null, 'body' => 'How billing works.']],
        'contexts' => $rows,
    ];
}

/**
 * The stored rows in sort order, flattened to what the picker put there.
 *
 * @return list<array{0: ?string, 1: ContextType, 2: string, 3: int}>
 */
function finCodexContextsRows(Article $article): array
{
    return $article->contexts()->orderBy('sort_order')->get()
        ->map(static fn (ArticleContext $context): array => [
            $context->panel_id,
            $context->type,
            $context->key,
            $context->sort_order,
        ])
        ->all();
}

/**
 * The repeater rows in form state, flattened and stripped of the generated
 * item keys.
 *
 * @return list<array{0: ?string, 1: ?string, 2: ?string, 3: ?string}>
 */
function finCodexContextsFormRows(Testable $component): array
{
    $rows = data_get($component->instance()->data, 'contexts');

    return array_map(
        static fn (array $row): array => [$row['panel_id'] ?? null, $row['type'] ?? null, $row['key'] ?? null, $row['url'] ?? null],
        array_values(is_array($rows) ? $rows : []),
    );
}

/**
 * The key picker of one repeater row, rebuilt from the component's current
 * form so a changed panel or type is reflected.
 */
function finCodexContextsKeyPicker(Testable $component, string $item): ModalTableSelect
{
    $repeater = $component->instance()->form->getComponent(
        fn (mixed $schemaComponent): bool => $schemaComponent instanceof Repeater && $schemaComponent->getName() === 'contexts',
    );

    $picker = $repeater instanceof Repeater
        ? $repeater->getChildSchema($item)?->getComponent(
            fn (mixed $schemaComponent): bool => $schemaComponent instanceof ModalTableSelect && $schemaComponent->getName() === 'key',
        )
        : null;

    expect($picker)->toBeInstanceOf(ModalTableSelect::class);

    /** @var ModalTableSelect $picker */
    return $picker;
}

it('saves picker rows in drag order with any-panel as null', function (): void {
    $user = finCodexContextsUser();
    $this->usesPanel('admin', $user);

    Livewire::test(CreateArticle::class)
        ->fillForm(finCodexContextsState([
            ['panel_id' => 'admin', 'type' => 'class', 'key' => UserResource::class],
            ['panel_id' => ContextPicker::ANY_PANEL, 'type' => 'route', 'key' => 'filament.staff.pages.reports'],
            ['panel_id' => 'admin', 'type' => 'url', 'url' => '/admin/users/*'],
        ]))
        ->call('create')
        ->assertHasNoFormErrors();

    $article = Article::query()->where('slug', 'billing')->sole();

    expect(finCodexContextsRows($article))->toBe([
        ['admin', ContextType::PageClass, UserResource::class, 0],
        [null, ContextType::Route, 'filament.staff.pages.reports', 1],
        ['admin', ContextType::Url, '/admin/users/*', 2],
    ]);
});

it('fills the repeater from stored rows on edit and reorders on save', function (): void {
    $user = finCodexContextsUser();
    $this->usesPanel('admin', $user);

    $article = Article::factory()
        ->public()
        ->withTranslation('en', ['title' => 'Billing', 'body' => 'How billing works.'])
        ->withContext(ContextType::PageClass, UserResource::class, 'admin', 0)
        ->withContext(ContextType::Route, 'filament.staff.pages.reports', null, 1)
        ->withContext(ContextType::Url, '/admin/users/*', 'admin', 2)
        ->create(['slug' => 'billing']);

    $oldIds = $article->contexts()->pluck('id')->all();

    $component = Livewire::test(EditArticle::class, ['record' => $article->getRouteKey()]);

    expect(finCodexContextsFormRows($component))->toBe([
        ['admin', 'class', UserResource::class, ''],
        [ContextPicker::ANY_PANEL, 'route', 'filament.staff.pages.reports', ''],
        ['admin', 'url', '', '/admin/users/*'],
    ]);

    $component
        ->fillForm(['contexts' => array_reverse(ContextsRepeater::fill($article))])
        ->call('save')
        ->assertHasNoFormErrors();

    expect(finCodexContextsRows($article->fresh()))->toBe([
        ['admin', ContextType::Url, '/admin/users/*', 0],
        [null, ContextType::Route, 'filament.staff.pages.reports', 1],
        ['admin', ContextType::PageClass, UserResource::class, 2],
    ])->and(ArticleContext::query()->whereIn('id', $oldIds)->count())->toBe(0);
});

it('drops blank rows and requires a key or pattern', function (): void {
    $user = finCodexContextsUser();
    $this->usesPanel('admin', $user);

    Livewire::test(CreateArticle::class)
        ->fillForm(finCodexContextsState([['panel_id' => 'admin', 'type' => 'class', 'key' => null]]))
        ->call('create')
        ->assertHasFormErrors(['contexts.0.key']);

    Livewire::test(CreateArticle::class)
        ->fillForm(finCodexContextsState([['panel_id' => 'admin', 'type' => 'url', 'url' => null]]))
        ->call('create')
        ->assertHasFormErrors(['contexts.0.url']);

    expect(Article::query()->count())->toBe(0);

    // dehydrate() is the safety net under the form-level required() rules.
    expect(ContextsRepeater::dehydrate([
        ['panel_id' => 'admin', 'type' => 'class', 'key' => UserResource::class, 'url' => ''],
        ['panel_id' => ContextPicker::ANY_PANEL, 'type' => 'class', 'key' => '', 'url' => ''],
        ['panel_id' => ContextPicker::ANY_PANEL, 'type' => 'url', 'key' => '', 'url' => '/x/*'],
        ['panel_id' => 'admin', 'type' => '', 'key' => 'x', 'url' => ''],
    ]))->toBe([
        ['panel_id' => 'admin', 'type' => 'class', 'key' => UserResource::class],
        ['panel_id' => null, 'type' => 'url', 'key' => '/x/*'],
    ]);
});

it('cascades: changing the type clears the key and the pattern', function (): void {
    $user = finCodexContextsUser();
    $this->usesPanel('admin', $user);

    $component = Livewire::test(CreateArticle::class)
        ->fillForm(finCodexContextsState([['panel_id' => 'admin', 'type' => 'class', 'key' => UserResource::class]]));

    $item = array_key_first(data_get($component->instance()->data, 'contexts'));

    // A class key is never a route key, so the type select still clears both
    // fields with nothing to ask.
    $component
        ->assertSet("data.contexts.{$item}.key", UserResource::class)
        ->set("data.contexts.{$item}.type", 'route')
        ->assertSet("data.contexts.{$item}.key", null)
        ->assertSet("data.contexts.{$item}.url", null);
});

it('keeps a key the panel just chosen still offers', function (): void {
    $user = finCodexContextsUser();
    $this->usesPanel('admin', $user);

    // Both fixture panels register UserResource, so the move costs the author
    // nothing and the row keeps what was picked.
    $component = Livewire::test(CreateArticle::class)
        ->fillForm(finCodexContextsState([['panel_id' => 'admin', 'type' => 'class', 'key' => UserResource::class]]));

    $item = array_key_first(data_get($component->instance()->data, 'contexts'));

    $component
        ->set("data.contexts.{$item}.panel_id", 'staff')
        ->assertSet("data.contexts.{$item}.key", UserResource::class)
        ->assertNotNotified();
});

it('clears a key the panel just chosen does not offer, and says so', function (): void {
    $user = finCodexContextsUser();
    $this->usesPanel('admin', $user);

    // The article resource is registered on the admin panel alone, and a
    // filament.admin.* route name says which panel it belongs to outright.
    $component = Livewire::test(CreateArticle::class)
        ->fillForm(finCodexContextsState([
            ['panel_id' => 'admin', 'type' => 'class', 'key' => AdminHelpArticleResource::class],
            ['panel_id' => 'admin', 'type' => 'route', 'key' => 'filament.admin.resources.users.index'],
        ]));

    $items = array_keys(data_get($component->instance()->data, 'contexts'));

    $component
        ->set("data.contexts.{$items[0]}.panel_id", 'staff')
        ->assertSet("data.contexts.{$items[0]}.key", null)
        ->assertNotified(__('fin-codex::fin-codex.editor.contexts.key_cleared'));

    $component
        ->set("data.contexts.{$items[1]}.panel_id", 'staff')
        ->assertSet("data.contexts.{$items[1]}.key", null)
        ->assertNotified(__('fin-codex::fin-codex.editor.contexts.key_cleared'));
});

it('never clears a key when the row switches to any panel', function (): void {
    $user = finCodexContextsUser();
    $this->usesPanel('admin', $user);

    // Any panel unions every panel, so it can never take a key away.
    $component = Livewire::test(CreateArticle::class)
        ->fillForm(finCodexContextsState([['panel_id' => 'admin', 'type' => 'class', 'key' => AdminHelpArticleResource::class]]));

    $item = array_key_first(data_get($component->instance()->data, 'contexts'));

    $component
        ->set("data.contexts.{$item}.panel_id", ContextPicker::ANY_PANEL)
        ->assertSet("data.contexts.{$item}.key", AdminHelpArticleResource::class)
        ->assertNotNotified();
});

it('clears an auth page when the row moves from any panel to a named one', function (): void {
    $user = finCodexContextsUser();
    $this->usesPanel('admin', $user);

    // The sign-in page belongs to the application rather than to one panel,
    // so naming a panel takes it away: the binding that would hide the
    // article everywhere cannot be written from this direction either.
    $component = Livewire::test(CreateArticle::class)
        ->fillForm(finCodexContextsState([['panel_id' => ContextPicker::ANY_PANEL, 'type' => 'class', 'key' => Login::class]]));

    $item = array_key_first(data_get($component->instance()->data, 'contexts'));

    $component
        ->set("data.contexts.{$item}.panel_id", 'admin')
        ->assertSet("data.contexts.{$item}.key", null)
        ->assertNotified(__('fin-codex::fin-codex.editor.contexts.key_cleared'));
});

it('leaves a url pattern and an empty row alone when the panel changes', function (): void {
    $user = finCodexContextsUser();
    $this->usesPanel('admin', $user);

    // A pattern is free text an author may well mean, and a row with nothing
    // picked has nothing to lose and nothing to announce.
    $component = Livewire::test(CreateArticle::class)
        ->fillForm(finCodexContextsState([
            ['panel_id' => 'admin', 'type' => 'url', 'url' => '/admin/users/*'],
            ['panel_id' => 'admin', 'type' => 'class', 'key' => null],
        ]));

    $items = array_keys(data_get($component->instance()->data, 'contexts'));

    $component
        ->set("data.contexts.{$items[0]}.panel_id", 'staff')
        ->set("data.contexts.{$items[1]}.panel_id", 'staff')
        ->assertSet("data.contexts.{$items[0]}.url", '/admin/users/*')
        ->assertSet("data.contexts.{$items[1]}.key", null)
        ->assertNotNotified();
});

it('shows the picked page with its class, or its route name and path, under the label', function (): void {
    $user = finCodexContextsUser();
    $this->usesPanel('admin', $user);

    // The class appears with single backslashes only where the row renders
    // it — Livewire's snapshot carries it JSON-escaped — and only the
    // rendered row puts a route name and its path on consecutive lines.
    Livewire::test(CreateArticle::class)
        ->fillForm(finCodexContextsState([
            ['panel_id' => 'admin', 'type' => 'class', 'key' => UserResource::class],
            ['panel_id' => 'admin', 'type' => 'route', 'key' => 'filament.admin.resources.users.index'],
        ]))
        ->assertSee('Users')
        ->assertSee(UserResource::class)
        ->assertSee("filament.admin.resources.users.index\n/admin/users");
});

it('lists declared contexts read-only above the repeater and never saves them', function (): void {
    $user = finCodexContextsUser();
    $this->usesPanel('admin', $user);

    $declared = Article::factory()->public()
        ->withTranslation('en', ['title' => 'Users', 'body' => 'How users work.'])
        ->create(['slug' => 'users']);

    $component = Livewire::test(EditArticle::class, ['record' => $declared->getRouteKey()]);
    $html = $component->html();

    expect($html)->toContain('data-fin-codex-declared')
        ->toContain('data-fin-codex-declared-panel="admin"')
        ->toContain('data-fin-codex-declared-panel="staff"')
        ->toContain('admin:class:'.UserResource::class)
        ->toContain('staff:class:'.UserResource::class)
        ->toContain((string) __('fin-codex::fin-codex.editor.contexts.declared'));

    expect(data_get($component->instance()->data, 'contexts'))->toBe([]);

    $component->call('save')->assertHasNoFormErrors();

    expect($declared->contexts()->count())->toBe(0);

    // An undeclared article, and the create page, carry no declared list.
    $plain = Article::factory()->public()
        ->withTranslation('en', ['title' => 'Billing', 'body' => 'How billing works.'])
        ->create(['slug' => 'billing']);

    expect(Livewire::test(EditArticle::class, ['record' => $plain->getRouteKey()])->html())
        ->not->toContain('data-fin-codex-declared')
        ->and(Livewire::test(CreateArticle::class)->html())
        ->not->toContain('data-fin-codex-declared');
});

it('makes the article resolvable by the saved context through the core', function (): void {
    $user = finCodexContextsUser();
    $this->usesPanel('admin', $user);

    Livewire::test(CreateArticle::class)
        ->fillForm(finCodexContextsState([
            ['panel_id' => 'admin', 'type' => 'class', 'key' => UserResource::class],
        ]))
        ->call('create')
        ->assertHasNoFormErrors();

    forgetHelpMemo();

    $found = app(ContentSource::class)->findByContext(ContextType::PageClass, UserResource::class, 'admin');

    expect(array_map(static fn (ArticleData $article): string => $article->slug, $found))->toContain('billing');

    $html = $this->get('/admin/users')->assertOk()->getContent();

    expect($html)->toContain('data-codex-page-article="billing"')
        ->toContain('data-codex-page-count="1"');
});

it('scopes the key picker rows and columns to the row panel and type', function (): void {
    $user = finCodexContextsUser();
    $this->usesPanel('admin', $user);

    $component = Livewire::test(CreateArticle::class)
        ->fillForm(finCodexContextsState([['panel_id' => 'admin', 'type' => 'class', 'key' => UserResource::class]]));

    $item = array_key_first(data_get($component->instance()->data, 'contexts'));
    $picker = finCodexContextsKeyPicker($component, $item);

    expect($picker->getTableConfiguration())->toBe(PageClassPickerTable::class)
        ->and(array_keys($picker->getStandaloneRecordsIndex()))->toContain(UserResource::class)
        ->not->toContain(StaffHelpArticleResource::class)
        ->and($picker->getOptionLabel())->toBe('Users');

    $component->set("data.contexts.{$item}.panel_id", 'staff');
    $picker = finCodexContextsKeyPicker($component, $item);

    expect(array_keys($picker->getStandaloneRecordsIndex()))->toContain(StaffHelpArticleResource::class)
        ->not->toContain(AdminHelpArticleResource::class);

    $component->set("data.contexts.{$item}.type", 'route');
    $picker = finCodexContextsKeyPicker($component, $item);

    expect($picker->getTableConfiguration())->toBe(RoutePickerTable::class)
        ->and(array_keys($picker->getStandaloneRecordsIndex()))->toContain('filament.staff.pages.dashboard')
        ->not->toContain('filament.admin.resources.users.index');
});

it('offers the auth pages to the live key picker under any panel only', function (): void {
    $user = finCodexContextsUser();
    $this->usesPanel('admin', $user);

    // Plan 14.1-01's rule, proved through the form the author actually uses:
    // a sign-in page belongs to the application, so a row naming one panel
    // cannot bind it and never sees it.
    $component = Livewire::test(CreateArticle::class)
        ->fillForm(finCodexContextsState([['panel_id' => ContextPicker::ANY_PANEL, 'type' => 'class', 'key' => null]]));

    $item = array_key_first(data_get($component->instance()->data, 'contexts'));

    expect(array_keys(finCodexContextsKeyPicker($component, $item)->getStandaloneRecordsIndex()))
        ->toContain(Login::class);

    $component->set("data.contexts.{$item}.panel_id", 'admin');

    expect(array_keys(finCodexContextsKeyPicker($component, $item)->getStandaloneRecordsIndex()))
        ->not->toContain(Login::class)
        ->toContain(UserResource::class);
});

it('says where the sign-in pages went, and only where there is something to say', function (): void {
    $user = finCodexContextsUser();
    $this->usesPanel('admin', $user);

    $component = Livewire::test(CreateArticle::class)
        ->fillForm(finCodexContextsState([['panel_id' => 'admin', 'type' => 'class', 'key' => UserResource::class]]));

    $item = array_key_first(data_get($component->instance()->data, 'contexts'));

    expect(finCodexContextsKeyPicker($component, $item)->getAction('select')?->getModalDescription())
        ->toBe(__('fin-codex::fin-codex.editor.contexts.auth_any_panel'));

    // Under Any panel the pages are right there in the list, and a route row
    // never offered them in the first place.
    $component->set("data.contexts.{$item}.panel_id", ContextPicker::ANY_PANEL);

    expect(finCodexContextsKeyPicker($component, $item)->getAction('select')?->getModalDescription())
        ->toBeNull();

    $component
        ->set("data.contexts.{$item}.panel_id", 'admin')
        ->set("data.contexts.{$item}.type", 'route');

    expect(finCodexContextsKeyPicker($component, $item)->getAction('select')?->getModalDescription())
        ->toBeNull();
});
