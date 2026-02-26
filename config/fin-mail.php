<?php

declare(strict_types=1);

return [

    /*
    |--------------------------------------------------------------------------
    | Table Names
    |--------------------------------------------------------------------------
    |
    | Customize the database table names used by the plugin.
    |
    */
    'table_names' => [
        'templates' => 'email_templates',
        'versions' => 'email_template_versions',
        'themes' => 'email_themes',
        'sent' => 'sent_emails',
    ],

    /*
    |--------------------------------------------------------------------------
    | Editor
    |--------------------------------------------------------------------------
    |
    | The WYSIWYG editor used for template body editing.
    |
    | 'default' uses Filament's built-in RichEditor (zero dependencies).
    | You can swap to any editor by providing a class implementing EditorContract:
    |   \FinityLabs\FinMail\Editors\TiptapEditor::class
    |   \FinityLabs\FinMail\Editors\TinyMceEditor::class
    |   \App\Editors\MyCustomEditor::class
    |
    */
    'editor' => 'default',

    /*
    |--------------------------------------------------------------------------
    | Queue Settings
    |--------------------------------------------------------------------------
    */
    'queue' => [
        'enabled' => true,
        'connection' => null,
        'queue' => 'emails',
    ],

    /*
    |--------------------------------------------------------------------------
    | Template Versioning
    |--------------------------------------------------------------------------
    */
    'versioning' => [
        'enabled' => true,
        'max_versions' => 50,
    ],

    /*
    |--------------------------------------------------------------------------
    | Auth Email Overrides
    |--------------------------------------------------------------------------
    |
    | When enabled, the plugin will automatically override Laravel's default
    | auth emails (verification, password reset, welcome) without manual setup.
    |
    */
    'auth_emails' => [
        'override_verification' => false,
        'override_password_reset' => false,
        'override_welcome' => false,
    ],

    /*
    |--------------------------------------------------------------------------
    | Attachments Disk
    |--------------------------------------------------------------------------
    */
    'attachments_disk' => 'local',

    /*
    |--------------------------------------------------------------------------
    | Token Replacement
    |--------------------------------------------------------------------------
    |
    | Token format: {{ model.attribute }}
    | Config tokens: {{ config.app.name }}
    | Conditional:   {% if user.is_premium %} ... {% endif %}
    | Fallback:      {{ user.name | 'Valued Customer' }}
    |
    */
    'tokens' => [
        'allowed_config_keys' => [
            'app.name',
            'app.url',
        ],

        'open' => '{{',
        'close' => '}}',
    ],

    /*
    |--------------------------------------------------------------------------
    | Navigation
    |--------------------------------------------------------------------------
    */
    'navigation' => [
        'enabled' => true,
        'group' => 'Email',
        'icon' => 'heroicon-o-envelope',
        'sort' => 50,
    ],

];
