<?php

declare(strict_types=1);

namespace FinityLabs\FinMail\Contracts;

use Filament\Schemas\Components\Component;

/**
 * Contract for swappable WYSIWYG editors.
 *
 * Implement this to use your own editor (TinyMCE, Tiptap, etc.)
 */
interface EditorContract
{
    /**
     * Return the Filament form component for the editor.
     */
    public function make(string $fieldName): Component;

    /**
     * Get the editor identifier.
     */
    public function name(): string;
}
