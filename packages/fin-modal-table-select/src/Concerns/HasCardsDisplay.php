<?php

declare(strict_types=1);

namespace FinityLabs\FinModalTableSelect\Concerns;

use Closure;
use Illuminate\Database\Eloquent\Model;

trait HasCardsDisplay
{
    protected bool|Closure $isCardGrid = false;

    protected string|Closure|null $cardTitle = null;

    protected string|Closure|null $cardDescription = null;

    protected string|Closure|null $cardImage = null;

    protected int|Closure $cardColumns = 3;

    protected bool|Closure $isCardsRemovable = true;

    /**
     * Display the selected records as a grid of cards: optional image on top,
     * a title, an optional description, and a remove button. Suited to visual
     * records (products, properties, media).
     */
    public function cardGrid(bool|Closure $condition = true): static
    {
        $this->isCardGrid = $condition;

        return $this;
    }

    /**
     * The card title: an attribute path or a Closure receiving the record.
     * Defaults to the option label.
     */
    public function cardTitle(string|Closure|null $source): static
    {
        $this->cardTitle = $source;

        return $this;
    }

    public function cardDescription(string|Closure|null $source): static
    {
        $this->cardDescription = $source;

        return $this;
    }

    /**
     * Image URL rendered at the top of each card.
     */
    public function cardImage(string|Closure|null $source): static
    {
        $this->cardImage = $source;

        return $this;
    }

    public function cardColumns(int|Closure $columns): static
    {
        $this->cardColumns = $columns;

        return $this;
    }

    public function cardsRemovable(bool|Closure $condition = true): static
    {
        $this->isCardsRemovable = $condition;

        return $this;
    }

    public function hasCardGridDisplay(): bool
    {
        return (bool) $this->evaluate($this->isCardGrid);
    }

    public function getCardColumns(): int
    {
        return (int) $this->evaluate($this->cardColumns);
    }

    public function getIsCardsRemovable(): bool
    {
        return (bool) $this->evaluate($this->isCardsRemovable);
    }

    public function getCardTitle(Model $record): string
    {
        return $this->resolveRecordDisplayValue($record, $this->cardTitle)
            ?? $this->getRecordDisplayLabel($record);
    }

    public function getCardDescription(Model $record): ?string
    {
        return $this->resolveRecordDisplayValue($record, $this->cardDescription);
    }

    public function getCardImage(Model $record): ?string
    {
        return $this->resolveRecordDisplayValue($record, $this->cardImage);
    }
}
