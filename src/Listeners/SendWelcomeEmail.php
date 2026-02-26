<?php

declare(strict_types=1);

namespace FinityLabs\FinMail\Listeners;

use FinityLabs\FinMail\Mail\TemplateMail;
use FinityLabs\FinMail\Models\EmailTemplate;
use Illuminate\Auth\Events\Registered;
use Illuminate\Support\Facades\Mail;

class SendWelcomeEmail
{
    public function handle(Registered $event): void
    {
        $user = $event->user;

        $template = EmailTemplate::findByKey('user-welcome');

        if (! $template) {
            return;
        }

        try {
            Mail::to($user->email)->send(
                TemplateMail::make('user-welcome')
                    ->models(['user' => $user])
            );
        } catch (\Throwable $e) {
            report($e);
        }
    }
}
