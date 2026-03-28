<?php

declare(strict_types=1);

namespace FinityLabs\FinComponents\Components\ModalTableSelect\Concerns;

use Closure;
use Filament\Forms\Components\Component as FormComponent;

trait HasFormDisplay
{
    /** @var array<FormComponent>|Closure|null */
    protected array | Closure | null $formSchema = null;

    /** @var int|Closure */
    protected int | Closure $formColumns = 1;

    /** @var array<string>|Closure|null */
    protected array | Closure | null $formEagerLoad = null;

    /**
     * Define the form schema for displaying a single selected record.
     * All fields are rendered as disabled (read-only) by default.
     *
     * @param  array<FormComponent>|Closure  $schema
     */
    public function formSchema(array | Closure $schema): static
    {
        $this->formSchema = $schema;

        return $this;
    }

    public function formColumns(int | Closure $columns): static
    {
        $this->formColumns = $columns;

        return $this;
    }

    /**
     * @param  array<string>|Closure  $relationships
     */
    public function formEagerLoad(array | Closure $relationships): static
    {
        $this->formEagerLoad = $relationships;

        return $this;
    }

    /** @return array<FormComponent>|null */
    public function getFormSchema(): ?array
    {
        return $this->evaluate($this->formSchema);
    }

    public function getFormColumns(): int
    {
        return (int) $this->evaluate($this->formColumns);
    }

    public function hasFormSchema(): bool
    {
        return $this->formSchema !== null;
    }
}
