<?php

declare(strict_types=1);

namespace FinityLabs\FinMail\Resources\EmailThemeResource\Tables;

use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteAction;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Actions\ReplicateAction;
use Filament\Actions\ViewAction;
use Filament\Tables\Columns\ColorColumn;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;

class EmailThemesTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('name')
                    ->searchable()
                    ->sortable(),

                ColorColumn::make('colors.primary')
                    ->label(__('fin-mail::fin-mail.theme.columns.primary')),

                ColorColumn::make('colors.background')
                    ->label(__('fin-mail::fin-mail.theme.columns.background')),

                ColorColumn::make('colors.text')
                    ->label(__('fin-mail::fin-mail.theme.columns.text')),

                ColorColumn::make('colors.button_bg')
                    ->label(__('fin-mail::fin-mail.theme.columns.button')),

                IconColumn::make('is_default')
                    ->boolean()
                    ->label(__('fin-mail::fin-mail.theme.columns.default')),

                TextColumn::make('templates_count')
                    ->counts('templates')
                    ->label(__('fin-mail::fin-mail.theme.columns.templates'))
                    ->sortable(),

                TextColumn::make('updated_at')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(),
            ])
            ->deferFilters()
            ->recordAction(null)
            ->recordActions([
                ViewAction::make(),
                EditAction::make(),
                ReplicateAction::make()
                    ->beforeReplicaSaved(function ($replica): void {
                        $replica->name = $replica->name.' '.__('fin-mail::fin-mail.theme.replicate_suffix');
                        $replica->is_default = false;
                    }),
                DeleteAction::make()
                    ->before(function ($record): void {
                        $record->templates()->update(['email_theme_id' => null]);
                    }),
            ])
            ->toolbarActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make(),
                ]),
            ]);
    }
}
