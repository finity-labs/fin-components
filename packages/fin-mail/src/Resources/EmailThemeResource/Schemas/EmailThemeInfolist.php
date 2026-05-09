<?php

declare(strict_types=1);

namespace FinityLabs\FinMail\Resources\EmailThemeResource\Schemas;

use Filament\Infolists\Components\ColorEntry;
use Filament\Infolists\Components\IconEntry;
use Filament\Infolists\Components\TextEntry;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;

class EmailThemeInfolist
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->schema([
                Section::make(__('fin-mail::fin-mail.theme.sections.details'))
                    ->schema([
                        TextEntry::make('name')
                            ->label(__('fin-mail::fin-mail.theme.fields.name')),

                        IconEntry::make('is_default')
                            ->label(__('fin-mail::fin-mail.theme.fields.is_default'))
                            ->boolean(),
                    ])
                    ->columns(3),

                Section::make(__('fin-mail::fin-mail.theme.sections.background'))
                    ->description(__('fin-mail::fin-mail.theme.sections.background_description'))
                    ->schema([
                        ...self::colorEntry('colors.background', __('fin-mail::fin-mail.theme.fields.page_background')),
                        ...self::colorEntry('colors.content_bg', __('fin-mail::fin-mail.theme.fields.content_background')),
                        ...self::colorEntry('colors.border', __('fin-mail::fin-mail.theme.fields.border')),
                    ])
                    ->columns(3),

                Section::make(__('fin-mail::fin-mail.theme.sections.typography'))
                    ->description(__('fin-mail::fin-mail.theme.sections.typography_description'))
                    ->schema([
                        ...self::colorEntry('colors.heading', __('fin-mail::fin-mail.theme.fields.headings')),
                        ...self::colorEntry('colors.text', __('fin-mail::fin-mail.theme.fields.body_text')),
                        ...self::colorEntry('colors.text_light', __('fin-mail::fin-mail.theme.fields.secondary_text')),
                        ...self::colorEntry('colors.link', __('fin-mail::fin-mail.theme.fields.links')),
                    ])
                    ->columns(4),

                Section::make(__('fin-mail::fin-mail.theme.sections.buttons'))
                    ->description(__('fin-mail::fin-mail.theme.sections.buttons_description'))
                    ->schema([
                        ...self::colorEntry('colors.button_bg', __('fin-mail::fin-mail.theme.fields.button_background')),
                        ...self::colorEntry('colors.button_text', __('fin-mail::fin-mail.theme.fields.button_text')),
                        ...self::colorEntry('colors.primary', __('fin-mail::fin-mail.theme.fields.primary_accent')),
                    ])
                    ->columns(3),

                Section::make(__('fin-mail::fin-mail.theme.sections.footer'))
                    ->description(__('fin-mail::fin-mail.theme.sections.footer_description'))
                    ->schema([
                        ...self::colorEntry('colors.footer_bg', __('fin-mail::fin-mail.theme.fields.footer_background')),
                        ...self::colorEntry('colors.footer_text', __('fin-mail::fin-mail.theme.fields.footer_text')),
                    ])
                    ->columns(2),

                Section::make(__('fin-mail::fin-mail.theme.sections.preview'))
                    ->schema([
                        TextEntry::make('preview')
                            ->label('')
                            ->state(fn ($record) => view('fin-mail::components.theme-preview', [
                                'theme' => $record->resolvedColors(),
                            ]))
                            ->columnSpanFull(),
                    ])
                    ->collapsible(),
            ]);
    }

    /**
     * @return array<int, ColorEntry|TextEntry>
     */
    private static function colorEntry(string $name, string $label): array
    {
        return [
            ColorEntry::make($name)
                ->label($label)
                ->visible(fn ($state) => strtolower($state ?? '') !== '#ffffff'),
            TextEntry::make($name)
                ->label($label)
                ->visible(fn ($state) => strtolower($state ?? '') === '#ffffff'),
        ];
    }
}
