<?php

declare(strict_types=1);

namespace FinityLabs\FinMail\Actions;

use Closure;
use Filament\Actions\Action;
use Filament\Schemas\Components\Component;
use Filament\Support\Enums\Width;
use Filament\Support\Icons\Heroicon;
use Illuminate\Database\Eloquent\Model;

/**
 * Reusable Filament action to compose and send emails from any resource.
 *
 * Works as a page/header action, table row action, or anywhere Filament actions are used.
 *
 * Usage:
 *   SendEmailAction::make()
 *       ->template('invoice-sent')
 *       ->recipient(fn (Invoice $record) => $record->customer->email)
 *       ->models(fn (Invoice $record) => ['invoice' => $record, 'customer' => $record->customer])
 */
class SendEmailAction extends Action
{
    protected ?string $templateKey = null;

    protected Closure|string|null $recipientResolver = null;

    protected Closure|array|null $ccResolver = null;

    protected Closure|array|null $bccResolver = null;

    protected Closure|array|null $modelsResolver = null;

    protected Closure|array|null $attachmentsResolver = null;

    protected ?Closure $onSentCallback = null;

    public static function getDefaultName(): ?string
    {
        return 'send-email';
    }

    protected function setUp(): void
    {
        parent::setUp();

        $this
            ->label(__('fin-mail::fin-mail.send_action.label'))
            ->icon(Heroicon::OutlinedPaperAirplane)
            ->modal()
            ->modalHeading(__('fin-mail::fin-mail.send_action.modal_heading'))
            ->modalWidth(Width::FiveExtraLarge)
            ->modalSubmitActionLabel(__('fin-mail::fin-mail.send_action.submit'))
            ->schema(fn (?Model $record): array => $this->getComposeForm($record))
            ->action(fn (array $data, ?Model $record) => $this->sendEmail($data, $record))
            ->modalIcon(Heroicon::OutlinedEnvelope);
    }

    /*
    |--------------------------------------------------------------------------
    | Fluent Configuration
    |--------------------------------------------------------------------------
    */

    public function template(string $key): static
    {
        $this->templateKey = $key;

        return $this;
    }

    public function recipient(Closure|string $resolver): static
    {
        $this->recipientResolver = $resolver;

        return $this;
    }

    public function cc(Closure|array $resolver): static
    {
        $this->ccResolver = $resolver;

        return $this;
    }

    public function bcc(Closure|array $resolver): static
    {
        $this->bccResolver = $resolver;

        return $this;
    }

    public function models(Closure|array $resolver): static
    {
        $this->modelsResolver = $resolver;

        return $this;
    }

    public function attachments(Closure|array $resolver): static
    {
        $this->attachmentsResolver = $resolver;

        return $this;
    }

    public function onSent(Closure $callback): static
    {
        $this->onSentCallback = $callback;

        return $this;
    }

    /*
    |--------------------------------------------------------------------------
    | Compose Form
    |--------------------------------------------------------------------------
    */

    /**
     * @return array<int, Component>
     */
    protected function getComposeForm(?Model $record): array
    {
        return ComposeFormBuilder::make(
            templateKey: $this->templateKey,
            record: $record,
            recipientResolver: $this->recipientResolver,
            ccResolver: $this->ccResolver,
            bccResolver: $this->bccResolver,
            modelsResolver: $this->modelsResolver,
            attachmentsResolver: $this->attachmentsResolver,
        )->build();
    }

    /*
    |--------------------------------------------------------------------------
    | Send Logic
    |--------------------------------------------------------------------------
    */

    protected function sendEmail(array $data, ?Model $record): void
    {
        (new EmailSender(
            data: $data,
            record: $record,
            templateKey: $this->templateKey,
            modelsResolver: $this->modelsResolver,
            attachmentsResolver: $this->attachmentsResolver,
            onSentCallback: $this->onSentCallback,
        ))->send();
    }
}
