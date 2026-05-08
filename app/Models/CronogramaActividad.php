<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class CronogramaActividad extends Model
{
    protected $table = 'cronograma_actividades';

    protected $fillable = [
        'cronograma_id', 'codigo', 'nombre', 'parent_id',
        'fecha_inicio_planeada', 'fecha_fin_planeada',
        'fecha_inicio_real', 'fecha_fin_real',
        'porcentaje_avance', 'predecesoras',
    ];

    protected $casts = [
        'fecha_inicio_planeada' => 'date',
        'fecha_fin_planeada' => 'date',
        'fecha_inicio_real' => 'date',
        'fecha_fin_real' => 'date',
        'porcentaje_avance' => 'decimal:2',
        'predecesoras' => 'array',
    ];

    public function cronograma(): BelongsTo
    {
        return $this->belongsTo(Cronograma::class);
    }

    public function parent(): BelongsTo
    {
        return $this->belongsTo(self::class, 'parent_id');
    }
}
