<?php

declare(strict_types=1);

namespace FinityLabs\FinMail\Resources\EmailThemeResource\Schemas;

use Filament\Forms\Components\ColorPicker;
use Filament\Forms\Components\Placeholder;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use FinityLabs\FinMail\Models\EmailTheme;

class EmailThemeForm
{
    public static function configure(Schema $form): Schema
    {
        $defaultColors = EmailTheme::defaultColors();

        return $form->schema([
            Section::make('Theme Details')
                ->schema([
                    TextInput::make('name')
                        ->required()
                        ->maxLength(255)
                        ->columnSpan(2),

                    Toggle::make('is_default')
                        ->label('Default Theme')
                        ->helperText('The default theme is applied to templates that don\'t specify one.')
                        ->afterStateUpdated(function (bool $state, $record): void {
                            if ($state && $record) {
                                EmailTheme::where('id', '!=', $record->id)
                                    ->where('is_default', true)
                                    ->update(['is_default' => false]);
                            }
                        })
                        ->live(),
                ])
                ->columns(3),

            Section::make('Background & Layout')
                ->description('Main structural colors for the email layout.')
                ->schema([
                    ColorPicker::make('colors.background')
                        ->label('Page Background')
                        ->default($defaultColors['background']),

                    ColorPicker::make('colors.content_bg')
                        ->label('Content Background')
                        ->default($defaultColors['content_bg']),

                    ColorPicker::make('colors.border')
                        ->label('Border')
                        ->default($defaultColors['border']),
                ])
                ->columns(3),

            Section::make('Typography')
                ->description('Colors for text and headings.')
                ->schema([
                    ColorPicker::make('colors.heading')
                        ->label('Headings')
                        ->default($defaultColors['heading']),

                    ColorPicker::make('colors.text')
                        ->label('Body Text')
                        ->default($defaultColors['text']),

                    ColorPicker::make('colors.text_light')
                        ->label('Secondary Text')
                        ->default($defaultColors['text_light']),

                    ColorPicker::make('colors.link')
                        ->label('Links')
                        ->default($defaultColors['link']),
                ])
                ->columns(4),

            Section::make('Buttons')
                ->description('Call-to-action button styling.')
                ->schema([
                    ColorPicker::make('colors.button_bg')
                        ->label('Button Background')
                        ->default($defaultColors['button_bg']),

                    ColorPicker::make('colors.button_text')
                        ->label('Button Text')
                        ->default($defaultColors['button_text']),

                    ColorPicker::make('colors.primary')
                        ->label('Primary/Accent')
                        ->default($defaultColors['primary']),
                ])
                ->columns(3),

            Section::make('Footer')
                ->description('Footer area styling.')
                ->schema([
                    ColorPicker::make('colors.footer_bg')
                        ->label('Footer Background')
                        ->default($defaultColors['footer_bg']),

                    ColorPicker::make('colors.footer_text')
                        ->label('Footer Text')
                        ->default($defaultColors['footer_text']),
                ])
                ->columns(2),

            Section::make('Preview')
                ->schema([
                    Placeholder::make('preview')
                        ->label('')
                        ->content(fn ($record) => view('fin-mail::components.theme-preview', [
                            'theme' => $record?->resolvedColors() ?? $defaultColors,
                        ]))
                        ->columnSpanFull(),
                ])
                ->collapsible(),
        ]);
    }
}
