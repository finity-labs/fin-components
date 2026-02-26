<?php

declare(strict_types=1);

namespace FinityLabs\FinMail\Pages;

use BackedEnum;
use Filament\Forms\Components\ColorPicker;
use Filament\Forms\Components\Repeater;
use Filament\Forms\Components\TagsInput;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Pages\SettingsPage;
use Filament\Schemas\Components\Grid;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Components\Tabs;
use Filament\Schemas\Components\Tabs\Tab;
use Filament\Schemas\Schema;
use FinityLabs\FinMail\Enums\NavigationGroup;
use FinityLabs\FinMail\Settings\AttachmentSettings;
use FinityLabs\FinMail\Settings\BrandingSettings;
use FinityLabs\FinMail\Settings\LoggingSettings;
use FinityLabs\FinMail\Settings\MailSettings;
use UnitEnum;

class ManageFinMailSettings extends SettingsPage
{
    protected static string $settings = MailSettings::class;

    protected static string|UnitEnum|null $navigationGroup = NavigationGroup::Email;

    protected static string|BackedEnum|null $navigationIcon = 'heroicon-o-cog-6-tooth';

    protected static ?int $navigationSort = 10;

    public static function getNavigationLabel(): string
    {
        return __('fin-mail::fin-mail.navigation.settings');
    }

    public function getTitle(): string
    {
        return __('fin-mail::fin-mail.settings.title');
    }

    public ?array $brandingData = [];

    public ?array $loggingData = [];

    public ?array $attachmentData = [];

    public function mount(): void
    {
        parent::mount();

        $branding = app(BrandingSettings::class);
        $this->brandingData = [
            'logo' => $branding->logo,
            'logo_width' => $branding->logo_width,
            'logo_height' => $branding->logo_height,
            'content_width' => $branding->content_width,
            'primary_color' => $branding->primary_color,
            'footer_links' => $branding->footer_links,
            'customer_service_email' => $branding->customer_service_email,
            'customer_service_phone' => $branding->customer_service_phone,
        ];

        $logging = app(LoggingSettings::class);
        $this->loggingData = [
            'enabled' => $logging->enabled,
            'store_rendered_body' => $logging->store_rendered_body,
            'retention_days' => $logging->retention_days,
        ];

        $attachment = app(AttachmentSettings::class);
        $this->attachmentData = [
            'max_size_mb' => $attachment->max_size_mb,
            'allowed_types' => $attachment->allowed_types,
        ];
    }

    public function form(Schema $form): Schema
    {
        return $form->schema([
            Tabs::make('Settings')
                ->tabs([
                    $this->mailTab(),
                    $this->brandingTab(),
                    $this->loggingTab(),
                    $this->attachmentTab(),
                ])
                ->columnSpanFull(),
        ]);
    }

