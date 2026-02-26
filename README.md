# FinMail

A powerful email template manager and composer for Filament. Build, manage, and send emails directly from your admin panel.

## Features

- **Email Composer** — Send emails from any resource using templates as starting points, with full editing of subject, body, recipients, and attachments
- **Dynamic Templates** — No need for separate Mailable classes per template. One universal `TemplateMail` handles everything
- **Token Replacement** — `{{ user.name }}`, `{{ config.app.name }}`, conditionals `{% if user.is_premium %}`, and fallbacks `{{ user.name | 'Customer' }}`
- **Template Versioning** — Automatic version history with compare and restore
- **Email Logging** — Every sent email is logged with status tracking, rendered body storage, and polymorphic model association
- **Translatable** — Templates support multiple languages via `spatie/laravel-translatable`, all locales stored in a single record
- **Theme System** — Create color themes and apply them to templates
- **Swappable Editor** — Ships with Tiptap support, but swap in TinyMCE or any editor via the `EditorContract`
- **Categories & Tags** — Organize templates as they grow
- **Reusable Actions** — `SendEmailTableAction` and `SentEmailsRelationManager` drop into any Filament resource
- **Preview & Test Send** — Preview templates inline and send test emails from the admin
- **Admin Settings** — Manage sender defaults, branding, logging, and attachment rules from the UI

## Requirements

- PHP 8.2+
- Laravel 11 or 12
- Filament 4 or 5

## Installation

```bash
composer require finity-labs/fin-mail
```

```bash
php artisan fin-mail:install
```

The install command will:
- Publish config and migrations
- Optionally run migrations and seed default templates
- Optionally register the plugin in your Filament panel
- Optionally configure scheduled cleanup of old sent emails

### Register the plugin

```php
use FinityLabs\FinMail\FinMailPlugin;

public function panel(Panel $panel): Panel
{
    return $panel
        ->plugins([
            FinMailPlugin::make(),
        ]);
}
```

#### Plugin options

```php
FinMailPlugin::make()
    ->enableNavigation(fn () => auth()->user()?->isAdmin())
    ->enableSentEmails()    // Show the Sent Emails resource (default: true)
    ->enableThemes()        // Show the Themes resource (default: true)
```

## Usage

### Sending emails programmatically

```php
use FinityLabs\FinMail\Mail\TemplateMail;

// Simple
Mail::to($user->email)->send(
    TemplateMail::make('welcome-email')
        ->models(['user' => $user])
);

// With locale, attachments, and overrides
Mail::to($customer->email)->send(
    TemplateMail::make('invoice-sent', locale: 'hu')
        ->models(['customer' => $customer, 'invoice' => $invoice])
        ->attachFile($invoice->getPdfPath(), "Invoice-{$invoice->number}.pdf")
);
```

`TemplateMail` is automatically queued. Configure the queue connection and name in `config/fin-mail.php`.

### Adding "Send Email" to any resource

```php
use FinityLabs\FinMail\Actions\SendEmailTableAction;

// In your table actions
SendEmailTableAction::make()
    ->template('invoice-sent')
    ->recipient(fn (Invoice $record) => $record->customer->email)
    ->models(fn (Invoice $record) => [
        'invoice'  => $record,
        'customer' => $record->customer,
    ])
    ->attachments(fn (Invoice $record) => [
        ['path' => $record->getPdfPath(), 'name' => "Invoice-{$record->number}.pdf"],
    ])
    ->onSent(fn (Invoice $record) => $record->update(['emailed_at' => now()]))
```

A `SendEmailAction` (page header action) is also available with the same API.

### Showing sent emails on any resource

Add the `HasEmailTemplates` trait to your model:

```php
use FinityLabs\FinMail\Traits\HasEmailTemplates;

class Invoice extends Model
{
    use HasEmailTemplates;
}
```

Then add the relation manager to your resource:

```php
use FinityLabs\FinMail\Resources\RelationManagers\SentEmailsRelationManager;

public static function getRelations(): array
{
    return [
        SentEmailsRelationManager::class,
    ];
}
```

The trait provides helpers on your model:

```php
$invoice->sentEmails;                         // All sent emails
$invoice->latestSentEmail();                  // Most recent
$invoice->hasBeenEmailed('invoice-sent');     // Check if a specific template was sent
$invoice->sentEmailsCount();                  // Count
```

### Token syntax

| Syntax | Example | Description |
|--------|---------|-------------|
| `{{ model.attr }}` | `{{ user.name }}` | Model attribute |
| `{{ model.rel.attr }}` | `{{ order.customer.name }}` | Nested relation |
| `{{ config.key }}` | `{{ config.app.name }}` | Config value |
| `{{ token \| 'fallback' }}` | `{{ user.name \| 'Customer' }}` | With fallback |
| `{% if token %}...{% endif %}` | `{% if user.is_premium %}...{% endif %}` | Conditional |
| `{% if token %}...{% else %}...{% endif %}` | | If/else |

## Configuration

Publish the config:

```bash
php artisan vendor:publish --tag=fin-mail-config
```

Other publish tags:

| Tag | Description |
|-----|-------------|
| `fin-mail-config` | Configuration file |
| `fin-mail-migrations` | Database migrations |
| `fin-mail-settings-migrations` | Spatie Settings migrations |
| `fin-mail-views` | Email template views |

## Testing

```bash
composer test
```

## License

MIT
