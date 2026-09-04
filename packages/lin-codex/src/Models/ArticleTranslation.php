<?php

declare(strict_types=1);

namespace FinityLabs\LinCodex\Models;

use FinityLabs\LinCodex\Database\Factories\ArticleTranslationFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Carbon;

/**
 * One locale's title and body for an article.
 *
 * @property int $id
 * @property int $article_id
 * @property string $locale
 * @property string $title
 * @property string|null $excerpt
 * @property string $body
 * @property string|null $search_text
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 * @property-read Article $article
 */
class ArticleTranslation extends Model
{
    /** @use HasFactory<ArticleTranslationFactory> */
    use HasFactory;

    protected $fillable = [
        'article_id',
        'locale',
        'title',
        'excerpt',
        'body',
        'search_text',
    ];

    /*
    |--------------------------------------------------------------------------
    | Relationships
    |--------------------------------------------------------------------------
    */

    /**
     * @return BelongsTo<Article, $this>
     */
    public function article(): BelongsTo
    {
        return $this->belongsTo(Article::class, 'article_id');
    }

    /*
    |--------------------------------------------------------------------------
    | Factory
    |--------------------------------------------------------------------------
    */

    protected static function newFactory(): ArticleTranslationFactory
    {
        return ArticleTranslationFactory::new();
    }

    /*
    |--------------------------------------------------------------------------
    | Table
    |--------------------------------------------------------------------------
    */

    public function getTable(): string
    {
        return config('lin-codex.table_names.article_translations', 'codex_article_translations');
    }
}
