<?php

declare(strict_types=1);

namespace FinityLabs\FinModalTableSelect\Concerns;

use Closure;
use Illuminate\Database\Eloquent\Model;

trait HasThumbnailsDisplay
{
    protected string|Closure|null $thumbnailsImage = null;

    protected bool|Closure $isThumbnailsCircular = true;

    protected bool|Closure $isThumbnailsRemovable = false;

    /**
     * Display the selection as a compact strip of image thumbnails. Each
     * thumbnail shows the record label as a tooltip. Pass an attribute path
     * or a Closure receiving the record that resolves the image URL.
     */
    public function thumbnails(string|Closure $imageSource): static
    {
        $this->thumbnailsImage = $imageSource;

        return $this;
    }

    /**
     * Render square (rounded-corner) thumbnails instead of circles.
     */
    public function thumbnailsSquare(bool|Closure $condition = true): static
    {
        $this->isThumbnailsCircular = ! $condition;

        return $this;
    }

    /**
     * Show a small remove button on each thumbnail.
     */
    public function thumbnailsRemovable(bool|Closure $condition = true): static
    {
        $this->isThumbnailsRemovable = $condition;

        return $this;
    }

    public function hasThumbnailsDisplay(): bool
    {
        return $this->thumbnailsImage !== null;
    }

    public function getIsThumbnailsCircular(): bool
    {
        return (bool) $this->evaluate($this->isThumbnailsCircular);
    }

    public function getIsThumbnailsRemovable(): bool
    {
        return (bool) $this->evaluate($this->isThumbnailsRemovable);
    }

    public function getThumbnailImage(Model $record): ?string
    {
        return $this->resolveRecordDisplayValue($record, $this->thumbnailsImage);
    }
}
