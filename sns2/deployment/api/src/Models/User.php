<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class User extends Model
{
    protected $fillable = [
        'username',
        'email',
        'password_hash',
        'display_name',
        'bio',
        'avatar_url',
    ];

    protected $hidden = [
        'password_hash',
    ];

    protected $casts = [
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];

    public function posts(): HasMany
    {
        return $this->hasMany(Post::class);
    }

    public function reactions(): HasMany
    {
        return $this->hasMany(Reaction::class);
    }
}
