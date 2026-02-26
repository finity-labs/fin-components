<?php

declare(strict_types=1);

namespace FinityLabs\FinMail\Resources\EmailTemplateResource\Pages;

use BackedEnum;
use Filament\Actions\Action;
use Filament\Forms\Concerns\InteractsWithForms;
use Filament\Resources\Pages\Page;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use FinityLabs\FinMail\Actions\EmailSender;
use FinityLabs\FinMail\Models\EmailTemplate;
use FinityLabs\FinMail\Resources\EmailTemplateResource\EmailTemplateResource;
use FinityLabs\FinMail\Resources\EmailTemplateResource\Schemas\ComposeEmailForm;
use FinityLabs\FinMail\Settings\GeneralSettings;

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

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedPaperAirplane;

    /** @var array<string, mixed>|null */
    public ?array $data = [];

    public EmailTemplate $record;

    public function mount(EmailTemplate $record): void
    {
        $this->record = $record;

        $this->form->fill([
            'template_key' => $record->key,
            'from' => $record->from['address'] ?? app(GeneralSettings::class)->default_from_address,
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
        return ComposeEmailForm::configure($form, $this->record);
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
                ->icon(Heroicon::OutlinedArrowLeft)
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
                ->icon(Heroicon::OutlinedPaperAirplane)
                ->action('send')
                ->color('primary')
                ->requiresConfirmation()
                ->modalHeading(__('fin-mail::fin-mail.compose.confirm.heading'))
                ->modalDescription(__('fin-mail::fin-mail.compose.confirm.description')),

            Action::make('preview')
                ->label(__('fin-mail::fin-mail.compose.actions.preview'))
                ->icon(Heroicon::OutlinedEye)
                ->action('preview')
                ->color('gray'),
        ];
    }
}
