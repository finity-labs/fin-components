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
use FinityLabs\FinMail\Settings\AttachmentSettings;
use FinityLabs\FinMail\Settings\BrandingSettings;
use FinityLabs\FinMail\Settings\LoggingSettings;
use FinityLabs\FinMail\Settings\MailSettings;
use UnitEnum;

class ManageFinMailSettings extends SettingsPage
{
    protected static string $settings = MailSettings::class;

    protected static string|BackedEnum|null $navigationIcon = 'heroicon-o-cog-6-tooth';

    protected static string|UnitEnum|null $navigationGroup = 'Email';

    protected static ?int $navigationSort = 10;

    protected static ?string $title = 'Email Settings';

    protected static ?string $navigationLabel = 'Settings';

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
        return Tab::make('General')
            ->icon('heroicon-o-envelope')
            ->schema([
                Section::make('Default Sender')
                    ->description('The default "From" address for all emails sent by the plugin.')
                    ->schema([
                        TextInput::make('default_from_address')
                            ->label('From Email')
                            ->email()
                            ->required()
                            ->maxLength(255),

                        TextInput::make('default_from_name')
                            ->label('From Name')
                            ->required()
                            ->maxLength(255),
                    ])
                    ->columns(2),

                Section::make('Additional Senders')
                    ->description('Extra "From" addresses users can choose when composing emails.')
                    ->schema([
                        Repeater::make('additional_senders')
                            ->label('')
                            ->schema([
                                TextInput::make('address')
                                    ->label('Email')
                                    ->email()
                                    ->required()
                                    ->maxLength(255),
                                TextInput::make('name')
                                    ->label('Display Name')
                                    ->required()
                                    ->maxLength(255),
                            ])
                            ->columns(2)
                            ->defaultItems(0)
                            ->collapsible()
                            ->itemLabel(fn (array $state): ?string => $state['name'] ?? $state['address'] ?? 'New Sender'),
                    ]),

                Section::make('Localization')
                    ->schema([
                        TextInput::make('default_locale')
                            ->label('Default Locale')
                            ->required()
                            ->maxLength(10)
                            ->helperText('The default language for new templates (e.g., en, hu, de).'),

                        Repeater::make('languages')
                            ->label('Available Languages')
                            ->schema([
                                TextInput::make('code')
                                    ->label('Code')
                                    ->required()
                                    ->maxLength(10)
                                    ->placeholder('en'),
                                TextInput::make('display')
                                    ->label('Display Name')
                                    ->required()
                                    ->maxLength(50)
                                    ->placeholder('English'),
                                TextInput::make('flag-icon')
                                    ->label('Flag Icon')
                                    ->maxLength(10)
                                    ->placeholder('gb'),
                            ])
                            ->columns(3)
                            ->defaultItems(1)
                            ->collapsible()
                            ->itemLabel(fn (array $state): ?string => $state['display'] ?? $state['code'] ?? 'New Language'),
                    ]),

                Section::make('Template Categories')
                    ->schema([
                        Repeater::make('categories')
                            ->label('')
                            ->schema([
                                TextInput::make('key')
                                    ->label('Key')
                                    ->required()
                                    ->maxLength(50)
                                    ->placeholder('transactional'),
                                TextInput::make('label')
                                    ->label('Label')
                                    ->required()
                                    ->maxLength(100)
                                    ->placeholder('Transactional'),
                            ])
                            ->columns(2)
                            ->defaultItems(0)
                            ->collapsible()
                            ->itemLabel(fn (array $state): ?string => $state['label'] ?? $state['key'] ?? 'New Category'),
                    ]),
            ]);
    }

    protected function brandingTab(): Tab
    {
        return Tab::make('Branding')
            ->icon('heroicon-o-paint-brush')
            ->statePath('brandingData')
            ->schema([
                Section::make('Logo')
                    ->schema([
                        TextInput::make('logo')
                            ->label('Logo URL or Path')
                            ->maxLength(500)
                            ->placeholder('https://example.com/logo.png')
                            ->helperText('Absolute URL or path to your email logo.'),

                        Grid::make(3)->schema([
                            TextInput::make('logo_width')
                                ->label('Width (px)')
                                ->numeric()
                                ->required()
                                ->minValue(10)
                                ->maxValue(800),

                            TextInput::make('logo_height')
                                ->label('Height (px)')
                                ->numeric()
                                ->required()
                                ->minValue(10)
                                ->maxValue(400),

                            TextInput::make('content_width')
                                ->label('Content Width (px)')
                                ->numeric()
                                ->required()
                                ->minValue(300)
                                ->maxValue(900),
                        ]),
                    ]),

                Section::make('Colors')
                    ->schema([
                        ColorPicker::make('primary_color')
                            ->label('Primary Color'),
                    ]),

                Section::make('Footer Links')
                    ->schema([
                        Repeater::make('footer_links')
                            ->label('')
                            ->schema([
                                TextInput::make('name')
                                    ->label('Label')
                                    ->required()
                                    ->maxLength(100),
                                TextInput::make('url')
                                    ->label('URL')
                                    ->url()
                                    ->required()
                                    ->maxLength(500),
                            ])
                            ->columns(2)
                            ->defaultItems(0)
                            ->collapsible()
                            ->itemLabel(fn (array $state): ?string => $state['name'] ?? 'New Link'),
                    ]),

                Section::make('Customer Service')
                    ->schema([
                        TextInput::make('customer_service_email')
                            ->label('Support Email')
                            ->email()
                            ->maxLength(255),

                        TextInput::make('customer_service_phone')
                            ->label('Support Phone')
                            ->tel()
                            ->maxLength(50),
                    ])
                    ->columns(2),
            ]);
    }

    protected function loggingTab(): Tab
    {
        return Tab::make('Logging')
            ->icon('heroicon-o-document-magnifying-glass')
            ->statePath('loggingData')
            ->schema([
                Section::make('Email Logging')
                    ->description('Control how sent emails are recorded in the database.')
                    ->schema([
                        Toggle::make('enabled')
                            ->label('Enable Logging')
                            ->helperText('When disabled, no sent email records will be created.'),

                        Toggle::make('store_rendered_body')
                            ->label('Store Rendered Body')
                            ->helperText('Save the final HTML of each sent email. Required for resend and preview features.'),

                        TextInput::make('retention_days')
                            ->label('Retention (days)')
                            ->numeric()
                            ->nullable()
                            ->minValue(1)
                            ->maxValue(3650)
                            ->helperText('Auto-delete sent email records after this many days. Leave empty to keep forever.'),
                    ]),
            ]);
    }

    protected function attachmentTab(): Tab
    {
        return Tab::make('Attachments')
            ->icon('heroicon-o-paper-clip')
            ->statePath('attachmentData')
            ->schema([
                Section::make('Attachment Rules')
                    ->description('Configure limits for file attachments in composed emails.')
                    ->schema([
                        TextInput::make('max_size_mb')
                            ->label('Max File Size (MB)')
                            ->numeric()
                            ->required()
                            ->minValue(1)
                            ->maxValue(100),

                        TagsInput::make('allowed_types')
                            ->label('Allowed File Extensions')
                            ->placeholder('Add extension (e.g., pdf)')
                            ->helperText('File extensions allowed for upload.'),
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
