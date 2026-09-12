<?php

declare(strict_types=1);

namespace FinityLabs\FinCodex\Pages;

use Filament\Actions\Action;
use Filament\Facades\Filament;
use Filament\Pages\Page;
use Filament\Panel;
use Filament\Schemas\Components\Component;
use Filament\Schemas\Components\Grid;
use Filament\Schemas\Components\Group;
use Filament\Schemas\Schema;
use Filament\Support\Enums\Width;
use FinityLabs\FinSupport\Pages\Concerns\HasPageShieldSupport;
use FinityLabs\LinCodex\Data\TreeNode;
use FinityLabs\LinCodex\Livewire\Concerns\CapturesPageHelp;
use FinityLabs\LinCodex\Livewire\Concerns\SearchesArticles;
use FinityLabs\LinCodex\Reading\ArticleReader;
use FinityLabs\LinCodex\Reading\ReadArticle;
use FinityLabs\LinCodex\Reading\TreeBuilder;
use FinityLabs\LinCodex\View\PageHelpResolver;
use Illuminate\Contracts\Support\Htmlable;
use Illuminate\Support\Str;

/**
 * The Help Center: the whole help library inside the panel, at {panel}/help and
 * {panel}/help/{slug}, behind the panel's own auth and guard.
 *
 * ONE page class on ONE route. The slug is a single optional wildcard
 * parameter, because article slugs are path-like (account/signing-in), so
 * {panel}/help, {panel}/help/intro and {panel}/help/account/signing-in are all
 * this class. Filament builds a page's route from getRoutePath() and exposes no
 * hook for the route's own where(), so the parameter's pattern is declared
 * globally by the service provider — see SLUG_PARAMETER below.
 *
 * There is no Blade view and no custom layout: content() returns the whole
 * three-column page out of schema components and the framework's own page view
 * renders it, exactly as HelpCoverage does. A rail on the left (the contents
 * tree and the search), the article in the middle, "On this page" on the right.
 *
 * Registered on EVERY panel that carries the plugin, authoring(false)
 * included: that flag means "this panel only reads help", and this page is the
 * reading surface the button, the drawer and the field hints point at.
 *
 * No navigation item in this phase — shouldRegisterNavigation() answers false
 * and thereby discards the Shield trait's version — so the page is reachable by
 * URL only until the phase that decides where it belongs in the menu.
 *
 * Not final: a helpCenterPage() override extends it, exactly as HelpSettings
 * and HelpCoverage are open for their own options.
 *
 * Access goes through HasPageShieldSupport, like the other two pages: Shield's
 * own permission when Shield is installed, the opt-in Gate ability
 * page_HelpCenter when it is not, open to any panel user otherwise.
 */
class HelpCenter extends Page
{
    use CapturesPageHelp;
    use HasPageShieldSupport;
    use SearchesArticles;

    protected static ?string $slug = 'help';

    /**
     * The route parameter name, distinctive on purpose: the pattern that lets
     * it hold slashes is registered globally on the router, so the name has to
     * be one no host route would pick by accident.
     */
    public const SLUG_PARAMETER = 'codexSlug';

    /** Three columns need the room; the article text is not capped to a reading measure. */
    protected Width|string|null $maxContentWidth = Width::Full;

    /** The article being read, or null on the landing. Comes from the route. */
    public ?string $codexSlug = null;

    /** The rail's tab strip, bound through Tabs::livewireProperty(). */
    public string $tab = 'contents';

    /** The panel scope filter. Null means "the normal rule". */
    public ?string $panelFilter = null;

    /** Whether read() has been asked yet; the answer itself may legitimately be null. */
    private bool $readResolved = false;

    private ?ReadArticle $readMemo = null;

    /** @var list<TreeNode>|null */
    private ?array $treeMemo = null;

    /**
     * The landing and every article on one route.
     *
     * Only getRoutePath() is overridden. getRelativeRouteName() reads
     * getSlug(), not this, so the route name stays filament.{panel}.pages.help;
     * and routes() is deliberately left alone — its signature changed inside
     * the Filament 4 line and this package supports both majors, so an override
     * could not be written once for the whole supported range.
     */
    public static function getRoutePath(Panel $panel): string
    {
        return '/'.static::getSlug($panel).'/{'.self::SLUG_PARAMETER.'?}';
    }

