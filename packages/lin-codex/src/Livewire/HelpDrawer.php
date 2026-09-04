<?php

declare(strict_types=1);

namespace FinityLabs\LinCodex\Livewire;

use FinityLabs\LinCodex\Livewire\Concerns\CapturesPageHelp;
use FinityLabs\LinCodex\Livewire\Concerns\SearchesArticles;
use FinityLabs\LinCodex\View\PageHelpResolver;
use Illuminate\Contracts\View\View;
use Livewire\Component;

/**
 * The in-app help drawer, registered as "lin-codex.help-drawer" and
 * rendered by <livewire:lin-codex.help-drawer /> or the
 * <x-lin-codex::help-drawer> wrapper.
 *
 * This skeleton fixes the mount contract (the page class, panel id and
 * locale a host passes, the slug a wrapper opens on) and the registration.
 * The drawer's state machine (open, the page, search, tree and article
 * views, the history and the current slug) and its actions follow in a
 * later plan.
 */
final class HelpDrawer extends Component
{
    use CapturesPageHelp;
    use SearchesArticles;

    public function mount(PageHelpResolver $resolver, ?string $slug = null, ?string $pageClass = null, ?string $panelId = null, ?string $locale = null): void
    {
        $this->capturePageHelp($resolver, $pageClass, $panelId, $locale);
    }

    public function render(): View
    {
        return view('lin-codex::livewire.help-drawer', [
            'width' => (int) config('lin-codex.ui.drawer_width', 480),
        ]);
    }
}