    protected function mailTab(): Tab
    {
        return Tab::make(__('fin-mail::fin-mail.settings.tabs.general'))
            ->icon('heroicon-o-envelope')
            ->schema([
                Section::make(__('fin-mail::fin-mail.settings.sections.default_sender'))
                    ->description(__('fin-mail::fin-mail.settings.sections.default_sender_description'))
                    ->schema([
                        TextInput::make('default_from_address')
                            ->label(__('fin-mail::fin-mail.settings.fields.from_email'))
                            ->email()
                            ->required()
                            ->maxLength(255),

                        TextInput::make('default_from_name')
                            ->label(__('fin-mail::fin-mail.settings.fields.from_name'))
                            ->required()
                            ->maxLength(255),
                    ])
                    ->columns(2),

                Section::make(__('fin-mail::fin-mail.settings.sections.additional_senders'))
                    ->description(__('fin-mail::fin-mail.settings.sections.additional_senders_description'))
                    ->schema([
                        Repeater::make('additional_senders')
                            ->label('')
                            ->schema([
                                TextInput::make('address')
                                    ->label(__('fin-mail::fin-mail.settings.fields.sender_email'))
                                    ->email()
                                    ->required()
                                    ->maxLength(255),
                                TextInput::make('name')
                                    ->label(__('fin-mail::fin-mail.settings.fields.sender_name'))
                                    ->required()
                                    ->maxLength(255),
                            ])
                            ->columns(2)
                            ->defaultItems(0)
                            ->collapsible()
                            ->itemLabel(fn (array $state): ?string => $state['name'] ?? $state['address'] ?? __('fin-mail::fin-mail.settings.fields.sender_new')),
                    ]),

                Section::make(__('fin-mail::fin-mail.settings.sections.localization'))
                    ->schema([
                        TextInput::make('default_locale')
                            ->label(__('fin-mail::fin-mail.settings.fields.default_locale'))
                            ->required()
                            ->maxLength(10)
                            ->helperText(__('fin-mail::fin-mail.settings.fields.default_locale_helper')),

                        Repeater::make('languages')
                            ->label(__('fin-mail::fin-mail.settings.fields.languages'))
                            ->schema([
                                TextInput::make('code')
                                    ->label(__('fin-mail::fin-mail.settings.fields.language_code'))
                                    ->required()
                                    ->maxLength(10)
                                    ->placeholder('en'),
                                TextInput::make('display')
                                    ->label(__('fin-mail::fin-mail.settings.fields.language_display'))
                                    ->required()
                                    ->maxLength(50)
                                    ->placeholder('English'),
                                TextInput::make('flag-icon')
                                    ->label(__('fin-mail::fin-mail.settings.fields.language_flag'))
                                    ->maxLength(10)
                                    ->placeholder('gb'),
                            ])
                            ->columns(3)
                            ->defaultItems(1)
                            ->collapsible()
                            ->itemLabel(fn (array $state): ?string => $state['display'] ?? $state['code'] ?? __('fin-mail::fin-mail.settings.fields.language_new')),
                    ]),

                Section::make(__('fin-mail::fin-mail.settings.sections.categories'))
                    ->schema([
                        Repeater::make('categories')
                            ->label('')
                            ->schema([
                                TextInput::make('key')
                                    ->label(__('fin-mail::fin-mail.settings.fields.category_key'))
                                    ->required()
                                    ->maxLength(50)
                                    ->placeholder('transactional'),
                                TextInput::make('label')
                                    ->label(__('fin-mail::fin-mail.settings.fields.category_label'))
                                    ->required()
                                    ->maxLength(100)
                                    ->placeholder('Transactional'),
                            ])
                            ->columns(2)
                            ->defaultItems(0)
                            ->collapsible()
                            ->itemLabel(fn (array $state): ?string => $state['label'] ?? $state['key'] ?? __('fin-mail::fin-mail.settings.fields.category_new')),
                    ]),
            ]);
    }

    protected function brandingTab(): Tab
    {
        return Tab::make(__('fin-mail::fin-mail.settings.tabs.branding'))
            ->icon('heroicon-o-paint-brush')
            ->statePath('brandingData')
            ->schema([
                Section::make(__('fin-mail::fin-mail.settings.sections.logo'))
                    ->schema([
                        TextInput::make('logo')
                            ->label(__('fin-mail::fin-mail.settings.fields.logo_url'))
                            ->maxLength(500)
                            ->placeholder(__('fin-mail::fin-mail.settings.fields.logo_url_placeholder'))
                            ->helperText(__('fin-mail::fin-mail.settings.fields.logo_url_helper')),

                        Grid::make(3)->schema([
                            TextInput::make('logo_width')
                                ->label(__('fin-mail::fin-mail.settings.fields.logo_width'))
                                ->numeric()
                                ->required()
                                ->minValue(10)
                                ->maxValue(800),

                            TextInput::make('logo_height')
                                ->label(__('fin-mail::fin-mail.settings.fields.logo_height'))
                                ->numeric()
                                ->required()
                                ->minValue(10)
                                ->maxValue(400),

                            TextInput::make('content_width')
                                ->label(__('fin-mail::fin-mail.settings.fields.content_width'))
                                ->numeric()
                                ->required()
                                ->minValue(300)
                                ->maxValue(900),
                        ]),
                    ]),

                Section::make(__('fin-mail::fin-mail.settings.sections.colors'))
                    ->schema([
                        ColorPicker::make('primary_color')
                            ->label(__('fin-mail::fin-mail.settings.fields.primary_color')),
                    ]),

                Section::make(__('fin-mail::fin-mail.settings.sections.footer_links'))
                    ->schema([
                        Repeater::make('footer_links')
                            ->label('')
                            ->schema([
                                TextInput::make('name')
                                    ->label(__('fin-mail::fin-mail.settings.fields.footer_link_label'))
                                    ->required()
                                    ->maxLength(100),
                                TextInput::make('url')
                                    ->label(__('fin-mail::fin-mail.settings.fields.footer_link_url'))
                                    ->url()
                                    ->required()
                                    ->maxLength(500),
                            ])
                            ->columns(2)
                            ->defaultItems(0)
                            ->collapsible()
                            ->itemLabel(fn (array $state): ?string => $state['name'] ?? __('fin-mail::fin-mail.settings.fields.footer_link_new')),
                    ]),

                Section::make(__('fin-mail::fin-mail.settings.sections.customer_service'))
                    ->schema([
                        TextInput::make('customer_service_email')
                            ->label(__('fin-mail::fin-mail.settings.fields.support_email'))
                            ->email()
                            ->maxLength(255),

                        TextInput::make('customer_service_phone')
                            ->label(__('fin-mail::fin-mail.settings.fields.support_phone'))
                            ->tel()
                            ->maxLength(50),
                    ])
                    ->columns(2),
            ]);
    }