    /**
     * No navigation item: the page is reachable by URL only for now. Declaring
     * the method here discards the Shield trait's version, which is what this
     * phase wants; canAccess() from the trait is untouched.
     */
    public static function shouldRegisterNavigation(): bool
    {
        return false;
    }

    public function mount(PageHelpResolver $resolver, ?string $codexSlug = null): void
    {
        $panel = Filament::getCurrentPanel();

        $this->capturePageHelp($resolver, static::class, $panel?->getId(), null, $panel?->getAuthGuard());
        $this->codexSlug = blank($codexSlug) ? null : $codexSlug;
    }

    /** The browser tab follows the article, so open tabs and bookmarks stay legible. */
    public function getTitle(): string|Htmlable
    {
        return $this->read()?->translation->title ?? (string) __('fin-codex::fin-codex.help_center.title');
    }

    /**
     * Always "Help": the page looks like every other panel page, and the
     * article's own title lives in the article column, which is a different
     * level rather than a repeat. BasePage::getHeading() defaults to
     * getTitle(), so both have to be declared to get the two apart.
     */
    public function getHeading(): string|Htmlable|null
    {
        return (string) __('fin-codex::fin-codex.help_center.title');
    }

    /**
     * The whole page, out of schema components.
     *
     * The headings column is built first and the same list decides whether the
     * column renders at all, so the three empty cases — no article, an article
     * without headings, and a slug that found nothing — all hide the column
     * rather than leaving an empty box in the grid.
     */
    public function content(Schema $schema): Schema
    {
        $headings = $this->headingsComponents();

        return $schema->components([
            Grid::make(['default' => 1, 'lg' => 12])->schema([
                Group::make($this->railComponents())
                    ->columnSpan(['default' => 1, 'lg' => 3])
                    ->extraAttributes(['class' => 'fin-codex-help__rail']),
                Group::make($this->articleComponents())
                    ->columnSpan(['default' => 1, 'lg' => 6])
                    ->extraAttributes(['class' => 'fin-codex-help__article']),
                Group::make($headings)
                    ->columnSpan(['default' => 1, 'lg' => 3])
                    ->extraAttributes(['class' => 'fin-codex-help__toc'])
                    ->hidden($headings === []),
            ]),
        ]);
    }

    /**
     * The left rail: the search box, the tab strip, the contents tree and the
     * panel filter. Empty here — the rail is the next plan's, and the route,
     * the article and the headings are all provable without it.
     *
     * @return list<Component>
     */
    private function railComponents(): array
    {
        return [];
    }

    /**
     * The middle column: breadcrumbs, the title, the fallback notice, the body
     * and the related articles — or the not-found, empty and landing states.
     * Filled by task 2 of this plan.
     *
     * @return list<Component>
     */
    private function articleComponents(): array
    {
        return [];
    }

    /**
     * The right column: "On this page", one entry per heading the renderer
     * anchored. Filled by task 3 of this plan.
     *
     * @return list<Component>
     */
    private function headingsComponents(): array
    {
        return [];
    }

    /**
     * The article being read, asked for once per request.
     *
     * Memoised because getTitle() and content() both want it and Filament may
     * build the content schema twice in one request. Deliberately NOT wrapped
     * in the panel scope filter: an article already open stays readable when
     * the filter moves to a panel that cannot see it.
     */
    protected function read(): ?ReadArticle
    {
        if (! $this->readResolved) {
            $this->readResolved = true;

            $this->readMemo = blank($this->codexSlug)
                ? null
                : app(ArticleReader::class)->read($this->codexSlug, $this->viewer(), $this->locale);
        }

        return $this->readMemo;
    }

    /**
     * Everything this viewer may read, as a tree, asked for once per request.
     *
     * @return list<TreeNode>
     */
    protected function tree(): array
    {
        return $this->treeMemo ??= app(TreeBuilder::class)->build($this->viewer(), $this->locale);
    }

    /**
     * A link to another article: a real anchor with an href, never a Livewire
     * action, so the address bar is always right, the back button walks
     * articles and a reader can copy or open the link in a new tab.
     *
     * Named after the slug the way the drawer names its own, so two links to
     * one article in one schema do not collide.
     */
    protected function linkTo(string $slug, string $label): Action
    {
        return Action::make('open-'.Str::slug(str_replace('/', '-', $slug)))
            ->link()
            ->label($label)
            ->url(static::getUrl([self::SLUG_PARAMETER => $slug]))
            ->extraAttributes(['data-fin-codex-help-node' => $slug]);
    }
}
