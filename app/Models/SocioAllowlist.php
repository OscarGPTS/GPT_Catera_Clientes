<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class SocioAllowlist extends Model
{
    protected $table = 'socios_allowlist';

    protected $fillable = ['email', 'notes', 'added_by', 'added_at'];

    protected $casts = ['added_at' => 'datetime'];

    public function addedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'added_by');
    }
}
