<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class KpiSnapshot extends Model
{
    protected $fillable = ['fecha', 'kpi', 'valor', 'contexto'];

    protected $casts = [
        'fecha' => 'date',
        'valor' => 'decimal:4',
        'contexto' => 'array',
    ];
}
