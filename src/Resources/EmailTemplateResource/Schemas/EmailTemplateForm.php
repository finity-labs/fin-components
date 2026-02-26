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

                    Tab::make('Content')
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
                                    ->helperText('Unique key used in code: e.g., "invoice-sent"')
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
                                ->helperText('Supports tokens: {{ user.name }}, {{ config.app.name }}')
                                ->columnSpanFull(),

                            TextInput::make('preheader')
                                ->maxLength(255)
                                ->helperText('Preview text shown in email clients')
                                ->columnSpanFull(),

                            $editor->make('body')
                                ->required(),
                        ]),

                    Tab::make('Settings')
                        ->icon('heroicon-o-cog-6-tooth')
                        ->schema([
                            Grid::make(2)->schema([
                                Select::make('email_theme_id')
                                    ->label('Theme')
                                    ->relationship('theme', 'name')
                                    ->placeholder('Default theme')
                                    ->native(false)
                                    ->preload(),

                                Toggle::make('is_active')
                                    ->label('Active')
                                    ->default(true)
                                    ->helperText('Inactive templates cannot be used for sending'),
                            ]),

                            TagsInput::make('tags')
                                ->placeholder('Add tags for organization'),

                            Section::make('Custom Sender')
                                ->description('Override the default from address for this template')
                                ->schema([
                                    Grid::make(2)->schema([
                                        TextInput::make('from.address')
                                            ->label('From Email')
                                            ->email()
                                            ->placeholder(fn (): string => app(MailSettings::class)->default_from_address),

                                        TextInput::make('from.name')
                                            ->label('From Name')
                                            ->placeholder(fn (): string => app(MailSettings::class)->default_from_name),
                                    ]),
                                ])
                                ->collapsed(),
                        ]),

                    Tab::make('Tokens')
                        ->icon('heroicon-o-code-bracket')
                        ->schema([
                            Repeater::make('token_schema')
                                ->label('Available Tokens')
                                ->helperText('Document the tokens available for this template. This helps editors know what variables they can use.')
                                ->schema([
                                    TextInput::make('token')
                                        ->placeholder('user.name')
                                        ->required(),
                                    TextInput::make('description')
                                        ->placeholder('The full name of the recipient')
                                        ->required(),
                                    TextInput::make('example')
                                        ->placeholder('John Doe'),
                                ])
                                ->columns(3)
                                ->defaultItems(0)
                                ->collapsible()
                                ->itemLabel(fn (array $state): ?string => $state['token'] ?? 'New Token'),
                        ]),
                ])
                ->columnSpanFull(),
        ]);
    }
}
