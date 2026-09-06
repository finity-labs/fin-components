<?php

use Filament\Facades\Filament;
use FinityLabs\FinCodex\Editor\ArticleWriter;
use FinityLabs\FinCodex\Resources\ArticleResource\Pages\CreateArticle;
use FinityLabs\FinCodex\Resources\ArticleResource\Pages\EditArticle;
use FinityLabs\FinCodex\Tests\Fixtures\User;
use FinityLabs\LinCodex\Enums\ArticleFormat;
use FinityLabs\LinCodex\Enums\RevisionReason;
use FinityLabs\LinCodex\Enums\Visibility;
use FinityLabs\LinCodex\Models\Article;
use FinityLabs\LinCodex\Models\ArticleRevision;
use FinityLabs\LinCodex\Models\ArticleTranslation;
use FinityLabs\LinCodex\Settings\CodexSettings;
use Livewire\Livewire;

/*
 * EDIT-03 and EDIT-06 through the pages: the form the admin actually uses.
 * Every row drives CreateArticle or EditArticle with Livewire, so the proof
 * is the whole path — validation, the enum ints in form state, the language
 * tabs keyed by locale — and not the writer, which 05-01 already proved on
 * its own. usesPanel() comes first in every row: Filament's auth guard is
 * the panel's, and the writer stores whoever it names.
 */

/** A fixture user signed in on the panel under test. */
function finCodexFormUser(string $name = 'Editor'): User
{
    return User::create(['name' => $name, 'email' => strtolower($name).'@example.com']);
}

/**
 * A complete create-form state; $overrides is merged recursively so one
 * nested translation key can be changed on its own.
 *
 * @param  array<string, mixed>  $overrides
 *
 * @return array<string, mixed>
 */
function finCodexFormState(array $overrides = []): array
{
    return array_replace_recursive([
        'slug' => 'users',
        'icon' => 'heroicon-o-users',
        'sort_order' => 2,
        'format' => ArticleFormat::Markdown->value,
        'is_published' => true,
        'visibility' => Visibility::Public->value,
        'keywords' => ['people'],
        'related' => [],
        'translations' => [
            'en' => ['title' => 'Users', 'excerpt' => 'Manage users.', 'body' => 'How users work.'],
        ],
    ], $overrides);
}

/**
 * @param  list<string>  $codes
 */
function finCodexFormUseLanguages(array $codes, string $default = 'en'): void
{
    $settings = app(CodexSettings::class);
    $settings->languages = array_map([CodexSettings::class, 'languageEntry'], $codes);
    $settings->default_locale = $default;
    $settings->save();
}

/** The display name CodexSettings gives a locale, so the tab labels are never hard-coded. */
function finCodexFormDisplay(string $code): string
{
    return CodexSettings::languageEntry($code)['display'];
}

it('creates an article through the form with the panel user and redirects to edit', function (): void {
    enableRevisions(true);
    $user = finCodexFormUser();
    $this->usesPanel('admin', $user);

    Livewire::test(CreateArticle::class)
        ->fillForm(finCodexFormState())
        ->call('create')
        ->assertHasNoFormErrors()
        ->assertRedirect();

    $article = Article::query()->where('slug', 'users')->sole();

    expect($article->icon)->toBe('heroicon-o-users')
        ->and($article->sort_order)->toBe(2)
        ->and($article->format)->toBe(ArticleFormat::Markdown)
        ->and($article->visibility)->toBe(Visibility::Public)
        ->and($article->is_published)->toBeTrue()
        ->and($article->keywords)->toBe(['people'])
        ->and($article->related)->toBe([])
        ->and($article->parent_id)->toBeNull()
        ->and($article->created_by)->toBe($user->id)
        ->and($article->updated_by)->toBe($user->id);

    $translation = ArticleTranslation::query()->where('article_id', $article->id)->sole();

    expect($translation->locale)->toBe('en')
        ->and($translation->title)->toBe('Users')
        ->and($translation->excerpt)->toBe('Manage users.')
        ->and($translation->body)->toBe('How users work.')
        ->and($translation->search_text)->not->toBeNull()
        ->and(ArticleRevision::query()->count())->toBe(0);
});

it('saves an edit through the form and records the revision with the panel user id', function (): void {
    enableRevisions(true);
    $author = finCodexFormUser('Author');
    $editor = finCodexFormUser('Second');

    $article = app(ArticleWriter::class)->create(finCodexFormState([
        'visibility' => Visibility::Authenticated->value,
    ]), $author->id);

    $this->usesPanel('admin', $editor);

    Livewire::test(EditArticle::class, ['record' => $article->getRouteKey()])
        ->assertFormSet([
            'slug' => 'users',
            'translations.en.title' => 'Users',
            'format' => ArticleFormat::Markdown->value,
            'visibility' => Visibility::Authenticated->value,
        ])
        ->fillForm([
            'translations' => ['en' => ['title' => 'Users v2', 'body' => 'v2 body']],
            'keywords' => ['x', 'y'],
        ])
        ->call('save')
        ->assertHasNoFormErrors();

    $article->refresh();
    $translation = ArticleTranslation::query()->where('article_id', $article->id)->sole();
    $revision = ArticleRevision::query()->sole();

    expect($article->created_by)->toBe($author->id)
        ->and($article->updated_by)->toBe($editor->id)
        ->and($article->keywords)->toBe(['x', 'y'])
        ->and($translation->title)->toBe('Users v2')
        ->and($translation->body)->toBe('v2 body')
        ->and($translation->search_text)->toContain('x')
        ->and($revision->user_id)->toBe($editor->id)
        ->and($revision->reason)->toBe(RevisionReason::Manual)
        ->and($revision->body)->toBe('How users work.');
});

