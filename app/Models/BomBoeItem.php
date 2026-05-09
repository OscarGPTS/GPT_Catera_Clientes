<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class BomBoeItem extends Model
{
    protected $fillable = [
        'proyecto_id', 'tipo', 'descripcion', 'cantidad', 'unidad',
        'status', 'responsable_id', 'fecha_requerida', 'observaciones',
    ];

    protected $casts = [
        'fecha_requerida' => 'date',
        'cantidad' => 'decimal:4',
    ];

    public function proyecto(): BelongsTo
    {
        return $this->belongsTo(Proyecto::class);
    }

    public function responsable(): BelongsTo
    {
        return $this->belongsTo(User::class, 'responsable_id');
    }
}
