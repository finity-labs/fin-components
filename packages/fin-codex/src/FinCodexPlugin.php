<?php

declare(strict_types=1);

namespace FinityLabs\FinCodex;

use Closure;
use Filament\Contracts\Plugin;
use Filament\Panel;
use Filament\Support\Concerns\EvaluatesClosures;
use Filament\View\PanelsRenderHook;
use FinityLabs\FinCodex\Enums\NavigationGroup;
use Illuminate\Support\HtmlString;
use UnitEnum;

/**
 * Per-panel options for the Codex panel layer.
 *
 * Every option that can differ between two panels lives here as a fluent
 * method, never in config. Later code reads them through filament('fin-codex').
 */
class FinCodexPlugin implements Plugin
{
    use EvaluatesClosures;

    protected string|Closure $helpButtonRenderHook = PanelsRenderHook::TOPBAR_END;

    protected string|Closure|null $shortcut = 'ctrl+/';

    protected int|Closure $drawerWidth = 480;

    protected bool|Closure $globalSearch = false;

    protected string|UnitEnum|Closure|null $navigationGroup = NavigationGroup::Help;

    protected int|Closure|null $navigationSort = null;

    /** @var class-string|null */
    protected ?string $articleResource = null;

    /** @var class-string|null */
    protected ?string $settingsPage = null;

    /** @var class-string|null */
    protected ?string $coveragePage = null;

    protected string $policyNamespace = 'App\\Policies';

    public static function make(): static
    {
        return app(static::class);
    }

    public static function get(): static
    {
        /** @var static */
        return filament(app(static::class)->getId());
    }

    public function getId(): string
    {
        return 'fin-codex';
    }

    /**
     * Hooks are registered on the panel, never app-wide through the facade: Panel::boot() flushes
     * them for the current panel only, so two panels never see each other's output.
     * The hidden marker is Phase 1's stand-in for the drawer mount; it renders from
     * this instance, so each panel carries its own option values.
     */
    public function register(Panel $panel): void
    {
        $panel->renderHook(
            PanelsRenderHook::BODY_END,
            fn (array $scopes = []): HtmlString => new HtmlString(sprintf(
                '<span data-fin-codex-panel="%s" data-fin-codex-shortcut="%s" hidden></span>',
                e($panel->getId()),
                e((string) $this->getShortcut()),
            )),
        );
    }

    /** Panel state (guard, global search provider, topbar) is read here or lazily, never in register(). */
    public function boot(Panel $panel): void {}

    public function helpButtonRenderHook(string|Closure $hook): static
    {
        $this->helpButtonRenderHook = $hook;

        return $this;
    }

    public function getHelpButtonRenderHook(): string
    {
        return $this->evaluate($this->helpButtonRenderHook);
    }

    public function shortcut(string|Closure|null $shortcut): static
    {
        $this->shortcut = $shortcut;

        return $this;
    }

    public function getShortcut(): ?string
    {
        return $this->evaluate($this->shortcut);
    }

    public function drawerWidth(int|Closure $width): static
    {
        $this->drawerWidth = $width;

        return $this;
    }

    public function getDrawerWidth(): int
    {
        return $this->evaluate($this->drawerWidth);
    }

    public function globalSearch(bool|Closure $enabled = true): static
    {
        $this->globalSearch = $enabled;

        return $this;
    }

    public function hasGlobalSearch(): bool
    {
        return $this->evaluate($this->globalSearch);
    }

    public function navigationGroup(string|UnitEnum|Closure|null $group): static
    {
        $this->navigationGroup = $group;

        return $this;
    }

    public function getNavigationGroup(): string|UnitEnum|null
    {
        return $this->evaluate($this->navigationGroup);
    }

    public function navigationSort(int|Closure|null $sort): static
    {
        $this->navigationSort = $sort;

        return $this;
    }

    public function getNavigationSort(): ?int
    {
        return $this->evaluate($this->navigationSort);
    }

    /**
     * Swap in your own article resource (extend the built-in one once it exists).
     *
     * @param  class-string  $resource
     */
    public function articleResource(string $resource): static
    {
        $this->articleResource = $resource;

        return $this;
    }

    /** @return class-string|null */
    public function getArticleResource(): ?string
    {
        return $this->articleResource;
    }

    /** @param  class-string  $page */
    public function settingsPage(string $page): static
    {
        $this->settingsPage = $page;

        return $this;
    }

    /** @return class-string|null */
    public function getSettingsPage(): ?string
    {
        return $this->settingsPage;
    }

    /** @param  class-string  $page */
    public function coveragePage(string $page): static
    {
        $this->coveragePage = $page;

        return $this;
    }

    /** @return class-string|null */
    public function getCoveragePage(): ?string
    {
        return $this->coveragePage;
    }

    public function policyNamespace(string $namespace): static
    {
        $this->policyNamespace = $namespace;

        return $this;
    }

    public function getPolicyNamespace(): string
    {
        return $this->policyNamespace;
    }
}
