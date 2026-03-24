<?php

declare(strict_types=1);

namespace FinityLabs\FinSentinel\Clusters\FinSentinelSettings\Pages;

use BackedEnum;
use Filament\Actions\Action;
use Filament\Forms\Components\Placeholder;
use Filament\Forms\Components\Repeater;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Notifications\Notification;
use Filament\Pages\SettingsPage;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use FinityLabs\FinSentinel\Clusters\FinSentinelSettings\FinSentinelSettings;
use FinityLabs\FinSentinel\Mail\DebugMail;
use FinityLabs\FinSentinel\Settings\DebugChannelSettings;
use FinityLabs\FinSentinel\Traits\HasPageShieldSupport;
use Illuminate\Support\Facades\Mail;

class ManageDebugChannelSettings extends SettingsPage
{
    use HasPageShieldSupport;

    protected static ?string $cluster = FinSentinelSettings::class;

    protected static string $settings = DebugChannelSettings::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedBugAnt;

    protected static ?int $navigationSort = 2;

    public static function getNavigationLabel(): string
    {
        return 'Debug Channel';
    }

    public function getTitle(): string
    {
        return 'Debug Channel Settings';
    }

    protected function getHeaderActions(): array
    {
        return [
            Action::make('sendTestEmail')
                ->label(__('fin-sentinel::fin-sentinel.test_email_send'))
                ->icon('heroicon-o-paper-airplane')
                ->color('primary')
                ->action(function (): void {
                    $settings = app(DebugChannelSettings::class);

                    if (empty($settings->debug_recipients)) {
                        Notification::make()
                            ->title(__('fin-sentinel::fin-sentinel.test_email_no_recipients'))
                            ->danger()
                            ->send();

                        return;
                    }

                    if (! $settings->debug_enabled) {
                        Notification::make()
                            ->title(__('fin-sentinel::fin-sentinel.test_email_channel_disabled'))
                            ->warning()
                            ->send();
                    }

                    try {
                        $testMail = new DebugMail(
                            formattedData: [
                                'type' => 'array',
                                'data' => [
                                    'message' => 'This is a test debug email from FinSentinel',
                                    'timestamp' => now()->toDateTimeString(),
                                    'status' => 'working',
                                ],
                            ],
                            callSite: ['file' => 'FinSentinel Settings Page', 'line' => 0],
                            requestContext: DebugMail::buildRequestContext(),
                            environmentContext: DebugMail::buildEnvironmentContext(),
                            customSubject: '[TEST] Test Debug Notification',
                        );

                        Mail::to($settings->debug_recipients)->send($testMail);

                        Notification::make()
                            ->title(__('fin-sentinel::fin-sentinel.test_email_sent', [
                                'count' => count($settings->debug_recipients),
                            ]))
                            ->success()
                            ->send();
                    } catch (\Throwable $e) {
                        Notification::make()
                            ->title(__('fin-sentinel::fin-sentinel.test_email_failed'))
                            ->body($e->getMessage())
                            ->danger()
                            ->send();
                    }
                }),
        ];
    }

    public function form(Schema $schema): Schema
    {
        return $schema->schema([
            Toggle::make('debug_enabled')
                ->label('Enable debug channel')
                ->helperText('When disabled, Sentinel::debug() calls will be silently ignored.')
                ->live(),

            Placeholder::make('disabled_warning')
                ->label('')
                ->content('This channel is currently disabled.')
                ->extraAttributes(['class' => 'text-warning-600 dark:text-warning-400 font-medium'])
                ->visible(fn (callable $get): bool => ! $get('debug_enabled')),

            Placeholder::make('no_recipients_warning')
                ->label('')
                ->content('No recipients configured -- notifications won\'t be sent until at least one email is added.')
                ->extraAttributes(['class' => 'text-warning-600 dark:text-warning-400 font-medium'])
                ->visible(fn (callable $get): bool => empty($get('debug_recipients'))),

            Section::make('Recipients')
                ->schema([
                    Repeater::make('debug_recipients')
                        ->label('')
                        ->helperText('Add email addresses that will receive debug notifications.')
                        ->schema([
                            TextInput::make('email')
                                ->label('Email address')
                                ->email()
                                ->required()
                                ->maxLength(255),
                        ])
                        ->defaultItems(0)
                        ->collapsible()
                        ->itemLabel(fn (array $state): string => $state['email'] ?? 'New recipient'),
                ]),

            Section::make('Throttling')
                ->schema([
                    Toggle::make('debug_throttle_enabled')
                        ->label('Enable throttling')
                        ->helperText('When disabled, every debug call sends an email. When enabled, duplicate calls are throttled.')
                        ->live(),

                    TextInput::make('debug_throttle_minutes')
                        ->label('Throttle rate')
                        ->helperText('Minimum minutes between duplicate debug emails.')
                        ->numeric()
                        ->required()
                        ->minValue(1)
                        ->maxValue(1440)
                        ->suffix('minutes')
                        ->visible(fn (callable $get): bool => (bool) $get('debug_throttle_enabled')),
                ]),
        ]);
    }

    protected function mutateFormDataBeforeFill(array $data): array
    {
        $data['debug_recipients'] = array_map(
            fn (string $email): array => ['email' => $email],
            $data['debug_recipients'] ?? []
        );

        return $data;
    }

    protected function mutateFormDataBeforeSave(array $data): array
    {
        $data['debug_recipients'] = array_values(array_filter(
            array_map(
                fn (array $row): ?string => $row['email'] ?? null,
                $data['debug_recipients'] ?? []
            )
        ));

        return $data;
    }
}
