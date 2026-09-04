<?php

declare(strict_types=1);

namespace FinityLabs\LinCodex\Livewire;

use FinityLabs\LinCodex\Livewire\Concerns\CapturesPageHelp;
use FinityLabs\LinCodex\Livewire\Concerns\SearchesArticles;
use FinityLabs\LinCodex\View\PageHelpResolver;
use Illuminate\Contracts\View\View;
use Livewire\Component;

/**
 * The full-page help center, registered as "lin-codex.help-center" and
 * mounted by the "{help_center}" and "{help_center}/{slug}" routes.
 *
 * The page renders inside lin-codex.routes.help_center_layout when set,
 * else the package layout. Either must be a component layout, one that
 * receives $slot; an @extends layout does not work here. The layout is
 * always passed explicitly because the Livewire default differs between
 * versions.
 *
 * This skeleton fixes the mount contract and the registration; search, the
 * tree and the article view follow in a later plan.
 */
final class HelpCenter extends Component
{
    use CapturesPageHelp;
    use SearchesArticles;

    public ?string $slug = null;

    public function mount(PageHelpResolver $resolver, ?string $slug = null, ?string $locale = null): void
    {
        $this->capturePageHelp($resolver, null, null, $locale);
        $this->slug = $slug;
    }

    public function render(): View
    {
        $layout = config('lin-codex.routes.help_center_layout');
        $layout = is_string($layout) && $layout !== '' ? $layout : 'lin-codex::layouts.help-center';

        return view('lin-codex::livewire.help-center')->layout($layout, ['title' => (string) config('app.name')]);
    }
}
