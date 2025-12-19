<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Fingerprint extends Model
{
    protected $fillable = [
        'user_id',
        'fingerprint_hash',
        'ip_address',
        'user_agent',
        'device_data',
    ];

    protected $casts = [
        'device_data' => 'array',
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }
}