    protected function loggingTab(): Tab
    {
        return Tab::make(__('fin-mail::fin-mail.settings.tabs.logging'))
            ->icon('heroicon-o-document-magnifying-glass')
            ->statePath('loggingData')
            ->schema([
                Section::make(__('fin-mail::fin-mail.settings.sections.logging'))
                    ->description(__('fin-mail::fin-mail.settings.sections.logging_description'))
                    ->schema([
                        Toggle::make('enabled')
                            ->label(__('fin-mail::fin-mail.settings.fields.enable_logging'))
                            ->helperText(__('fin-mail::fin-mail.settings.fields.enable_logging_helper')),

                        Toggle::make('store_rendered_body')
                            ->label(__('fin-mail::fin-mail.settings.fields.store_rendered_body'))
                            ->helperText(__('fin-mail::fin-mail.settings.fields.store_rendered_body_helper')),

                        TextInput::make('retention_days')
                            ->label(__('fin-mail::fin-mail.settings.fields.retention_days'))
                            ->numeric()
                            ->nullable()
                            ->minValue(1)
                            ->maxValue(3650)
                            ->helperText(__('fin-mail::fin-mail.settings.fields.retention_days_helper')),
                    ]),
            ]);
    }

    protected function attachmentTab(): Tab
    {
        return Tab::make(__('fin-mail::fin-mail.settings.tabs.attachments'))
            ->icon('heroicon-o-paper-clip')
            ->statePath('attachmentData')
            ->schema([
                Section::make(__('fin-mail::fin-mail.settings.sections.attachment_rules'))
                    ->description(__('fin-mail::fin-mail.settings.sections.attachment_rules_description'))
                    ->schema([
                        TextInput::make('max_size_mb')
                            ->label(__('fin-mail::fin-mail.settings.fields.max_file_size'))
                            ->numeric()
                            ->required()
                            ->minValue(1)
                            ->maxValue(100),

                        TagsInput::make('allowed_types')
                            ->label(__('fin-mail::fin-mail.settings.fields.allowed_extensions'))
                            ->placeholder(__('fin-mail::fin-mail.settings.fields.allowed_extensions_placeholder'))
                            ->helperText(__('fin-mail::fin-mail.settings.fields.allowed_extensions_helper')),
                    ]),
            ]);
    }

    public function save(): void
    {
        $mailData = $this->form->getState();

        // Save MailSettings (main settings class)
        $mailSettings = app(MailSettings::class);
        $mailSettings->default_from_address = $mailData['default_from_address'];
        $mailSettings->default_from_name = $mailData['default_from_name'];
        $mailSettings->additional_senders = $mailData['additional_senders'] ?? [];
        $mailSettings->default_locale = $mailData['default_locale'];
        $mailSettings->languages = $mailData['languages'] ?? [];
        $mailSettings->categories = $mailData['categories'] ?? [];
        $mailSettings->save();

        // Save BrandingSettings
        $branding = app(BrandingSettings::class);
        $branding->logo = $this->brandingData['logo'] ?? null;
        $branding->logo_width = (int) ($this->brandingData['logo_width'] ?? 200);
        $branding->logo_height = (int) ($this->brandingData['logo_height'] ?? 50);
        $branding->content_width = (int) ($this->brandingData['content_width'] ?? 600);
        $branding->primary_color = $this->brandingData['primary_color'] ?? '#4F46E5';
        $branding->footer_links = $this->brandingData['footer_links'] ?? [];
        $branding->customer_service_email = $this->brandingData['customer_service_email'] ?? null;
        $branding->customer_service_phone = $this->brandingData['customer_service_phone'] ?? null;
        $branding->save();

        // Save LoggingSettings
        $logging = app(LoggingSettings::class);
        $logging->enabled = (bool) ($this->loggingData['enabled'] ?? true);
        $logging->store_rendered_body = (bool) ($this->loggingData['store_rendered_body'] ?? true);
        $logging->retention_days = isset($this->loggingData['retention_days']) ? (int) $this->loggingData['retention_days'] : null;
        $logging->save();

        // Save AttachmentSettings
        $attachment = app(AttachmentSettings::class);
        $attachment->max_size_mb = (int) ($this->attachmentData['max_size_mb'] ?? 10);
        $attachment->allowed_types = $this->attachmentData['allowed_types'] ?? [];
        $attachment->save();

        $this->getSavedNotification()?->send();
    }
}
