<?php

declare(strict_types=1);

namespace FinityLabs\FinCodex\Pages;

use Filament\Actions\Action;
use Filament\Facades\Filament;
use Filament\Pages\Page;
use Filament\Panel;
use Filament\Schemas\Components\Actions;
use Filament\Schemas\Components\Component;
use Filament\Schemas\Components\Grid;
use Filament\Schemas\Components\Group;
use Filament\Schemas\Components\Html;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Components\Text;
use Filament\Schemas\Components\UnorderedList;
use Filament\Schemas\Schema;
use Filament\Support\Enums\FontWeight;
use Filament\Support\Enums\TextSize;
use Filament\Support\Enums\Width;
use Filament\Support\Icons\Heroicon;
use FinityLabs\FinCodex\Auth\ArticleAbility;
use FinityLabs\FinCodex\FinCodexPlugin;
use FinityLabs\FinSupport\Pages\Concerns\HasPageShieldSupport;
use FinityLabs\LinCodex\Data\TreeNode;
use FinityLabs\LinCodex\Livewire\Concerns\CapturesPageHelp;
use FinityLabs\LinCodex\Livewire\Concerns\SearchesArticles;
use FinityLabs\LinCodex\Reading\ArticleReader;
use FinityLabs\LinCodex\Reading\ReadArticle;
use FinityLabs\LinCodex\Reading\TreeBuilder;
use FinityLabs\LinCodex\View\PageHelpResolver;
use Illuminate\Contracts\Support\Htmlable;
use Illuminate\Support\HtmlString;
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
     * The middle column: the article, or one of the three states it can be in.
     *
     * The states are checked in this order and the order is the decision. A set
     * slug that finds nothing is a not-found even when nothing at all is
     * readable, because the reader asked for something specific; an empty tree
     * then wins over the landing, because "nothing has been written yet" is more
     * use than "pick a topic" when there are no topics.
     *
     * @return list<Component>
     */
    private function articleComponents(): array
    {
        $read = $this->read();

        if ($read === null) {
            return match (true) {
                $this->codexSlug !== null => $this->notFoundComponents(),
                $this->tree() === [] => $this->emptyComponents(),
                default => $this->landingComponents(),
            };
        }

        $components = [];

        if ($read->breadcrumbs !== []) {
            $components[] = Actions::make(array_map(
                fn (array $crumb): Action => $this->linkTo($crumb['slug'], $crumb['title'])->size('sm')->color('gray'),
                $read->breadcrumbs,
            ));
        }

        $components[] = Text::make($read->translation->title)
            ->weight(FontWeight::Bold)
            ->size(TextSize::Large);

        $notice = $this->fallbackNoticeFor($read);

        if (is_string($notice)) {
            // The drawer's treatment: a small warning-coloured line between the
            // title and the body, not a bordered callout that takes room from
            // the article.
            $components[] = Text::make($notice)->color('warning')->size(TextSize::Small);
        }

        $components[] = $this->bodyComponent($read);

        if ($read->related !== []) {
            // At the bottom, where a reader meets them after reading, rather
            // than competing with the headings rail for the right column.
            $components[] = Section::make(__('lin-codex::lin-codex.ui.related'))
                ->compact()
                ->schema([Actions::make(array_map(
                    fn (array $entry): Action => $this->linkTo($entry['slug'], $entry['title']),
                    $read->related,
                ))]);
        }

        return $components;
    }

    /**
     * The rendered body, its style scope and its lightbox, in one component.
     *
     * The codex-root wrapper is not optional. The core stylesheet defines every
     * token the body rules consume on .codex-root and .codex-help-button only,
     * while the body rules themselves are unscoped — and a Filament page carries
     * neither class anywhere. Without this wrapper callouts, steps, figures,
     * code blocks and tables render with no colours, borders or spacing, and the
     * panel's dark mode does nothing to them. fin-codex's own token remap in the
     * panel head view is keyed on the same two selectors.
     *
     * The lightbox is the core's markup, the core's classes and the renderer's
     * own marker attribute on every image, with a small inline Alpine object of
     * this page's own. The drawer's Alpine component cannot be reused: it is
     * built entirely around the drawer's open and close state and watches it as
     * it initialises, and its partial has to live inside a script block in a
     * component's own Blade view, which this page deliberately does not have.
     *
     * Downloads need nothing here: the core renderer already wrote them into the
     * body HTML.
     */
    private function bodyComponent(ReadArticle $read): Html
    {
        $closeLabel = e((string) __('lin-codex::lin-codex.ui.lightbox_close'));

        return Html::make(new HtmlString(
            '<div class="codex-root" x-data="{ lightbox: null, lightboxAlt: \'\' }"'
            .' x-on:click="const i = $event.target.closest(\'img[data-codex-lightbox]\'); if (i) { lightbox = i.currentSrc || i.src; lightboxAlt = i.alt || \'\' }"'
            .' x-on:keydown.escape.window="lightbox = null">'
            .'<div class="codex-article__body" lang="'.e($read->locale).'">'.$read->rendered->html.'</div>'
            .'<template x-if="lightbox !== null">'
            .'<div class="codex-lightbox" role="dialog" aria-label="'.$closeLabel.'" x-on:click="lightbox = null">'
            .'<img class="codex-lightbox__image" x-bind:src="lightbox" x-bind:alt="lightboxAlt" alt="">'
            .'<button type="button" class="codex-lightbox__close" aria-label="'.$closeLabel.'" x-on:click="lightbox = null"></button>'
            .'</div></template></div>',
        ));
    }

    /**
     * A slug that is missing, hidden, unpublished or belongs to another panel —
     * all of which look alike on purpose.
     *
     * The response stays 200: the page exists and works, only the requested
     * article is unavailable, and a 200 keeps the rail and the search usable
     * beside it while telling a probe nothing about whether the article exists.
     *
     * @return list<Component>
     */
    private function notFoundComponents(): array
    {
        return [Text::make(__('lin-codex::lin-codex.ui.not_found'))->color('gray')];
    }

    /**
     * Bare {panel}/help: the core's invitation and the top level of the tree.
     *
     * The rail carries the whole tree, but the middle is the largest area on
     * screen and one grey sentence wastes the first thing a reader sees. A node
     * that is a folder group has no article to open, so it is named rather than
     * linked.
     *
     * @return list<Component>
     */
    private function landingComponents(): array
    {
        $components = [Text::make(__('lin-codex::lin-codex.ui.pick_a_topic'))->color('gray')];

        foreach ($this->tree() as $node) {
            $components[] = $node->isGroup()
                ? Text::make($node->label)->weight(FontWeight::Medium)
                : Actions::make([$this->linkTo($node->slug, $node->label)]);
        }

        return $components;
    }

    /**
     * Nothing readable at all: a fresh install, or a viewer the scoping hides
     * everything from.
     *
     * The editor link is gated twice and both halves are needed. The ability is
     * the obvious one. The second is that the article resource is actually
     * registered on THIS panel: this page lives on every panel, while the
     * resource only goes on the panels that author, so building the create URL
     * on a reading-only panel would raise a missing-route error. It is a link
     * with a URL rather than a redirecting action, so nothing here touches
     * Livewire's back-button-cache flag.
     *
     * @return list<Component>
     */
    private function emptyComponents(): array
    {
        $components = [
            Text::make(__('fin-codex::fin-codex.help_center.empty'))
                ->weight(FontWeight::Bold)
                ->size(TextSize::Large),
            Text::make(__('fin-codex::fin-codex.help_center.empty_description'))->color('gray'),
        ];

        $resource = FinCodexPlugin::articleResourceClass();

        if (ArticleAbility::allows('create') && in_array($resource, Filament::getCurrentPanel()?->getResources() ?? [], true)) {
            $components[] = Actions::make([
                Action::make('write-first-article')
                    ->link()
                    ->icon(Heroicon::OutlinedPencilSquare)
                    ->label(__('fin-codex::fin-codex.help_center.write_article'))
                    ->url($resource::getUrl('create')),
            ]);
        }

        return $components;
    }

    /**
     * The right column: "On this page", one entry per heading of the article.
     *
     * Plain anchors on purpose: the browser jumps and puts the heading id in the
     * hash for free, no JavaScript of ours, and a heading link can be copied
     * out. The table of contents is the renderer's own — second and third level
     * only, in document order, with the ids it already wrote into the body — so
     * nothing here parses the HTML for them.
     *
     * An empty list here is what hides the whole column: see content().
     *
     * @return list<Component>
     */
    private function headingsComponents(): array
    {
        $toc = $this->read()?->rendered->toc ?? [];

        if ($toc === []) {
            return [];
        }

        return [
            Section::make(__('lin-codex::lin-codex.ui.on_this_page'))
                ->compact()
                ->schema([UnorderedList::make(array_map(
                    static fn (array $entry): Text => Text::make(new HtmlString('<a href="#'.e($entry['id']).'">'.e($entry['text']).'</a>'))->size(TextSize::Small),
                    $toc,
                ))]),
        ];
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
