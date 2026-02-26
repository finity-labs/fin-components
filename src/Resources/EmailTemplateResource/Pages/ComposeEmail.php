<?php

declare(strict_types=1);

namespace FinityLabs\FinMail\Resources\EmailTemplateResource\Pages;

use BackedEnum;
use Filament\Actions\Action;
use Filament\Forms\Components\FileUpload;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TagsInput;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Concerns\InteractsWithForms;
use Filament\Infolists\Components\TextEntry;
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

    protected static ?string $title = null;

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

            Section::make(__('fin-mail::fin-mail.compose.sections.recipients'))
                ->icon('heroicon-o-user-group')
                ->schema([
                    Select::make('from')
                        ->label(__('fin-mail::fin-mail.compose.fields.from'))
                        ->options($senders)
                        ->native(false)
                        ->required(),

                    TagsInput::make('to')
                        ->label(__('fin-mail::fin-mail.compose.fields.to'))
                        ->placeholder(__('fin-mail::fin-mail.compose.fields.to_placeholder'))
                        ->required()
                        ->nestedRecursiveRules(['email']),

                    TagsInput::make('cc')
                        ->label(__('fin-mail::fin-mail.compose.fields.cc'))
                        ->placeholder(__('fin-mail::fin-mail.compose.fields.cc_placeholder'))
                        ->nestedRecursiveRules(['email']),

                    TagsInput::make('bcc')
                        ->label(__('fin-mail::fin-mail.compose.fields.bcc'))
                        ->placeholder(__('fin-mail::fin-mail.compose.fields.bcc_placeholder'))
                        ->nestedRecursiveRules(['email']),
                ])
                ->columns(2)
                ->collapsible(),

            Section::make(__('fin-mail::fin-mail.compose.sections.content'))
                ->icon('heroicon-o-document-text')
                ->schema([
                    TextInput::make('subject')
                        ->required()
                        ->maxLength(255)
                        ->columnSpanFull(),

                    TextInput::make('preheader')
                        ->maxLength(255)
                        ->helperText(__('fin-mail::fin-mail.compose.fields.preheader_helper'))
                        ->columnSpanFull(),

                    $editor->make('body')
                        ->required(),
                ]),

            Section::make(__('fin-mail::fin-mail.compose.sections.attachments'))
                ->icon('heroicon-o-paper-clip')
                ->schema([
                    FileUpload::make('attachments')
                        ->label(__('fin-mail::fin-mail.compose.fields.attach_files'))
                        ->multiple()
                        ->disk(config('fin-mail.attachments_disk', 'local'))
                        ->directory('email-attachments')
                        ->maxSize(app(AttachmentSettings::class)->max_size_mb * 1024)
                        ->columnSpanFull(),
                ])
                ->collapsible()
                ->collapsed(),

            Section::make(__('fin-mail::fin-mail.compose.sections.tokens'))
                ->icon('heroicon-o-code-bracket')
                ->schema([
                    TextEntry::make('tokens_info')
                        ->state(function (): string {
                            $tokens = $this->record->token_schema ?? [];
                            if (empty($tokens)) {
                                return __('fin-mail::fin-mail.compose.fields.no_tokens');
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
        return __('fin-mail::fin-mail.compose.title_with_name', ['name' => $this->record->name]);
    }

    protected function getHeaderActions(): array
    {
        return [
            Action::make('back')
                ->label(__('fin-mail::fin-mail.template.actions.back_to_templates'))
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
                ->label(__('fin-mail::fin-mail.compose.actions.send'))
                ->icon('heroicon-o-paper-airplane')
                ->action('send')
                ->color('primary')
                ->requiresConfirmation()
                ->modalHeading(__('fin-mail::fin-mail.compose.confirm.heading'))
                ->modalDescription(__('fin-mail::fin-mail.compose.confirm.description')),

            Action::make('preview')
                ->label(__('fin-mail::fin-mail.compose.actions.preview'))
                ->icon('heroicon-o-eye')
                ->action('preview')
                ->color('gray'),
        ];
    }
}
