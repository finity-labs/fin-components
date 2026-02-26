<?php

declare(strict_types=1);

namespace FinityLabs\FinMail\Resources\EmailThemeResource\Schemas;

use Filament\Forms\Components\ColorPicker;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Infolists\Components\TextEntry;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use FinityLabs\FinMail\Models\EmailTheme;

class EmailThemeForm
{
    public static function configure(Schema $form): Schema
    {
        $defaultColors = EmailTheme::defaultColors();

        return $form->schema([
            Section::make(__('fin-mail::fin-mail.theme.sections.details'))
                ->schema([
                    TextInput::make('name')
                        ->required()
                        ->maxLength(255)
                        ->columnSpan(2),

                    Toggle::make('is_default')
                        ->label(__('fin-mail::fin-mail.theme.fields.is_default'))
                        ->helperText(__('fin-mail::fin-mail.theme.fields.is_default_helper'))
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

            Section::make(__('fin-mail::fin-mail.theme.sections.background'))
                ->description(__('fin-mail::fin-mail.theme.sections.background_description'))
                ->schema([
                    ColorPicker::make('colors.background')
                        ->label(__('fin-mail::fin-mail.theme.fields.page_background'))
                        ->default($defaultColors['background']),

                    ColorPicker::make('colors.content_bg')
                        ->label(__('fin-mail::fin-mail.theme.fields.content_background'))
                        ->default($defaultColors['content_bg']),

                    ColorPicker::make('colors.border')
                        ->label(__('fin-mail::fin-mail.theme.fields.border'))
                        ->default($defaultColors['border']),
                ])
                ->columns(3),

            Section::make(__('fin-mail::fin-mail.theme.sections.typography'))
                ->description(__('fin-mail::fin-mail.theme.sections.typography_description'))
                ->schema([
                    ColorPicker::make('colors.heading')
                        ->label(__('fin-mail::fin-mail.theme.fields.headings'))
                        ->default($defaultColors['heading']),

                    ColorPicker::make('colors.text')
                        ->label(__('fin-mail::fin-mail.theme.fields.body_text'))
                        ->default($defaultColors['text']),

                    ColorPicker::make('colors.text_light')
                        ->label(__('fin-mail::fin-mail.theme.fields.secondary_text'))
                        ->default($defaultColors['text_light']),

                    ColorPicker::make('colors.link')
                        ->label(__('fin-mail::fin-mail.theme.fields.links'))
                        ->default($defaultColors['link']),
                ])
                ->columns(4),

            Section::make(__('fin-mail::fin-mail.theme.sections.buttons'))
                ->description(__('fin-mail::fin-mail.theme.sections.buttons_description'))
                ->schema([
                    ColorPicker::make('colors.button_bg')
                        ->label(__('fin-mail::fin-mail.theme.fields.button_background'))
                        ->default($defaultColors['button_bg']),

                    ColorPicker::make('colors.button_text')
                        ->label(__('fin-mail::fin-mail.theme.fields.button_text'))
                        ->default($defaultColors['button_text']),

                    ColorPicker::make('colors.primary')
                        ->label(__('fin-mail::fin-mail.theme.fields.primary_accent'))
                        ->default($defaultColors['primary']),
                ])
                ->columns(3),

            Section::make(__('fin-mail::fin-mail.theme.sections.footer'))
                ->description(__('fin-mail::fin-mail.theme.sections.footer_description'))
                ->schema([
                    ColorPicker::make('colors.footer_bg')
                        ->label(__('fin-mail::fin-mail.theme.fields.footer_background'))
                        ->default($defaultColors['footer_bg']),

                    ColorPicker::make('colors.footer_text')
                        ->label(__('fin-mail::fin-mail.theme.fields.footer_text'))
                        ->default($defaultColors['footer_text']),
                ])
                ->columns(2),

            Section::make(__('fin-mail::fin-mail.theme.sections.preview'))
                ->schema([
                    TextEntry::make('preview')
                        ->label('')
                        ->state(fn ($record) => view('fin-mail::components.theme-preview', [
                            'theme' => $record?->resolvedColors() ?? $defaultColors,
                        ]))
                        ->columnSpanFull(),
                ])
                ->collapsible(),
        ]);
    }
}