it('writes nothing of its own when the writer fails', function (): void {
    $user = finCodexFormUser();
    $article = app(ArticleWriter::class)->create(finCodexFormState(), $user->id);

    $this->usesPanel('admin', $user);

    // A stand-in bound in the container rather than a Mockery double:
    // ArticleWriter is final, so Mockery cannot subclass it (05-01 hit the
    // same wall with HtmlToMarkdown). The atomicity proof is 05-01's; this
    // row only proves the page delegates and never writes on its own.
    $this->instance(ArticleWriter::class, new class
    {
        /**
         * @param  array<string, mixed>  $data
         */
        public function update(Article $article, array $data, ?int $userId): Article
        {
            throw new RuntimeException('boom');
        }
    });

    expect(fn () => Livewire::test(EditArticle::class, ['record' => $article->getRouteKey()])
        ->fillForm(['slug' => 'people', 'keywords' => ['gone']])
        ->call('save'))->toThrow(RuntimeException::class, 'boom');

    $article->refresh();

    expect($article->slug)->toBe('users')
        ->and($article->keywords)->toBe(['people']);
});

it('requires the default-language title and body', function (): void {
    $user = finCodexFormUser();
    $this->usesPanel('admin', $user);

    Livewire::test(CreateArticle::class)
        ->fillForm(finCodexFormState(['translations' => ['en' => ['title' => '']]]))
        ->call('create')
        ->assertHasFormErrors(['translations.en.title' => 'required']);

    Livewire::test(CreateArticle::class)
        ->fillForm(finCodexFormState(['translations' => ['en' => ['body' => '']]]))
        ->call('create')
        ->assertHasFormErrors(['translations.en.body' => 'required']);

    expect(Article::query()->count())->toBe(0);
});

it('leaves the other languages optional', function (): void {
    finCodexFormUseLanguages(['en', 'de']);
    $user = finCodexFormUser();
    $this->usesPanel('admin', $user);

    Livewire::test(CreateArticle::class)
        ->fillForm(finCodexFormState(['translations' => [
            'de' => ['title' => null, 'excerpt' => null, 'body' => null],
        ]]))
        ->call('create')
        ->assertHasNoFormErrors();

    $article = Article::query()->where('slug', 'users')->sole();

    expect(ArticleTranslation::query()->where('article_id', $article->id)->pluck('locale')->all())->toBe(['en']);
});

it('builds one tab per configured language in settings order with the default active', function (): void {
    finCodexFormUseLanguages(['hu', 'de', 'en'], 'de');
    $user = finCodexFormUser();
    $this->usesPanel('admin', $user);

    $component = Livewire::test(CreateArticle::class);
    $html = $component->html();

    $positions = array_map(
        fn (string $code): int|false => strpos($html, finCodexFormDisplay($code)),
        ['hu', 'de', 'en'],
    );

    expect($positions[0])->not->toBeFalse()
        ->and($positions[1])->toBeGreaterThan($positions[0])
        ->and($positions[2])->toBeGreaterThan($positions[1])
        ->and($html)->toContain('🇩🇪')
        ->and($component->instance()->activeLocale)->toBe('de');
});

it('fills every translation tab on edit and keeps untouched tabs stored', function (): void {
    finCodexFormUseLanguages(['en', 'de']);
    $user = finCodexFormUser();

    $article = app(ArticleWriter::class)->create(finCodexFormState(['translations' => [
        'de' => ['title' => 'Benutzer', 'excerpt' => 'Benutzer verwalten.', 'body' => 'So funktionieren Benutzer.'],
    ]]), $user->id);

    $german = ArticleTranslation::query()->where('article_id', $article->id)->where('locale', 'de')->sole();
    $touchedAt = $german->updated_at;

    $this->usesPanel('admin', $user);

    $this->travel(5)->seconds();

    Livewire::test(EditArticle::class, ['record' => $article->getRouteKey()])
        ->assertFormSet([
            'translations.en.title' => 'Users',
            'translations.de.title' => 'Benutzer',
            'translations.de.body' => 'So funktionieren Benutzer.',
        ])
        ->fillForm(['translations' => ['en' => ['excerpt' => 'Manage the people who sign in.']]])
        ->call('save')
        ->assertHasNoFormErrors();

    $english = ArticleTranslation::query()->where('article_id', $article->id)->where('locale', 'en')->sole();

    expect($english->excerpt)->toBe('Manage the people who sign in.')
        ->and($german->fresh()->updated_at->equalTo($touchedAt))->toBeTrue()
        ->and($german->fresh()->body)->toBe('So funktionieren Benutzer.');
});

it('works on the staff panel through the override', function (): void {
    $user = finCodexFormUser();
    $this->usesPanel('staff', $user);

    Livewire::test(CreateArticle::class)
        ->fillForm(finCodexFormState())
        ->call('create')
        ->assertHasNoFormErrors();

    $article = Article::query()->where('slug', 'users')->sole();

    expect(Filament::getCurrentPanel()?->getId())->toBe('staff')
        ->and($article->created_by)->toBe($user->id)
        ->and($article->updated_by)->toBe($user->id);
});
