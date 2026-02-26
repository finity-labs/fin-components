<?php

declare(strict_types=1);

namespace FinityLabs\FinMail\Resources\EmailTemplateResource\Tables;

use Filament\Actions\Action;
use Filament\Actions\ActionGroup;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteAction;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Actions\ViewAction;
use Filament\Forms\Components\TextInput;
use Filament\Notifications\Notification;
use Filament\Support\Enums\Width;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Filters\TernaryFilter;
use Filament\Tables\Table;
use FinityLabs\FinMail\Mail\TemplateMail;
use FinityLabs\FinMail\Resources\EmailTemplateResource\EmailTemplateResource;
use FinityLabs\FinMail\Settings\GeneralSettings;
use Illuminate\Support\Facades\Mail;

class EmailTemplatesTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('name')
                    ->searchable()
                    ->sortable(),

                TextColumn::make('key')
                    ->badge()
                    ->color('gray')
                    ->searchable(),

                TextColumn::make('translations')
                    ->label(__('fin-mail::fin-mail.template.columns.locales'))
                    ->badge()
                    ->getStateUsing(fn ($record): array => $record->getTranslatedLocales('name')),

                TextColumn::make('category')
                    ->badge()
                    ->formatStateUsing(fn (string $state): string => app(GeneralSettings::class)->getCategoryOptions()[$state] ?? $state),

                TextColumn::make('subject')
                    ->limit(40)
                    ->toggleable(),

                IconColumn::make('is_active')
                    ->boolean()
                    ->label(__('fin-mail::fin-mail.template.columns.active')),

                TextColumn::make('sent_emails_count')
                    ->counts('sentEmails')
                    ->label(__('fin-mail::fin-mail.template.columns.sent'))
                    ->sortable(),

                TextColumn::make('updated_at')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(),
            ])
            ->filters([
                SelectFilter::make('category')
                    ->options(fn (): array => app(GeneralSettings::class)->getCategoryOptions()),

                TernaryFilter::make('is_active')
                    ->label(__('fin-mail::fin-mail.template.columns.active')),
            ])
            ->deferFilters()
            ->recordAction(null)
            ->recordActions([
                ActionGroup::make([
                    ViewAction::make(),
                    EditAction::make(),

                    Action::make('preview')
                        ->label(__('fin-mail::fin-mail.template.actions.preview'))
                        ->icon(Heroicon::OutlinedEye)
                        ->modal()
                        ->modalHeading(fn ($record): string => "Preview: {$record->name}")
                        ->modalContent(fn ($record) => view('fin-mail::components.email-preview', [
                            'html' => $record->body,
                        ]))
                        ->modalWidth(Width::FourExtraLarge)
                        ->modalSubmitAction(false),

                    Action::make('send_test')
                        ->label(__('fin-mail::fin-mail.template.actions.send_test'))
                        ->icon(Heroicon::OutlinedPaperAirplane)
                        ->modal()
                        ->schema([
                            TextInput::make('test_email')
                                ->label(__('fin-mail::fin-mail.template.actions.send_test_field'))
                                ->email()
                                ->required()
                                ->default(fn (): ?string => auth()->user()?->email),
                        ])
                        ->action(function ($record, array $data): void {
                            try {
                                $mail = TemplateMail::make($record->key)
                                    ->models([
                                        'user' => auth()->user(),
                                    ]);

                                Mail::to($data['test_email'])->send($mail);

                                Notification::make()
                                    ->title(__('fin-mail::fin-mail.template.notifications.test_sent'))
                                    ->body(__('fin-mail::fin-mail.template.notifications.test_sent_body', ['email' => $data['test_email']]))
                                    ->success()
                                    ->send();
                            } catch (\Throwable $e) {
                                Notification::make()
                                    ->title(__('fin-mail::fin-mail.template.notifications.test_failed'))
                                    ->body($e->getMessage())
                                    ->danger()
                                    ->send();
                            }
                        }),

                    Action::make('compose')
                        ->label(__('fin-mail::fin-mail.template.actions.compose'))
                        ->icon(Heroicon::OutlinedPencilSquare)
                        ->url(fn ($record): string => EmailTemplateResource::getUrl('compose', ['record' => $record])),

                    DeleteAction::make(),
                ]),
            ])
            ->toolbarActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make(),
                ]),
            ])
            ->defaultSort('updated_at', 'desc');
    }
}
