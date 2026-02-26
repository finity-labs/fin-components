<?php

declare(strict_types=1);

namespace FinityLabs\FinMail\Editors;

use Filament\Schemas\Components\Component;
use FinityLabs\FinMail\Contracts\EditorContract;

class TiptapEditor implements EditorContract
{
    public function make(string $fieldName): Component
    {
        if (! class_exists(\FilamentTiptapEditor\TiptapEditor::class)) {
            throw new \RuntimeException(
                'To use the Tiptap editor, install it first: composer require awcodes/filament-tiptap-editor'
            );
        }

        return \FilamentTiptapEditor\TiptapEditor::make($fieldName)
            ->profile('email')
            ->columnSpanFull()
            ->extraInputAttributes(['style' => 'min-height: 400px;']);
    }

    public function name(): string
    {
        return 'tiptap';
    }
}
