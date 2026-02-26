<?php

declare(strict_types=1);

namespace FinityLabs\FinMail\Resources\EmailTemplateResource\Pages;

use BackedEnum;
use Filament\Actions\Action;
use Filament\Forms\Components\FileUpload;
use Filament\Forms\Components\Placeholder;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TagsInput;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Concerns\InteractsWithForms;
use Filament\Resources\Pages\Page;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use FinityLabs\FinMail\Actions\EmailSender;
use FinityLabs\FinMail\Contracts\EditorContract;
use FinityLabs\FinMail\Models\EmailTemplate;
use FinityLabs\FinMail\Resources\EmailTemplateResource\EmailTemplateResource;
use FinityLabs\FinMail\Settings\AttachmentSettings;
use FinityLabs\FinMail\Settings\MailSettings;

/**
 * Full-page compose screen.
 *
 * Loaded from: /admin/email-templates/{record}/compose
 */
class ComposeEmail extends Page
{
    use InteractsWithForms;

    protected static string $resource = EmailTemplateResource::class;

    protected string $view = 'fin-mail::pages.compose-email';

    protected static ?string $title = 'Compose Email';

    protected static string|BackedEnum|null $navigationIcon = 'heroicon-o-paper-airplane';

    /** @var array<string, mixed>|null */
    public ?array $data = [];

    public EmailTemplate $record;

    public function mount(EmailTemplate $record): void
    {
        $this->record = $record;

        $this->form->fill([
            'template_key' => $record->key,
            'from' => $record->from['address'] ?? app(MailSettings::class)->default_from_address,
            'to' => [],
            'cc' => [],
            'bcc' => [],
            'subject' => $record->subject,
            'preheader' => $record->preheader ?? '',
            'body' => $record->body,
        ]);
    }

    public function form(Schema $form): Schema
    {
        $editor = app(EditorContract::class);

        $mailSettings = app(MailSettings::class);

        $senders = collect($mailSettings->additional_senders)
            ->prepend(['address' => $mailSettings->default_from_address, 'name' => $mailSettings->default_from_name])
            ->filter()
            ->mapWithKeys(fn (array $s): array => [$s['address'] => "{$s['name']} <{$s['address']}>"])
            ->all();

        return $form->schema([

            Section::make('Recipients')
                ->icon('heroicon-o-user-group')
                ->schema([
                    Select::make('from')
                        ->label('From')
                        ->options($senders)
                        ->native(false)
                        ->required(),

                    TagsInput::make('to')
                        ->label('To')
                        ->placeholder('Enter email addresses')
                        ->required()
                        ->nestedRecursiveRules(['email']),

                    TagsInput::make('cc')
                        ->label('CC')
                        ->placeholder('CC addresses')
                        ->nestedRecursiveRules(['email']),

                    TagsInput::make('bcc')
                        ->label('BCC')
                        ->placeholder('BCC addresses')
                        ->nestedRecursiveRules(['email']),
                ])
                ->columns(2)
                ->collapsible(),

            Section::make('Email Content')
                ->icon('heroicon-o-document-text')
                ->schema([
                    TextInput::make('subject')
                        ->required()
                        ->maxLength(255)
                        ->columnSpanFull(),

                    TextInput::make('preheader')
                        ->maxLength(255)
                        ->helperText('Preview text shown in email clients before opening')
                        ->columnSpanFull(),

                    $editor->make('body')
                        ->required(),
                ]),

            Section::make('Attachments')
                ->icon('heroicon-o-paper-clip')
                ->schema([
                    FileUpload::make('attachments')
                        ->label('Attach Files')
                        ->multiple()
                        ->disk(config('fin-mail.attachments_disk', 'local'))
                        ->directory('email-attachments')
                        ->maxSize(app(AttachmentSettings::class)->max_size_mb * 1024)
                        ->columnSpanFull(),
                ])
                ->collapsible()
                ->collapsed(),

            Section::make('Available Tokens')
                ->icon('heroicon-o-code-bracket')
                ->schema([
                    Placeholder::make('tokens_info')
                        ->content(function (): string {
                            $tokens = $this->record->token_schema ?? [];
                            if (empty($tokens)) {
                                return 'No tokens documented for this template. Tokens like {{ user.name }} will be replaced when sent via the API/code.';
                            }

                            return collect($tokens)
                                ->map(fn (array $t): string => "**{{ {$t['token']} }}** — {$t['description']}".($t['example'] ?? false ? " (e.g., {$t['example']})" : ''))
                                ->implode("\n\n");
                        }),
                ])
                ->collapsible()
                ->collapsed(),

        ])->statePath('data');
    }

    public function send(): void
    {
        $data = $this->form->getState();

        $sender = new EmailSender(
            data: $data,
            record: null,
            templateKey: $this->record->key,
        );

        $success = $sender->send();

        if ($success) {
            $this->redirect(static::getResource()::getUrl('index'));
        }
    }

    public function preview(): void
    {
        $this->form->getState();

        $this->dispatch('open-modal', id: 'email-preview');
    }

    public function getTitle(): string
    {
        return "Compose: {$this->record->name}";
    }

    protected function getHeaderActions(): array
    {
        return [
            Action::make('back')
                ->label('Back to Templates')
                ->icon('heroicon-o-arrow-left')
                ->url(static::getResource()::getUrl('index'))
                ->color('gray'),
        ];
    }

    /**
     * @return array<int, Action>
     */
    protected function getFormActions(): array
    {
        return [
            Action::make('send')
                ->label('Send Email')
                ->icon('heroicon-o-paper-airplane')
                ->action('send')
                ->color('primary')
                ->requiresConfirmation()
                ->modalHeading('Confirm Send')
                ->modalDescription('Are you sure you want to send this email?'),

            Action::make('preview')
                ->label('Preview')
                ->icon('heroicon-o-eye')
                ->action('preview')
                ->color('gray'),
        ];
    }
}
