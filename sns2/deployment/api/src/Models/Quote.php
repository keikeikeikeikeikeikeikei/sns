<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Quote extends Model
{
    public $timestamps = false;

    protected $fillable = [
        'source_post_id',
        'quoting_post_id',
    ];

    protected $casts = [
        'created_at' => 'datetime',
    ];

    protected static function boot(): void
    {
        parent::boot();

        static::creating(function ($quote) {
            $quote->created_at = now();
        });
    }

    public function sourcePost(): BelongsTo
    {
        return $this->belongsTo(Post::class, 'source_post_id');
    }

    public function quotingPost(): BelongsTo
    {
        return $this->belongsTo(Post::class, 'quoting_post_id');
    }
}
