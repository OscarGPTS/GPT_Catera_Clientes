<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class AuthProvider extends Model
{
    protected $fillable = [
        'user_id',
        'provider',
        'provider_user_id',
        'password_hash',
        'is_primary',
        'linked_at',
        'last_used_at',
    ];

    protected $casts = [
        'is_primary' => 'boolean',
        'linked_at' => 'datetime',
        'last_used_at' => 'datetime',
    ];

    protected $hidden = ['password_hash'];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
