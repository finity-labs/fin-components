<?php

declare(strict_types=1);

namespace FinityLabs\FinMail;

use FinityLabs\FinMail\Contracts\EditorContract;
use FinityLabs\FinMail\Editors\DefaultEditor;
use Spatie\LaravelPackageTools\Package;
use Spatie\LaravelPackageTools\PackageServiceProvider;

class FinMailServiceProvider extends PackageServiceProvider
{
    public static string $name = 'fin-mail';

    public function configurePackage(Package $package): void
    {
        $package
            ->name(static::$name)
            ->hasConfigFile()
            ->hasViews('fin-mail')
            ->hasMigrations([
                'create_email_themes_table',
                'create_email_templates_table',
                'create_email_template_versions_table',
                'create_sent_emails_table',
            ])
            ->hasCommands([
                Commands\InstallCommand::class,
                Commands\CleanupSentEmails::class,
            ]);

        $this->registerSettingsMigrations();
    }

    protected function registerSettingsMigrations(): void
    {
        $settingsPath = __DIR__.'/../database/settings';

        $this->loadMigrationsFrom($settingsPath);

        $this->publishes([
            $settingsPath => database_path('settings'),
        ], 'fin-mail-settings-migrations');
    }

    public function packageRegistered(): void
    {
        $this->app->bind(EditorContract::class, function (): EditorContract {
            $editor = config('fin-mail.editor', 'default');

            return match ($editor) {
                'default' => new DefaultEditor,
                default => new $editor,
            };
        });

        $this->app->singleton('fin-mail', function (): FinMailManager {
            return new FinMailManager;
        });
    }

    public function packageBooted(): void
    {
        if (config('fin-mail.auth_emails.override_verification')) {
            $this->registerVerificationOverride();
        }

        if (config('fin-mail.auth_emails.override_password_reset')) {
            $this->registerPasswordResetOverride();
        }

        if (config('fin-mail.auth_emails.override_welcome')) {
            \Illuminate\Support\Facades\Event::listen(
                \Illuminate\Auth\Events\Registered::class,
                Listeners\SendWelcomeEmail::class,
            );
        }

        $this->registerScheduledCommands();
    }

    protected function registerScheduledCommands(): void
    {
        if (! config('fin-mail.schedule.cleanup_enabled')) {
            return;
        }

        $this->app->afterResolving(
            \Illuminate\Console\Scheduling\Schedule::class,
            function (\Illuminate\Console\Scheduling\Schedule $schedule): void {
                $frequency = config('fin-mail.schedule.cleanup_frequency', 'daily');

                $event = $schedule->command('fin-mail:cleanup')
                    ->description('Clean up old sent email records');

                match ($frequency) {
                    'weekly' => $event->weekly(),
                    'monthly' => $event->monthly(),
                    default => $event->daily(),
                };
            }
        );
    }

    protected function registerVerificationOverride(): void
    {
        \Illuminate\Auth\Notifications\VerifyEmail::toMailUsing(function (mixed $notifiable, string $url): Mail\TemplateMail {
            return Mail\TemplateMail::make('user-verify-email')
                ->models([
                    'user' => $notifiable,
                    'url' => new Helpers\TokenValue($url),
                ]);
        });
    }

    protected function registerPasswordResetOverride(): void
    {
        \Illuminate\Auth\Notifications\ResetPassword::toMailUsing(function (mixed $notifiable, string $token): Mail\TemplateMail {
            $url = url(route('password.reset', [
                'token' => $token,
                'email' => $notifiable->getEmailForPasswordReset(),
            ], false));

            return Mail\TemplateMail::make('user-password-reset')
                ->models([
                    'user' => $notifiable,
                    'url' => new Helpers\TokenValue($url),
                ]);
        });
    }
}
