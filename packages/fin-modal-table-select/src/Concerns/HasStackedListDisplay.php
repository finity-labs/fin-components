<?php

declare(strict_types=1);

namespace FinityLabs\FinModalTableSelect\Concerns;

use Closure;
use Illuminate\Contracts\Support\Htmlable;
use Illuminate\Database\Eloquent\Model;

trait HasStackedListDisplay
{
    protected bool|Closure $isStackedList = false;

    protected string|Closure|null $stackedListPrimary = null;

    protected string|Closure|null $stackedListSecondary = null;

    protected string|Closure|null $stackedListImage = null;

    protected bool|Closure $isStackedListRemovable = true;

    protected bool|Closure $isStackedListPrimaryWrapped = false;

    protected bool|Closure $isStackedListSecondaryWrapped = false;

    /**
     * Display the selected records as a stacked list: one row per record with
     * a primary line, an optional secondary line and image, and a per-item
     * remove button.
     */
    public function stackedList(bool|Closure $condition = true): static
    {
        $this->isStackedList = $condition;

        return $this;
    }

    /**
     * The primary line of each row: an attribute path on the record or a
     * Closure receiving the record. Defaults to the option label (the
     * relationship title attribute, standalone title attribute, or record key).
     */
    public function stackedListPrimary(string|Closure|null $source): static
    {
        $this->stackedListPrimary = $source;

        return $this;
    }

    /**
     * Optional secondary line under the primary text.
     */
    public function stackedListSecondary(string|Closure|null $source): static
    {
        $this->stackedListSecondary = $source;

        return $this;
    }

    /**
     * Optional image URL rendered as a small round thumbnail before the text.
     */
    public function stackedListImage(string|Closure|null $source): static
    {
        $this->stackedListImage = $source;

        return $this;
    }

    /**
     * Toggle the per-item remove button (enabled by default).
     */
    public function stackedListRemovable(bool|Closure $condition = true): static
    {
        $this->isStackedListRemovable = $condition;

        return $this;
    }

    /**
     * Let the primary line wrap instead of truncating on narrow widths.
     * Newlines in the value become line breaks (white-space: pre-line).
     */
    public function stackedListPrimaryWrapped(bool|Closure $condition = true): static
    {
        $this->isStackedListPrimaryWrapped = $condition;

        return $this;
    }

    /**
     * Let the secondary line wrap instead of truncating on narrow widths.
     * Newlines in the value become line breaks (white-space: pre-line), so a
     * closure can return "street\ncity" without any HTML.
     */
    public function stackedListSecondaryWrapped(bool|Closure $condition = true): static
    {
        $this->isStackedListSecondaryWrapped = $condition;

        return $this;
    }

    public function hasStackedListDisplay(): bool
    {
        return (bool) $this->evaluate($this->isStackedList);
    }

    public function getIsStackedListRemovable(): bool
    {
        return (bool) $this->evaluate($this->isStackedListRemovable);
    }

    public function getIsStackedListPrimaryWrapped(): bool
    {
        return (bool) $this->evaluate($this->isStackedListPrimaryWrapped);
    }

    public function getIsStackedListSecondaryWrapped(): bool
    {
        return (bool) $this->evaluate($this->isStackedListSecondaryWrapped);
    }

    public function getStackedListPrimary(Model|array $record): string
    {
        return $this->resolveRecordDisplayValue($record, $this->stackedListPrimary)
            ?? $this->getRecordDisplayLabel($record);
    }

    /**
     * The secondary line keeps Htmlable values as-is, so a closure can return
     * an HtmlString and have it render as HTML — Blade's {{ }} outputs
     * Htmlable unescaped by design, and returning one is the opt-in.
     */
    public function getStackedListSecondary(Model|array $record): string|Htmlable|null
    {
        return $this->resolveRecordDisplayHtml($record, $this->stackedListSecondary);
    }

    public function getStackedListImage(Model|array $record): ?string
    {
        return $this->resolveRecordDisplayValue($record, $this->stackedListImage);
    }
}
