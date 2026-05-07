<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class SolicitudViaticos extends Model
{
    protected $table = 'solicitudes_viaticos';

    protected $fillable = [
        'proyecto_id', 'periodo_inicio', 'periodo_fin', 'justificacion',
        'status', 'solicitante_id', 'aprobador_serv_grales_id', 'aprobador_direccion_id',
        'aprobado_at', 'pdf_path',
    ];

    protected $casts = [
        'periodo_inicio' => 'date',
        'periodo_fin' => 'date',
        'aprobado_at' => 'datetime',
    ];

    public function proyecto(): BelongsTo
    {
        return $this->belongsTo(Proyecto::class);
    }

    public function personal(): HasMany
    {
        return $this->hasMany(ViaticosPersonal::class, 'solicitud_id');
    }

    public function partidas(): HasMany
    {
        return $this->hasMany(ViaticosPartida::class, 'solicitud_id');
    }
}
