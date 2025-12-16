<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Builder;

class Post extends Model
{
    public const TYPE_FEED = 'feed';
    public const TYPE_QA = 'qa';
    public const TYPE_BLOG = 'blog';

    public const QA_STATUS_OPEN = 'open';
    public const QA_STATUS_RESOLVED = 'resolved';

    protected $fillable = [
        'user_id',
        'type',
        'content_short',
        'content_long',
        'title',
        'qa_status',
        'best_answer_id',
        'parent_post_id',
    ];

    protected $casts = [
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];

    // Relationships

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function parentPost(): BelongsTo
    {
        return $this->belongsTo(Post::class, 'parent_post_id');
    }

    public function answers(): HasMany
    {
        return $this->hasMany(Post::class, 'parent_post_id');
    }

    public function bestAnswer(): BelongsTo
    {
        return $this->belongsTo(Post::class, 'best_answer_id');
    }

    public function reactions(): HasMany
    {
        return $this->hasMany(Reaction::class);
    }

    public function quotesAsSource(): HasMany
    {
        return $this->hasMany(Quote::class, 'source_post_id');
    }

    public function quotesAsQuoting(): HasMany
    {
        return $this->hasMany(Quote::class, 'quoting_post_id');
    }

    // Scopes

    public function scopeFeed(Builder $query): Builder
    {
        return $query->where('type', self::TYPE_FEED);
    }

    public function scopeQa(Builder $query): Builder
    {
        return $query->where('type', self::TYPE_QA)->whereNull('parent_post_id');
    }

    public function scopeBlog(Builder $query): Builder
    {
        return $query->where('type', self::TYPE_BLOG)->whereNull('parent_post_id');
    }

    public function scopeLatest(Builder $query): Builder
    {
        return $query->orderBy('created_at', 'desc');
    }

    // Accessors

    public function getReactionCountsAttribute(): array
    {
        return $this->reactions()
            ->selectRaw('emoji, COUNT(*) as count')
            ->groupBy('emoji')
            ->pluck('count', 'emoji')
            ->toArray();
    }
}
