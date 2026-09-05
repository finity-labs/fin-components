<?php

declare(strict_types=1);

namespace FinityLabs\FinCodex\Panel;

use Filament\Panel;
use FinityLabs\FinCodex\FinCodexPlugin;
use Illuminate\Support\HtmlString;

/**
 * The bodies of the render hooks FinCodexPlugin::register() wires. Every body
 * reads the page identity from CurrentPage, never from hook scopes: the topbar
 * hook has none and the button and the drawer must agree. Options come from
 * the plugin instance that registered the hook, panel state (dark mode,
 * topbar) is read lazily at render time. Nothing here decides who may read
 * what; the guard goes in as a prop and lin-codex's ViewerResolver and
 * ArticleGate decide.
 */
final class HelpMount
{
    public function __construct(private readonly CurrentPage $currentPage) {}

    /**
     * The core stylesheet link followed by the accent, font and guest-link
     * rules; fires at HEAD_END on every panel page.
     */
    public function head(Panel $panel): HtmlString
    {
        return $this->render('fin-codex::panel.head', []);
    }

    /**
     * The drawer at BODY_END: skipped when no panel is current and on
     * simple-layout pages when the guest drawer is off.
     */
    public function drawer(FinCodexPlugin $plugin, Panel $panel): HtmlString
    {
        $identity = $this->currentPage->identity();

        if (! $identity->hasPanel() || ($identity->isSimplePage && ! $plugin->hasGuestDrawer())) {
            return new HtmlString('');
        }

        return $this->render('fin-codex::panel.drawer', [
            'pageClass' => $identity->pageClass(),
            'resourceClass' => $identity->resourceClass,
            'panelId' => $identity->panelId,
            'guard' => $identity->guard,
            'shortcut' => $plugin->getShortcut(),
            'width' => $plugin->getDrawerWidth(),
            'hasDarkMode' => $panel->hasDarkMode(),
        ]);
    }

    /** @param  array<string, mixed>  $data */
    private function render(string $view, array $data): HtmlString
    {
        return new HtmlString(view($view, $data)->render());
    }
}
