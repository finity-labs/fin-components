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
                    ->label('Primary'),

                ColorColumn::make('colors.background')
                    ->label('Background'),

                ColorColumn::make('colors.text')
                    ->label('Text'),

                ColorColumn::make('colors.button_bg')
                    ->label('Button'),

                IconColumn::make('is_default')
                    ->boolean()
                    ->label('Default'),

                TextColumn::make('templates_count')
                    ->counts('templates')
                    ->label('Templates')
                    ->sortable(),

                TextColumn::make('updated_at')
                    ->dateTime('M d, Y')
                    ->sortable()
                    ->toggleable(),
            ])
            ->deferFilters()
            ->recordAction(null)
            ->actions([
                ViewAction::make(),
                EditAction::make(),
                ReplicateAction::make()
                    ->beforeReplicaSaved(function ($replica): void {
                        $replica->name = $replica->name.' (Copy)';
                        $replica->is_default = false;
                    }),
                DeleteAction::make()
                    ->before(function ($record): void {
                        $record->templates()->update(['email_theme_id' => null]);
                    }),
            ])
            ->bulkActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make(),
                ]),
            ]);
    }
}
