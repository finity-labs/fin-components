<?php

declare(strict_types=1);

namespace FinityLabs\FinModalTableSelect\Concerns;

use Closure;
use FinityLabs\FinModalTableSelect\Enums\ListStyle;
use Illuminate\Database\Eloquent\Model;

trait HasBadgeAndListDisplay
{
    protected ListStyle|Closure|null $listStyle = null;

    protected ?Closure $badgeColorFromRecord = null;

    protected ?Closure $badgeIconFromRecord = null;

    /**
     * Render the selection as plain text in the given style (comma, dot,
     * bullet, or line-break separated) instead of badges.
     */
    public function listStyle(ListStyle|Closure|null $style): static
    {
        $this->listStyle = $style;

        // A text list and badges are mutually exclusive.
        if ($style !== null) {
            $this->badge(false);
        }

        return $this;
    }

    public function getListStyle(): ?ListStyle
    {
        return $this->evaluate($this->listStyle);
    }

    /**
     * Color each badge from its record (e.g. by status). When set, badges are
     * rendered from the selected records rather than plain option labels.
     */
    public function badgeColorFromRecord(?Closure $callback): static
    {
        $this->badgeColorFromRecord = $callback;

        return $this;
    }

    /**
     * Add an icon to each badge, resolved from its record.
     */
    public function badgeIconFromRecord(?Closure $callback): static
    {
        $this->badgeIconFromRecord = $callback;

        return $this;
    }

    public function hasRecordBadges(): bool
    {
        return ($this->badgeColorFromRecord !== null) || ($this->badgeIconFromRecord !== null);
    }

    /** @param  Model|array<string, mixed>  $record */
    public function getBadgeColorForRecord(Model|array $record): mixed
    {
        if ($this->badgeColorFromRecord === null) {
            return $this->getBadgeColor();
        }

        return $this->evaluateWithRecord($this->badgeColorFromRecord, $record) ?? $this->getBadgeColor();
    }

    /** @param  Model|array<string, mixed>  $record */
    public function getBadgeIconForRecord(Model|array $record): ?string
    {
        if ($this->badgeIconFromRecord === null) {
            return null;
        }

        $icon = $this->evaluateWithRecord($this->badgeIconFromRecord, $record);

        return filled($icon) ? (string) $icon : null;
    }
}
