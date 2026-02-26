<?php

declare(strict_types=1);

namespace FinityLabs\FinMail\Resources\RelationManagers;

use Filament\Actions\Action;
use Filament\Notifications\Notification;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;
use FinityLabs\FinMail\Enums\EmailStatus;
use FinityLabs\FinMail\Mail\TemplateMail;
use FinityLabs\FinMail\Models\SentEmail;
use Illuminate\Support\Facades\Mail;

/**
 * Drop this into any Filament resource to show emails sent for that record.
 *
 * In your resource:
 *   public static function getRelations(): array
 *   {
 *       return [
 *           SentEmailsRelationManager::class,
 *       ];
 *   }
 *
 * Your model needs the HasEmailTemplates trait.
 */
class SentEmailsRelationManager extends RelationManager
{
    protected static string $relationship = 'sentEmails';

    protected static ?string $title = 'Sent Emails';

    protected static ?string $icon = 'heroicon-o-envelope';

    public function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('subject')
                    ->searchable()
                    ->limit(50),

                TextColumn::make('recipients_display')
                    ->label('To')
                    ->limit(40),

                TextColumn::make('template.name')
                    ->label('Template')
                    ->badge()
                    ->color('gray')
                    ->placeholder('—'),

                TextColumn::make('status')
                    ->badge(),

                TextColumn::make('sender.name')
                    ->label('Sent By')
                    ->placeholder('System'),

                TextColumn::make('sent_at')
                    ->label('Sent')
                    ->dateTime('M d, Y H:i')
                    ->sortable(),
            ])
            ->defaultSort('created_at', 'desc')
            ->recordAction(null)
            ->filters([
                SelectFilter::make('status')
                    ->options(EmailStatus::class),
            ])
            ->actions([
                Action::make('view_body')
                    ->label('View')
                    ->icon('heroicon-o-eye')
                    ->modal()
                    ->modalHeading(fn ($record): string => $record->subject)
                    ->modalContent(fn ($record) => view('fin-mail::components.email-preview', [
                        'html' => $record->rendered_body,
                    ]))
                    ->modalWidth('4xl')
                    ->modalSubmitAction(false)
                    ->visible(fn ($record): bool => (bool) $record->rendered_body),

                Action::make('resend')
                    ->label('Resend')
                    ->icon('heroicon-o-arrow-path')
                    ->requiresConfirmation()
                    ->modalDescription('Are you sure you want to resend this email?')
                    ->action(function ($record): void {
                        try {
                            if (! $record->rendered_body || ! $record->email_template_id) {
                                throw new \RuntimeException('Cannot resend: no rendered body or template stored.');
                            }

                            $template = $record->template;
                            if (! $template) {
                                throw new \RuntimeException('Original template no longer exists.');
                            }

                            $mail = TemplateMail::make($template->key)
                                ->overrideSubject($record->subject)
                                ->overrideBody($record->rendered_body);

                            $newLog = SentEmail::create([
                                'email_template_id' => $record->email_template_id,
                                'sender' => $record->sender,
                                'to' => $record->to,
                                'cc' => $record->cc,
                                'bcc' => $record->bcc,
                                'subject' => $record->subject,
                                'rendered_body' => $record->rendered_body,
                                'attachments' => $record->attachments,
                                'status' => EmailStatus::Queued,
                                'sent_by' => auth()->id(),
                                'sendable_type' => $record->sendable_type,
                                'sendable_id' => $record->sendable_id,
                                'metadata' => ['resent_from' => $record->id],
                            ]);

                            $mail->withLogging($newLog);

                            $message = Mail::to($record->to);
                            if (! empty($record->cc)) {
                                $message->cc($record->cc);
                            }
                            if (! empty($record->bcc)) {
                                $message->bcc($record->bcc);
                            }
                            $message->send($mail);

                            Notification::make()
                                ->title('Email resent successfully')
                                ->success()
                                ->send();
                        } catch (\Throwable $e) {
                            Notification::make()
                                ->title('Failed to resend')
                                ->body($e->getMessage())
                                ->danger()
                                ->send();
                        }
                    }),
            ])
            ->emptyStateHeading('No emails sent')
            ->emptyStateDescription('Emails sent for this record will appear here.')
            ->emptyStateIcon('heroicon-o-envelope');
    }
}
