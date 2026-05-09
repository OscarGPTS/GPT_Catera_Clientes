<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class FinanzasAudit extends Model
{
    protected $table = 'finanzas_audit';

    protected $fillable = [
        'user_id', 'action', 'subject_type', 'subject_id',
        'payload', 'ip', 'user_agent',
    ];

    protected $casts = [
        'payload' => 'array',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
