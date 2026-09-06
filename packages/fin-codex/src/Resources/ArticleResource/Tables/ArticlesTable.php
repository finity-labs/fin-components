<?php

declare(strict_types=1);

namespace FinityLabs\FinCodex\Resources\ArticleResource\Tables;

use Filament\Actions\EditAction;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;

/**
 * The article list, minimal for now: the slug path is the identity, sorted
 * ascending so a section and its children read together like a table of
 * contents. 05-03 adds the title, source, publishing and language columns
 * and the filters; the frame stays.
 *
 * No delete action and no bulk actions: deleting an article has consequences
 * the admin must see first, so it lives on the edit page behind 05-07's
 * confirmation modal.
 */
final class ArticlesTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('slug')
                    ->label(__('fin-codex::fin-codex.editor.form.slug'))
                    ->sortable()
                    ->searchable(),
            ])
            ->defaultSort('slug')
            ->recordActions([
                EditAction::make(),
            ]);
    }
}
