<?php

declare(strict_types=1);

namespace FinityLabs\FinModalTableSelect\Concerns;

use Closure;

trait HasItemViewDisplay
{
    protected string|Closure|null $itemView = null;

    /** @var array<string, mixed>|Closure */
    protected array|Closure $itemViewData = [];

    /**
     * The escape hatch: render each selected record with your own Blade view.
     * The view receives $record, $field, and $removeAction (a pre-bound
     * per-item remove action you can echo, or ignore).
     *
     * @param  array<string, mixed>|Closure  $viewData
     */
    public function itemView(string|Closure|null $view, array|Closure $viewData = []): static
    {
        $this->itemView = $view;
        $this->itemViewData = $viewData;

        return $this;
    }

    public function hasItemViewDisplay(): bool
    {
        return $this->itemView !== null;
    }

    public function getItemView(): ?string
    {
        return $this->evaluate($this->itemView);
    }

    /** @return array<string, mixed> */
    public function getItemViewData(): array
    {
        return $this->evaluate($this->itemViewData) ?? [];
    }
}
