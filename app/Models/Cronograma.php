<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Cronograma extends Model
{
    protected $fillable = [
        'proyecto_id', 'version', 'generado_por_id', 'archivo_origen_path',
        'fecha_inicio', 'fecha_fin',
    ];

    protected $casts = [
        'fecha_inicio' => 'date',
        'fecha_fin' => 'date',
    ];

    public function proyecto(): BelongsTo
    {
        return $this->belongsTo(Proyecto::class);
    }

    public function actividades(): HasMany
    {
        return $this->hasMany(CronogramaActividad::class)->orderBy('fecha_inicio_planeada');
    }
}
