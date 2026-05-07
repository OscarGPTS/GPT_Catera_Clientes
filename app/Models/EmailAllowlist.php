<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class EmailAllowlist extends Model
{
    protected $table = 'email_allowlist';

    protected $fillable = [
        'email',
        'allowed_providers',
        'role_default',
        'departamento_default',
        'notes',
    ];

    protected $casts = [
        'allowed_providers' => 'array',
    ];
}
