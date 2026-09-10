<?php

use Filament\Forms\Components\CheckboxList;
use FinityLabs\FinCodex\Resources\ArticleResource\Pages\ListArticles;
use FinityLabs\FinCodex\Resources\ArticleResource\Schemas\TranslationTabs;
use FinityLabs\FinCodex\Tests\Fixtures\User;
use FinityLabs\LinCodex\Models\Article;
use FinityLabs\LinCodex\Models\ArticleTranslation;
use FinityLabs\LinCodex\Settings\CodexSettings;
use Illuminate\Contracts\Support\Htmlable;
use Livewire\Features\SupportTesting\Testable;
use Livewire\Livewire;

/*
 * AIBULK-02 and the bulk half of AIBULK-04: Translate missing over a selection.
 *
 * Every row drives the article list, so what is asserted is what the admin
 * gets: the toolbar button is resolved out of the live table and pressed
 * through the page, never called by hand. Unlike the row action's file, this
 * one never opens an editor - the whole point of the bulk action is to fill
 * every gap in the selection from the list.
 *
 * A hidden bulk action takes the checkbox column with it, so the AI-off row
 * asserts the markup as well as the button: with AI unavailable the list looks
 * exactly as it did before the slot existed.
 */

/** A fixture user signed in on the admin panel; the same row on every call. */
function finCodexBulkUser(string $name = 'Bulk'): User
{
    return User::firstOrCreate(
        ['email' => strtolower($name).'@example.com'],
        ['name' => $name],
    );
}

/**
 * The languages this file's articles are written in. Its own helper rather
 * than a borrowed one: a Pest helper is loaded with its file, so a helper from
 * another test file is undefined the moment this one runs on its own.
 *
 * @param  list<string>  $codes
 */
function finCodexBulkUseLanguages(array $codes = ['en', 'de', 'hu'], string $default = 'en'): void
{
    $settings = app(CodexSettings::class);
    $settings->languages = array_map([CodexSettings::class, 'languageEntry'], $codes);
    $settings->default_locale = $default;
    $settings->save();
}

/**
 * An article with one translation row per entry of $translations. A '' title
 * writes a blank row - present in the table, still counted as missing, which
 * is what an abandoned half-translation looks like.
 *
 * @param  array<string, string>  $translations  locale => title
 */
function finCodexBulkArticle(string $slug, array $translations): Article
{
    $article = Article::factory()->public()->published()->markdown()->create(['slug' => $slug]);

    foreach ($translations as $locale => $title) {
        ArticleTranslation::factory()->create([
            'article_id' => $article->id,
            'locale' => $locale,
            'title' => $title,
            'body' => $title === '' ? '' : $title.' body',
        ]);
    }

    return $article;
}

/**
 * The mounted bulk modal's language options, code => display, read off the
 * live CheckboxList rather than out of the rendered HTML: the list's own
 * flag tooltips already carry every display name.
 *
 * @return array<string, string>
 */
function finCodexBulkOptions(Testable $component): array
{
    $page = $component->instance();
    $schema = $page->getSchema((string) $page->getMountedActionSchemaName());
    $locales = $schema?->getFlatFields()['locales'] ?? null;

    expect($locales)->toBeInstanceOf(CheckboxList::class);

    /** @var CheckboxList $locales */
    return $locales->getOptions();
}

/**
 * The expected options for $codes, in that order, with the display names the
 * table itself reads.
 *
 * @param  list<string>  $codes
 *
 * @return array<string, string>
 */
function finCodexBulkDisplays(array $codes): array
{
    $displays = array_column(TranslationTabs::languages()['languages'], 'display', 'code');

    $expected = [];

    foreach ($codes as $code) {
        $expected[$code] = (string) $displays[$code];
    }

    return $expected;
}

/** The mounted bulk action's modal description. */
function finCodexBulkDescription(Testable $component): string
{
    $description = $component->instance()->getMountedAction()?->getModalDescription();

    return $description instanceof Htmlable ? $description->toHtml() : (string) $description;
}

beforeEach(function (): void {
    finCodexBulkUseLanguages();
    finCodexFakeAi();
    finCodexEnableAi();
    $this->usesPanel('admin', finCodexBulkUser());
});

it('is the table\'s first bulk action while AI is available and hidden otherwise', function (): void {
    finCodexBulkArticle('billing', ['en' => 'Billing']);

    $component = Livewire::test(ListArticles::class)
        ->assertTableBulkActionExists('translate_missing')
        ->assertTableBulkActionVisible('translate_missing')
        ->assertTableBulkActionDoesNotExist('delete');

    expect($component->html())->toContain('fi-ta-selection-cell');

    finCodexEnableAi(['enabled' => false]);

    $off = Livewire::test(ListArticles::class)
        ->assertTableBulkActionHidden('translate_missing');

    expect($off->html())->not->toContain('fi-ta-selection-cell');
});

it('offers every configured non-default language pre-checked, in settings order', function (): void {
    $gapDe = finCodexBulkArticle('billing', ['en' => 'Billing', 'hu' => 'Számlázás']);
    $gapBoth = finCodexBulkArticle('users', ['en' => 'Users']);

    $component = Livewire::test(ListArticles::class)
        ->mountTableBulkAction('translate_missing', [$gapDe, $gapBoth])
        ->assertTableBulkActionDataSet(['locales' => ['de', 'hu']]);

    expect(finCodexBulkOptions($component))->toBe(finCodexBulkDisplays(['de', 'hu']));

    finCodexBulkUseLanguages(['en', 'hu', 'de']);

    $reordered = Livewire::test(ListArticles::class)
        ->mountTableBulkAction('translate_missing', [$gapDe, $gapBoth])
        ->assertTableBulkActionDataSet(['locales' => ['hu', 'de']]);

    expect(finCodexBulkOptions($reordered))->toBe(finCodexBulkDisplays(['hu', 'de']))
        ->and(finCodexBulkOptions($reordered))->not->toHaveKey('en');
});

it('names how many articles are selected and says the work runs in the background', function (): void {
    $gapDe = finCodexBulkArticle('billing', ['en' => 'Billing', 'hu' => 'Számlázás']);
    $gapBoth = finCodexBulkArticle('users', ['en' => 'Users']);

    $two = finCodexBulkDescription(
        Livewire::test(ListArticles::class)->mountTableBulkAction('translate_missing', [$gapDe, $gapBoth]),
    );

    expect($two)->toBe(trans_choice('fin-codex::fin-codex.editor.translate_missing.bulk_description', 2, ['count' => 2]))
        ->and($two)->toContain('2 articles are selected')
        ->and($two)->toContain('runs in the background');

    $one = finCodexBulkDescription(
        Livewire::test(ListArticles::class)->mountTableBulkAction('translate_missing', [$gapDe]),
    );

    expect($one)->toBe(trans_choice('fin-codex::fin-codex.editor.translate_missing.bulk_description', 1, ['count' => 1]))
        ->and($one)->toContain('One article is selected')
        ->and($one)->toContain('runs in the background');
});
