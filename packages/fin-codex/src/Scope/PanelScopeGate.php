<?php

declare(strict_types=1);

namespace FinityLabs\FinCodex\Scope;

use Filament\Facades\Filament;
use FinityLabs\FinCodex\Auth\ArticleAbility;
use FinityLabs\LinCodex\Auth\Viewer;
use FinityLabs\LinCodex\Contracts\ContentSource;
use FinityLabs\LinCodex\Data\ArticleData;
use FinityLabs\LinCodex\Locale\LocaleResolver;
use FinityLabs\LinCodex\Models\Article;
use FinityLabs\LinCodex\Sources\SlugPath;
use Illuminate\Contracts\Container\Container;
use Illuminate\Http\Request;
use InvalidArgumentException;

/**
 * The panel scope: what a reader inside one Filament panel may see.
 *
 * Installed as lin-codex's `auth.gate` hook when a fin-codex panel boots, so
 * every core read path — the drawer's page list, tree and search, the field
 * hints, global search, the reader and the context lookup — answers the same
 * way with no change of its own. The hook can only veto; published and
 * visibility are decided before it is asked and stay the core's.
 *
 * Four rules, applied to one article at a time:
 *
 * - **General.** An article with no contexts at all belongs everywhere and is
 *   shown in every panel.
 * - **Own panel.** An article with at least one context resolving into the
 *   current panel is shown. An article in two panels is shown in both.
 * - **Outside panels.** An article whose every context resolves into another
 *   panel, or into no panel at all (a plain Laravel route, an unregistered
 *   route name, a class no panel has, a pattern spanning panels), is hidden.
 *   Outside is not general: it is a real bucket and it is hidden everywhere.
 * - **Container.** A section carrying no contexts and no body of its own is
 *   shown only while at least one descendant survives the scoping,
 *   recursively.
 *
 * A section that *does* carry contexts is scoped exactly like an article, and
 * the core's ancestor rule then takes its whole subtree with it, general
 * children included. That is deliberate: an editor who wants an article
 * visible everywhere keeps it out of a panel-bound section.
 *
 * A container is a section with nothing of its own to read. `isSection` alone
 * would be too wide a net: the sources set it for an `index.md`/`index.html`
 * file **and** for any database article that has children, so a general
 * section an editor wrote a real page into would vanish — body and all — on a
 * panel where none of its descendants happens to survive. The body decides.
 * A section whose default-locale body is blank is a navigation shell and goes
 * where its children go; a section that carries one is an article like any
 * other and stays wherever the panel rule puts it.
 *
 * Descendants are counted by the panel rule alone. The hook is only ever asked
 * about articles that already passed published and visibility, but the map it
 * builds is the source's whole set, so a section whose only child is this
 * panel's but unpublished stays visible and empty — the behaviour shipped in
 * 0.4, left alone rather than quietly tightened here.
 *
 * The current panel is read at call time and never captured. A plugin boots
 * once per process under Testbench and once per request under Octane, while
 * this object outlives both; and the same worker serves the core's own routes,
 * where no panel is current and the core's answer has to stand unchanged. So
 * the current panel is asked for per call, and a null answer returns early.
 *
 * A host hook already sitting in the config slot is kept here as the inner
 * hook and runs first — either veto hides the article. It lives on this object
 * rather than in a second config key because the wrapping is an implementation
 * detail of taking a slot that already has a documented meaning: a host reads
 * and writes `lin-codex.auth.gate`, and giving it a second key to learn would
 * turn an invisible chaining into part of the package's surface.
 */
final class PanelScopeGate
{
    /** The hook that held lin-codex.auth.gate before this class took the slot. */
    private mixed $innerHook = null;

    private ?Request $memoRequest = null;

    /** @var array<string, bool> guard and user => the viewAllPanels answer */
    private array $viewAll = [];

    /** @var array<string, array<string, bool>> panel id => slug => verdict */
    private array $maps = [];

    public function __construct(
        private readonly Container $app,
        private readonly ContextPanels $panels,
        private readonly LocaleResolver $locales,
    ) {}

    /**
     * Remember what held the core's slot before us: null, an invokable class
     * name or a callable, which are the three shapes the core accepts.
     */
    public function wrap(mixed $inner): void
    {
        $this->innerHook = $inner;
    }

    /** What was in the slot before us, for the boot guard and for tests. */
    public function inner(): mixed
    {
        return $this->innerHook;
    }

    public function __invoke(Viewer $viewer, ArticleData $article): bool
    {
        if (! $this->allowedByHostHook($viewer, $article)) {
            return false;
        }

        $panelId = $this->currentPanelId();

        if ($panelId === null) {
            return true;
        }

        $this->rememberRequest();

        if ($this->viewsAllPanels($viewer)) {
            return true;
        }

        $map = $this->maps[$panelId] ??= $this->build($panelId);

        // An article the source does not know is not this rule's business.
        return $map[$article->slug] ?? true;
    }

