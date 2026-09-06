<?php

use Filament\Facades\Filament;
use FinityLabs\FinCodex\Coverage\CoverageReport;
use FinityLabs\FinCodex\Enums\NavigationGroup;
use FinityLabs\FinCodex\Pages\HelpCoverage;
use FinityLabs\FinCodex\Tests\Fixtures\Pages\AdminHelpCoverage;
use FinityLabs\FinCodex\Tests\Fixtures\Pages\StaffHelpCoverage;
use FinityLabs\FinCodex\Tests\Fixtures\Resources\UserResource;
use FinityLabs\FinCodex\Tests\Fixtures\User;
use FinityLabs\LinCodex\Contracts\ContentSource;
use FinityLabs\LinCodex\Data\ArticleData;
use FinityLabs\LinCodex\Enums\ContextType;
use FinityLabs\LinCodex\Models\Article;
use FinityLabs\LinCodex\Settings\CodexSettings;
use Illuminate\Support\Str;
use Livewire\Livewire;
use Spatie\LaravelSettings\Exceptions\MissingSettings;

/*
 * COV-01 and COV-03 on a rendered page: every panel that carries the plugin
 * gets a coverage page, it lists one row per screen with the gap at the top,
 * both filters are independent, and the sidebar number is the number of
 * uncovered rows the page opens with.
 *
 * The page is registered by FinCodexPlugin::register() beside the settings
 * page: admin and staff name real fixture subclasses through coveragePage()
 * (what a host does), portal keeps the built-in class, and the plain panel,
 * which carries no plugin, gets nothing.
 *
 * Helpers are prefixed finCodexPage* because finCodexCoverage* is taken by
 * CoverageReportTest and Pest helpers are global.
 */

/** A fixture user signed in on the given panel guard. */
function finCodexPageUser(string $guard = 'web'): User
{
    $user = User::create(['name' => 'Admin', 'email' => 'admin@example.com']);

    test()->actingAs($user, $guard);

    return $user;
}

/** A public article with one English translation and, optionally, one context. */
function finCodexPageArticle(string $slug, ?ContextType $type = null, ?string $key = null, ?string $panelId = null): Article
{
    $factory = Article::factory()->public()
        ->withTranslation('en', ['title' => Str::headline($slug), 'body' => 'About '.$slug.'.']);

    if ($type !== null && $key !== null) {
        $factory = $factory->withContext($type, $key, $panelId, 0);
    }

    return $factory->create(['slug' => $slug]);
}

/**
 * A content source that cannot be read at all — the only way to reach
 * CoverageReport's rescue and therefore the only honest way to ask for a
 * report with no rows in it. Deleting the settings group does NOT do it:
 * lin-codex's DefaultLocale already falls back to config('app.locale'), which
 * 07-01 measured.
 */
function finCodexPageBrokenSource(): ContentSource
{
    return new class implements ContentSource
    {
        public function all(): array
        {
            throw MissingSettings::create(CodexSettings::class, ['default_locale'], 'loading');
        }

        public function findBySlug(string $slug): ?ArticleData
        {
            return null;
        }

        public function tree(): array
        {
            return [];
        }

        public function findByContext(ContextType $type, string $key, ?string $panelId = null): array
        {
            return [];
        }

        public function allForSearch(): array
        {
            return [];
        }

        public function warnings(): array
        {
            return [];
        }
    };
}

/*
 * -----------------------------------------------------------------------
 * Registration, routing, navigation and the badge.
 * -----------------------------------------------------------------------
 */

it('registers the override on admin and staff and the built-in page on portal, nothing on plain', function (): void {
    expect(array_values(Filament::getPanel('admin')->getPages()))
        ->toContain(AdminHelpCoverage::class)
        ->not->toContain(HelpCoverage::class)
        ->and(array_values(Filament::getPanel('staff')->getPages()))
        ->toContain(StaffHelpCoverage::class)
        ->not->toContain(HelpCoverage::class)
        ->and(array_values(Filament::getPanel('portal')->getPages()))
        ->toContain(HelpCoverage::class)
        ->and(array_values(Filament::getPanel('plain')->getPages()))
        ->not->toContain(HelpCoverage::class)
        ->not->toContain(AdminHelpCoverage::class)
        ->not->toContain(StaffHelpCoverage::class);
});

