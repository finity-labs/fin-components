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
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Filters\TernaryFilter;
use Filament\Tables\Table;
use FinityLabs\FinMail\Enums\TemplateCategory;
use FinityLabs\FinMail\Mail\TemplateMail;
use FinityLabs\FinMail\Resources\EmailTemplateResource\EmailTemplateResource;
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
                    ->label('Locales')
                    ->badge()
                    ->getStateUsing(fn ($record): array => $record->getTranslatedLocales('name')),

                TextColumn::make('category')
                    ->badge(),

                TextColumn::make('subject')
                    ->limit(40)
                    ->toggleable(),

                IconColumn::make('is_active')
                    ->boolean()
                    ->label('Active'),

                TextColumn::make('sent_emails_count')
                    ->counts('sentEmails')
                    ->label('Sent')
                    ->sortable(),

                TextColumn::make('updated_at')
                    ->dateTime('M d, Y')
                    ->sortable()
                    ->toggleable(),
            ])
            ->filters([
                SelectFilter::make('category')
                    ->options(TemplateCategory::class),

                TernaryFilter::make('is_active')
                    ->label('Active'),
            ])
            ->deferFilters()
            ->recordAction(null)
            ->actions([
                ActionGroup::make([
                    ViewAction::make(),
                    EditAction::make(),

                    Action::make('preview')
                        ->label('Preview')
                        ->icon('heroicon-o-eye')
                        ->modal()
                        ->modalHeading(fn ($record): string => "Preview: {$record->name}")
                        ->modalContent(fn ($record) => view('fin-mail::components.email-preview', [
                            'html' => $record->body,
                        ]))
                        ->modalWidth('4xl')
                        ->modalSubmitAction(false),

                    Action::make('send_test')
                        ->label('Send Test')
                        ->icon('heroicon-o-paper-airplane')
                        ->modal()
                        ->form([
                            TextInput::make('test_email')
                                ->label('Send to')
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
                                    ->title('Test email sent!')
                                    ->body("Sent to {$data['test_email']}")
                                    ->success()
                                    ->send();
                            } catch (\Throwable $e) {
                                Notification::make()
                                    ->title('Failed to send test email')
                                    ->body($e->getMessage())
                                    ->danger()
                                    ->send();
                            }
                        }),

                    Action::make('compose')
                        ->label('Compose Email')
                        ->icon('heroicon-o-pencil-square')
                        ->url(fn ($record): string => EmailTemplateResource::getUrl('compose', ['record' => $record])),

                    DeleteAction::make(),
                ]),
            ])
            ->bulkActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make(),
                ]),
            ])
            ->defaultSort('updated_at', 'desc');
    }
}
