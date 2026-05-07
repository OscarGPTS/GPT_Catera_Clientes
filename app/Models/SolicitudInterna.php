<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class SolicitudInterna extends Model
{
    protected $table = 'solicitudes_internas';

    protected $fillable = [
        'tipo', 'proyecto_id', 'cp_numero', 'codigo_formato',
        'estado', 'solicitante_id', 'asignado_id',
        'fecha_solicitud', 'fecha_respuesta_requerida', 'fecha_respuesta_real',
        'descripcion', 'respuesta',
    ];

    protected $casts = [
        'fecha_solicitud' => 'date',
        'fecha_respuesta_requerida' => 'date',
        'fecha_respuesta_real' => 'date',
    ];

    public function proyecto(): BelongsTo
    {
        return $this->belongsTo(Proyecto::class);
    }

    public function solicitante(): BelongsTo
    {
        return $this->belongsTo(User::class, 'solicitante_id');
    }

    public function asignado(): BelongsTo
    {
        return $this->belongsTo(User::class, 'asignado_id');
    }

    public function items(): HasMany
    {
        return $this->hasMany(SolicitudInternaItem::class, 'solicitud_id');
    }
}
