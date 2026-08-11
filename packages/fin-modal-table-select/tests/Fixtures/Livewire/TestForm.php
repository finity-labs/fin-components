<?php

declare(strict_types=1);

namespace FinityLabs\FinModalTableSelect\Tests\Fixtures\Livewire;

use Closure;
use Filament\Schemas\Concerns\InteractsWithSchemas;
use Filament\Schemas\Contracts\HasSchemas;
use Filament\Schemas\Schema;
use Illuminate\Database\Eloquent\Model;
use Livewire\Component;

class TestForm extends Component implements HasSchemas
{
    use InteractsWithSchemas;

    /** @var array<string, mixed>|null */
    public ?array $data = [];

    public static ?Closure $makeComponents = null;

    public static ?Model $record = null;

    public function mount(): void
    {
        $this->form->fill();
    }

    public function form(Schema $schema): Schema
    {
        return $schema
            ->components((static::$makeComponents)())
            ->record(static::$record)
            ->model(static::$record)
            ->statePath('data');
    }

    public function render(): string
    {
        return <<<'HTML'
        <div>{{ $this->form }}</div>
        HTML;
    }
}
