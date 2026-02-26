<?php

declare(strict_types=1);

namespace FinityLabs\FinMail\Resources\SentEmailResource\Tables;

use Filament\Actions\Action;
use Filament\Forms\Components\DatePicker;
use Filament\Notifications\Notification;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\Filter;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;
use FinityLabs\FinMail\Enums\EmailStatus;
use FinityLabs\FinMail\Mail\TemplateMail;
use FinityLabs\FinMail\Models\SentEmail;
use Illuminate\Support\Facades\Mail;

class SentEmailsTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('subject')
                    ->searchable()
                    ->sortable()
                    ->limit(50),

                TextColumn::make('recipients_display')
                    ->label('To')
                    ->limit(40)
                    ->searchable(
                        query: fn ($query, string $search) => $query->whereJsonContains('to', $search)
                    ),

                TextColumn::make('template.name')
                    ->label('Template')
                    ->badge()
                    ->color('gray')
                    ->placeholder('Custom'),

                TextColumn::make('status')
                    ->badge(),

                TextColumn::make('sender.name')
                    ->label('Sent By')
                    ->placeholder('System'),

                TextColumn::make('sendable_type')
                    ->label('Related To')
                    ->formatStateUsing(fn (?string $state): string => $state ? class_basename($state) : '—')
                    ->toggleable(isToggledHiddenByDefault: true),

                TextColumn::make('sent_at')
                    ->label('Sent')
                    ->dateTime('M d, Y H:i')
                    ->sortable(),
            ])
            ->defaultSort('created_at', 'desc')
            ->deferFilters()
            ->recordAction(null)
            ->filters([
                SelectFilter::make('status')
                    ->options(EmailStatus::class),

                Filter::make('sent_at')
                    ->form([
                        DatePicker::make('from')->label('From'),
                        DatePicker::make('until')->label('Until'),
                    ])
                    ->query(
                        fn ($query, array $data) => $query
                            ->when($data['from'], fn ($q, $date) => $q->whereDate('sent_at', '>=', $date))
                            ->when($data['until'], fn ($q, $date) => $q->whereDate('sent_at', '<=', $date))
                    ),
            ])
            ->actions([
                Action::make('view')
                    ->label('View')
                    ->icon('heroicon-o-eye')
                    ->modal()
                    ->modalHeading(fn ($record): string => $record->subject)
                    ->modalContent(fn ($record) => view('fin-mail::components.sent-email-detail', [
                        'email' => $record,
                    ]))
                    ->modalWidth('5xl')
                    ->modalSubmitAction(false),

                Action::make('resend')
                    ->label('Resend')
                    ->icon('heroicon-o-arrow-path')
                    ->requiresConfirmation()
                    ->modalDescription('This will send a new copy of the email to the original recipients.')
                    ->action(function ($record): void {
                        try {
                            if (! $record->rendered_body || ! $record->email_template_id) {
                                throw new \RuntimeException('Cannot resend: no rendered body stored. Enable logging.store_rendered_body in settings.');
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
                                ->title('Failed to resend email')
                                ->body($e->getMessage())
                                ->danger()
                                ->send();
                        }
                    }),
            ])
            ->poll('30s');
    }
}
