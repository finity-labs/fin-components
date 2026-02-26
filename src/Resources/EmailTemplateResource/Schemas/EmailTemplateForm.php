<?php

declare(strict_types=1);

namespace FinityLabs\FinMail\Resources\EmailTemplateResource\Schemas;

use Filament\Forms\Components\Repeater;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TagsInput;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Schemas\Components\Grid;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Components\Tabs;
use Filament\Schemas\Components\Tabs\Tab;
use Filament\Schemas\Schema;
use FinityLabs\FinMail\Contracts\EditorContract;
use FinityLabs\FinMail\Enums\TemplateCategory;
use FinityLabs\FinMail\Settings\MailSettings;

class EmailTemplateForm
{
    public static function configure(Schema $form): Schema
    {
        $editor = app(EditorContract::class);

        return $form->schema([

            Tabs::make('Template')
                ->tabs([

                    Tab::make(__('fin-mail::fin-mail.template.tabs.content'))
                        ->icon('heroicon-o-document-text')
                        ->schema([
                            TextInput::make('name')
                                ->required()
                                ->maxLength(255)
                                ->columnSpanFull(),

                            Grid::make(2)->schema([
                                TextInput::make('key')
                                    ->required()
                                    ->unique(ignoreRecord: true)
                                    ->helperText(__('fin-mail::fin-mail.template.fields.key_helper'))
                                    ->maxLength(255),

                                Select::make('category')
                                    ->options(TemplateCategory::class)
                                    ->default(TemplateCategory::Transactional)
                                    ->native(false)
                                    ->required(),
                            ]),

                            TextInput::make('subject')
                                ->required()
                                ->maxLength(255)
                                ->helperText(__('fin-mail::fin-mail.template.fields.subject_helper'))
                                ->columnSpanFull(),

                            TextInput::make('preheader')
                                ->maxLength(255)
                                ->helperText(__('fin-mail::fin-mail.template.fields.preheader_helper'))
                                ->columnSpanFull(),

                            $editor->make('body')
                                ->required(),
                        ]),

                    Tab::make(__('fin-mail::fin-mail.template.tabs.settings'))
                        ->icon('heroicon-o-cog-6-tooth')
                        ->schema([
                            Grid::make(2)->schema([
                                Select::make('email_theme_id')
                                    ->label(__('fin-mail::fin-mail.template.fields.theme'))
                                    ->relationship('theme', 'name')
                                    ->placeholder(__('fin-mail::fin-mail.template.fields.theme_placeholder'))
                                    ->native(false)
                                    ->preload(),

                                Toggle::make('is_active')
                                    ->label(__('fin-mail::fin-mail.template.fields.is_active'))
                                    ->default(true)
                                    ->helperText(__('fin-mail::fin-mail.template.fields.is_active_helper')),
                            ]),

                            TagsInput::make('tags')
                                ->placeholder(__('fin-mail::fin-mail.template.fields.tags_placeholder')),

                            Section::make(__('fin-mail::fin-mail.template.sections.custom_sender'))
                                ->description(__('fin-mail::fin-mail.template.sections.custom_sender_description'))
                                ->schema([
                                    Grid::make(2)->schema([
                                        TextInput::make('from.address')
                                            ->label(__('fin-mail::fin-mail.template.fields.from_address'))
                                            ->email()
                                            ->placeholder(fn (): string => app(MailSettings::class)->default_from_address),

                                        TextInput::make('from.name')
                                            ->label(__('fin-mail::fin-mail.template.fields.from_name'))
                                            ->placeholder(fn (): string => app(MailSettings::class)->default_from_name),
                                    ]),
                                ])
                                ->collapsed(),
                        ]),

                    Tab::make(__('fin-mail::fin-mail.template.tabs.tokens'))
                        ->icon('heroicon-o-code-bracket')
                        ->schema([
                            Repeater::make('token_schema')
                                ->label(__('fin-mail::fin-mail.template.tokens.label'))
                                ->helperText(__('fin-mail::fin-mail.template.tokens.helper'))
                                ->schema([
                                    TextInput::make('token')
                                        ->placeholder(__('fin-mail::fin-mail.template.tokens.token_placeholder'))
                                        ->required(),
                                    TextInput::make('description')
                                        ->placeholder(__('fin-mail::fin-mail.template.tokens.description_placeholder'))
                                        ->required(),
                                    TextInput::make('example')
                                        ->placeholder(__('fin-mail::fin-mail.template.tokens.example_placeholder')),
                                ])
                                ->columns(3)
                                ->defaultItems(0)
                                ->collapsible()
                                ->itemLabel(fn (array $state): ?string => $state['token'] ?? __('fin-mail::fin-mail.template.tokens.new_item')),
                        ]),
                ])
                ->columnSpanFull(),
        ]);
    }
}
