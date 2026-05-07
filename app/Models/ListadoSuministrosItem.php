<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ListadoSuministrosItem extends Model
{
    protected $table = 'listados_suministros_items';

    protected $fillable = [
        'listado_id', 'descripcion', 'cantidad', 'unidad',
        'fecha_requerida', 'status', 'porcentaje_avance', 'etapa',
    ];

    protected $casts = [
        'fecha_requerida' => 'date',
        'porcentaje_avance' => 'decimal:2',
    ];

    public function listado(): BelongsTo
    {
        return $this->belongsTo(ListadoSuministros::class, 'listado_id');
    }
}