    /**
     * The panel Filament is serving, or null outside every panel. Never the
     * default panel: a request the core serves belongs to no panel, and
     * answering with the default one would scope it to a panel nobody asked
     * for.
     */
    private function currentPanelId(): ?string
    {
        return Filament::getCurrentPanel()?->getId();
    }

    /**
     * The host's own hook, resolved by the same three-way rule the core
     * applies to the config value it replaced.
     */
    private function allowedByHostHook(Viewer $viewer, ArticleData $article): bool
    {
        $hook = $this->innerHook;

        if ($hook === null) {
            return true;
        }

        if (is_string($hook)) {
            $hook = $this->app->make($hook);
        }

        if (! is_callable($hook)) {
            throw new InvalidArgumentException('lin-codex.auth.gate (wrapped by fin-codex) must be null, an invokable class name or a callable.');
        }

        return (bool) $hook($viewer, $article);
    }

    /**
     * Whether this viewer reads every panel's help. Asked once per viewer per
     * request; a guest is never asked at all, because the ability takes a user
     * and a host that grants it is describing staff, not the public.
     */
    private function viewsAllPanels(Viewer $viewer): bool
    {
        $user = $viewer->user;

        if ($user === null) {
            return false;
        }

        $identifier = $user->getAuthIdentifier();
        $key = $viewer->guard.'|'.(is_scalar($identifier) ? (string) $identifier : spl_object_id($user));

        return $this->viewAll[$key] ??= ArticleAbility::allows('viewAllPanels', Article::class, $user);
    }

    /**
     * The verdict for every slug the source knows, for one panel.
     *
     * Two passes over the map sorted by slug, so an ancestor is always seen
     * before its descendants in the first and after them in the second. The
     * forward pass applies the panel rule and inherits a hidden ancestor's
     * answer, the way the core inherits its own; the reverse pass turns
     * childless containers off and lights up the ancestors of everything that
     * survived. Array lookups only — no query, and one reading of the source
     * per request, through the decorated binding so declared help counts.
     *
     * @return array<string, bool>
     */
    private function build(string $panelId): array
    {
        $all = $this->app->make(ContentSource::class)->all();
        ksort($all);

        $scoped = [];

        foreach ($all as $slug => $article) {
            $ancestor = $this->ancestorOf($slug, $all);
            $scoped[$slug] = $this->belongsTo($article, $panelId) && ($ancestor === null || ($scoped[$ancestor] ?? true));
        }

        $alive = [];
        $verdict = [];
        $defaultLocale = $this->locales->defaultLocale();

        foreach (array_reverse($all, true) as $slug => $article) {
            $isContainer = $article->isSection && $article->contexts === [] && $this->hasNoBody($article, $defaultLocale);
            $visible = ($scoped[$slug] ?? true) && (! $isContainer || ($alive[$slug] ?? false));

            if ($visible) {
                for ($ancestor = $this->ancestorOf($slug, $all); $ancestor !== null; $ancestor = $this->ancestorOf($ancestor, $all)) {
                    $alive[$ancestor] = true;
                }
            }

            $verdict[$slug] = $visible;
        }

        return $verdict;
    }

    /**
     * Whether the section has nothing of its own to read, which is what makes
     * it a container rather than an article with children.
     *
     * The body lives on the translation, and the default locale is the one an
     * article is written in first. A section written only in another language
     * is still read there — the sources warn about the missing default-locale
     * file rather than dropping the article — so its first translation stands
     * in, the same substitution the filesystem assembler makes. Whitespace is
     * not content.
     */
    private function hasNoBody(ArticleData $article, string $defaultLocale): bool
    {
        $translation = $article->translation($defaultLocale) ?? (array_values($article->translations)[0] ?? null);

        return blank($translation?->body);
    }

    /**
     * Whether any of the article's contexts resolves into this panel. An
     * article with no contexts is general and belongs to every panel.
     */
    private function belongsTo(ArticleData $article, string $panelId): bool
    {
        if ($article->contexts === []) {
            return true;
        }

        foreach ($article->contexts as $context) {
            if (in_array($panelId, $this->panels->forContext($context), true)) {
                return true;
            }
        }

        return false;
    }

    /**
     * The nearest slug up the path that is an article, skipping folder groups
     * exactly as the core's own ancestor walk does.
     *
     * @param  array<string, ArticleData>  $all
     */
    private function ancestorOf(string $slug, array $all): ?string
    {
        $parent = SlugPath::parentOf($slug);

        while ($parent !== null) {
            if (isset($all[$parent])) {
                return $parent;
            }

            $parent = SlugPath::parentOf($parent);
        }

        return null;
    }

    /**
     * Memoised per request instance rather than behind a flag, like
     * ArticleLookup and the declared-help decorator: this is a singleton, so
     * Octane drops it between requests but Testbench does not, and a different
     * request object is the signal to rebuild both memos.
     */
    private function rememberRequest(): void
    {
        /** @var Request $request */
        $request = $this->app->make('request');

        if ($this->memoRequest === $request) {
            return;
        }

        $this->memoRequest = $request;
        $this->viewAll = [];
        $this->maps = [];
    }
}
