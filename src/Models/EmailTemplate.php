<?php

declare(strict_types=1);

namespace FinityLabs\FinMail\Models;

use FinityLabs\FinMail\Helpers\TokenReplacer;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Spatie\Translatable\HasTranslations;

class EmailTemplate extends Model
{
    use HasTranslations;
    use SoftDeletes;

    /** @var array<int, string> */
    public array $translatable = ['name', 'subject', 'preheader', 'body'];

    protected $fillable = [
        'key',
        'name',
        'category',
        'tags',
        'subject',
        'preheader',
        'body',
        'view_path',
        'from',
        'email_theme_id',
        'token_schema',
        'is_active',
        'is_locked',
    ];

    /*
    |--------------------------------------------------------------------------
    | Casts
    |--------------------------------------------------------------------------
    */

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'from' => 'array',
            'tags' => 'array',
            'is_active' => 'boolean',
            'is_locked' => 'boolean',
            'token_schema' => 'array',
        ];
    }

    /*
    |--------------------------------------------------------------------------
    | Boot
    |--------------------------------------------------------------------------
    */

    protected static function booted(): void
    {
        static::deleting(function (EmailTemplate $template): bool {
            return ! $template->is_locked;
        });
    }

    /*
    |--------------------------------------------------------------------------
    | Relationships
    |--------------------------------------------------------------------------
    */

    public function sentEmails(): HasMany
    {
        return $this->hasMany(SentEmail::class);
    }

    public function theme(): BelongsTo
    {
        return $this->belongsTo(EmailTheme::class, 'email_theme_id');
    }

    public function versions(): HasMany
    {
        return $this->hasMany(EmailTemplateVersion::class)->orderByDesc('version');
    }

    /*
    |--------------------------------------------------------------------------
    | Scopes
    |--------------------------------------------------------------------------
    */

    public function scopeActive(Builder $query): Builder
    {
        return $query->where('is_active', true);
    }

    public function scopeByCategory(Builder $query, string $category): Builder
    {
        return $query->where('category', $category);
    }

    public function scopeByKey(Builder $query, string $key): Builder
    {
        return $query->where('key', $key);
    }

    public function scopeLocked(Builder $query): Builder
    {
        return $query->where('is_locked', true);
    }

    /*
    |--------------------------------------------------------------------------
    | Helpers
    |--------------------------------------------------------------------------
    */

    public function isDeletable(): bool
    {
        return ! $this->is_locked;
    }

    /**
     * Find a template by its key, optionally setting the locale for translation resolution.
     */
    public static function findByKey(string $key, ?string $locale = null): ?static
    {
        $template = static::active()->byKey($key)->first();

        if ($template && $locale) {
            $template->setLocale($locale);
        }

        return $template;
    }

    /**
     * Render the template body with token replacement.
     *
     * @param  array<string, mixed>  $models  Keyed by token prefix: ['user' => $user, 'invoice' => $invoice]
     *
     * @return array{subject: string, preheader: string, body: string}
     */
    public function render(array $models = [], ?string $locale = null): array
    {
        if ($locale) {
            $this->setLocale($locale);
        }

        $replacer = app(TokenReplacer::class);

        return [
            'subject' => $replacer->replace($this->subject, $models),
            'preheader' => $replacer->replace($this->preheader ?? '', $models),
            'body' => $replacer->replace($this->body, $models),
        ];
    }

    /**
     * Save a version snapshot of the current state (all translations).
     */
    public function saveVersion(?int $userId = null): EmailTemplateVersion
    {
        if (! config('fin-mail.versioning.enabled')) {
            return new EmailTemplateVersion;
        }

        $latestVersion = $this->versions()->max('version') ?? 0;

        $version = $this->versions()->create([
            'version' => $latestVersion + 1,
            'subject' => $this->getTranslations('subject'),
            'preheader' => $this->getTranslations('preheader'),
            'body' => $this->getTranslations('body'),
            'created_by' => $userId ?? auth()->id(),
        ]);

        // Cleanup old versions beyond max
        $max = config('fin-mail.versioning.max_versions', 50);
        $this->versions()
            ->orderByDesc('version')
            ->skip($max)
            ->take(PHP_INT_MAX)
            ->delete();

        return $version;
    }

    /**
     * Restore a specific version (all translations).
     */
    public function restoreVersion(int $versionNumber): bool
    {
        $version = $this->versions()->where('version', $versionNumber)->first();

        if (! $version) {
            return false;
        }

        $this->saveVersion(); // save current state before restoring

        $this->replaceTranslations('subject', $version->subject ?? []);
        $this->replaceTranslations('preheader', $version->preheader ?? []);
        $this->replaceTranslations('body', $version->body ?? []);
        $this->save();

        return true;
    }

    /*
    |--------------------------------------------------------------------------
    | Table
    |--------------------------------------------------------------------------
    */

    public function getTable(): string
    {
        return config('fin-mail.table_names.templates', 'email_templates');
    }
}