it('answers on its own route for a signed-in admin', function (): void {
    finCodexPageUser();

    $url = route('filament.admin.pages.help-coverage');

    expect($url)->toContain('/admin/help-coverage');

    $this->get($url)->assertOk();
});

it('files two slots after the article resource on every panel that sets a sort', function (): void {
    Filament::setCurrentPanel(Filament::getPanel('admin'));

    expect(AdminHelpCoverage::getNavigationGroup())->toBe('Help')
        ->and(AdminHelpCoverage::getNavigationSort())->toBe(92);

    Filament::setCurrentPanel(Filament::getPanel('staff'));

    expect(StaffHelpCoverage::getNavigationGroup())->toBe('Support')
        ->and(StaffHelpCoverage::getNavigationSort())->toBe(7);

    Filament::setCurrentPanel(Filament::getPanel('portal'));

    expect(HelpCoverage::getNavigationGroup())->toBe(NavigationGroup::Help)
        ->and(HelpCoverage::getNavigationSort())->toBeNull();
});

it('reads its navigation label, title and badge tooltip from the lang files and follows the locale', function (): void {
    Filament::setCurrentPanel(Filament::getPanel('admin'));

    $englishLabel = (string) __('fin-codex::fin-codex.coverage.navigation');
    $englishTitle = (string) __('fin-codex::fin-codex.coverage.title');

    expect(AdminHelpCoverage::getNavigationLabel())->toBe($englishLabel)
        ->and((new AdminHelpCoverage)->getTitle())->toBe($englishTitle)
        ->and(AdminHelpCoverage::getNavigationBadgeTooltip())->toBe((string) __('fin-codex::fin-codex.coverage.badge_tooltip'));

    app()->setLocale('de');

    $germanLabel = (string) __('fin-codex::fin-codex.coverage.navigation');
    $germanTitle = (string) __('fin-codex::fin-codex.coverage.title');

    expect(AdminHelpCoverage::getNavigationLabel())->toBe($germanLabel)
        ->not->toBe($englishLabel)
        ->and((new AdminHelpCoverage)->getTitle())->toBe($germanTitle)
        ->not->toBe($englishTitle);
});

it('carries this panel\'s uncovered count on the navigation item', function (): void {
    // One covered admin screen, so the badge is not trivially the whole report.
    finCodexPageArticle('users-guide', ContextType::PageClass, UserResource::class, 'admin');

    Filament::setCurrentPanel(Filament::getPanel('admin'));
    forgetHelpMemo();

    $uncovered = app(CoverageReport::class)->uncovered('admin');

    expect($uncovered)->toBeGreaterThan(0)
        ->and(AdminHelpCoverage::getNavigationBadge())->toBe((string) $uncovered)
        ->and(AdminHelpCoverage::getNavigationBadgeColor())->toBe('warning');
});

it('shows no badge at all when nothing is uncovered', function (): void {
    Filament::setCurrentPanel(Filament::getPanel('admin'));

    expect(AdminHelpCoverage::getNavigationBadge())->not->toBeNull();

    // forgetHelpMemo() first: it forgets the ContentSource instance itself and
    // would undo the swap the other way round.
    forgetHelpMemo();
    app()->instance(ContentSource::class, finCodexPageBrokenSource());

    expect(app(CoverageReport::class)->uncovered('admin'))->toBe(0)
        ->and(AdminHelpCoverage::getNavigationBadge())->toBeNull();
});

it('mounts on the panel it was registered on', function (): void {
    $this->usesPanel('admin', finCodexPageUser());

    Livewire::test(AdminHelpCoverage::class)->assertOk();
});
