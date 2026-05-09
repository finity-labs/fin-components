<?php

declare(strict_types=1);

namespace FinityLabs\FinMail\Listeners;

use Filament\Auth\Events\Registered;
use FinityLabs\FinMail\Mail\TemplateMail;
use FinityLabs\FinMail\Models\EmailTemplate;
use Illuminate\Foundation\Auth\User;
use Illuminate\Support\Facades\Mail;

class SendWelcomeEmail
{
    public function handle(Registered $event): void
    {
        /** @var User $user */
        $user = $event->getUser();

        $template = EmailTemplate::findByKey('user-welcome');

        if (! $template) {
            return;
        }

        try {
            Mail::to($user->getEmailForVerification())->send(
                TemplateMail::make('user-welcome', app()->getLocale())
                    ->models(['user' => $user])
            );
        } catch (\Throwable $e) {
            report($e);
        }
    }
}
