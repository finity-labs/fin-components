<?php

declare(strict_types=1);

namespace FinityLabs\FinModalTableSelect\Tests\Fixtures\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Post extends Model
{
    /** @var array<int, string> */
    protected $fillable = ['title', 'body', 'user_id'];

    /** @return BelongsTo<User, $this> */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
