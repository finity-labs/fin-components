<?php

declare(strict_types=1);

namespace FinityLabs\FinMail\Database\Seeders;

use FinityLabs\FinMail\Enums\TemplateCategory;
use FinityLabs\FinMail\Models\EmailTemplate;
use FinityLabs\FinMail\Models\EmailTheme;
use Illuminate\Database\Seeder;

class EmailTemplateSeeder extends Seeder
{
    public function run(): void
    {
        $this->seedDefaultTheme();
        $this->seedTemplates();
    }

    protected function seedDefaultTheme(): void
    {
        EmailTheme::firstOrCreate(
            ['name' => 'Default'],
            [
                'colors' => EmailTheme::defaultColors(),
                'is_default' => true,
            ]
        );
    }

    protected function seedTemplates(): void
    {
        $theme = EmailTheme::where('is_default', true)->first();
        $locale = app()->getLocale();

        $templates = [
            [
                'key' => 'user-welcome',
                'name' => [$locale => 'Welcome New User'],
                'category' => TemplateCategory::Transactional,
                'subject' => [$locale => 'Welcome to {{ config.app.name }}, {{ user.name }}!'],
                'preheader' => [$locale => "We're glad you're here."],
                'body' => [$locale => <<<'HTML'
<h2>Welcome aboard, {{ user.name }}!</h2>
<p>Thanks for joining <strong>{{ config.app.name }}</strong>. We're excited to have you.</p>
<p>Here are a few things you can do to get started:</p>
<ul>
    <li>Complete your profile</li>
    <li>Explore the dashboard</li>
    <li>Check out our documentation</li>
</ul>
<p>If you have any questions, just reply to this email — we're here to help.</p>
<p>Cheers,<br>The {{ config.app.name }} Team</p>
HTML],
                'token_schema' => [
                    ['token' => 'user.name', 'description' => 'Full name of the registered user', 'example' => 'John Doe'],
                    ['token' => 'user.email', 'description' => 'Email address of the user', 'example' => 'john@example.com'],
                    ['token' => 'config.app.name', 'description' => 'Application name', 'example' => 'MyApp'],
                ],
            ],
            [
                'key' => 'user-verify-email',
                'name' => [$locale => 'Verify Email Address'],
                'category' => TemplateCategory::System,
                'subject' => [$locale => 'Verify your email address'],
                'preheader' => [$locale => 'Please confirm your email to activate your account.'],
                'body' => [$locale => <<<'HTML'
<h2>Verify your email address</h2>
<p>Hi {{ user.name }},</p>
<p>Please click the button below to verify your email address.</p>
<p style="text-align: center; margin: 30px 0;">
    <a href="{{ url }}" style="display: inline-block; background-color: #4F46E5; color: #ffffff; padding: 12px 32px; border-radius: 6px; text-decoration: none; font-weight: 600;">
        Verify Email Address
    </a>
</p>
<p>If you did not create an account, no further action is required.</p>
<p>Thanks,<br>{{ config.app.name }}</p>
HTML],
                'token_schema' => [
                    ['token' => 'user.name', 'description' => 'User name', 'example' => 'John Doe'],
                    ['token' => 'url', 'description' => 'Verification URL', 'example' => 'https://example.com/verify/...'],
                    ['token' => 'config.app.name', 'description' => 'Application name', 'example' => 'MyApp'],
                ],
            ],
            [
                'key' => 'user-password-reset',
                'name' => [$locale => 'Password Reset Request'],
                'category' => TemplateCategory::System,
                'subject' => [$locale => 'Reset your password'],
                'preheader' => [$locale => 'You requested a password reset.'],
                'body' => [$locale => <<<'HTML'
<h2>Reset your password</h2>
<p>Hi {{ user.name }},</p>
<p>We received a request to reset your password. Click the button below to choose a new one.</p>
<p style="text-align: center; margin: 30px 0;">
    <a href="{{ url }}" style="display: inline-block; background-color: #4F46E5; color: #ffffff; padding: 12px 32px; border-radius: 6px; text-decoration: none; font-weight: 600;">
        Reset Password
    </a>
</p>
<p>This link will expire in 60 minutes.</p>
<p>If you didn't request this, you can safely ignore this email.</p>
<p>Thanks,<br>{{ config.app.name }}</p>
HTML],
                'token_schema' => [
                    ['token' => 'user.name', 'description' => 'User name', 'example' => 'John Doe'],
                    ['token' => 'url', 'description' => 'Password reset URL', 'example' => 'https://example.com/reset/...'],
                    ['token' => 'config.app.name', 'description' => 'Application name', 'example' => 'MyApp'],
                ],
            ],
            [
                'key' => 'user-password-changed',
                'name' => [$locale => 'Password Changed Confirmation'],
                'category' => TemplateCategory::System,
                'subject' => [$locale => 'Your password has been changed'],
                'preheader' => [$locale => 'Your account password was updated.'],
                'body' => [$locale => <<<'HTML'
<h2>Password changed</h2>
<p>Hi {{ user.name }},</p>
<p>Your password for <strong>{{ config.app.name }}</strong> was successfully changed.</p>
<p>If you did not make this change, please contact us immediately.</p>
<p>Thanks,<br>{{ config.app.name }}</p>
HTML],
                'token_schema' => [
                    ['token' => 'user.name', 'description' => 'User name', 'example' => 'John Doe'],
                    ['token' => 'config.app.name', 'description' => 'Application name', 'example' => 'MyApp'],
                ],
            ],
            [
                'key' => 'general-notification',
                'name' => [$locale => 'General Notification'],
                'category' => TemplateCategory::Notification,
                'subject' => [$locale => '{{ subject | "Notification from " }}{{ config.app.name }}'],
                'preheader' => [$locale => ''],
                'body' => [$locale => <<<'HTML'
<p>Hi {{ user.name | "there" }},</p>
<p>{{ message }}</p>
<p>Thanks,<br>{{ config.app.name }}</p>
HTML],
                'token_schema' => [
                    ['token' => 'user.name', 'description' => 'Recipient name (optional)', 'example' => 'John'],
                    ['token' => 'subject', 'description' => 'Email subject (optional)', 'example' => 'Important Update'],
                    ['token' => 'message', 'description' => 'The notification message body', 'example' => 'Your report is ready.'],
                    ['token' => 'config.app.name', 'description' => 'Application name', 'example' => 'MyApp'],
                ],
            ],
        ];

        foreach ($templates as $data) {
            EmailTemplate::firstOrCreate(
                ['key' => $data['key']],
                array_merge($data, [
                    'email_theme_id' => $theme?->id,
                    'is_active' => true,
                ])
            );
        }
    }
}
