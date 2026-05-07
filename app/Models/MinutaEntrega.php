<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class MinutaEntrega extends Model
{
    protected $table = 'minutas_entrega';

    protected $fillable = [
        'proyecto_id', 'fecha_reunion', 'hora_inicio', 'hora_fin',
        'modalidad', 'orden_del_dia', 'acuerdos', 'pdf_path',
        'firmado_at', 'status',
    ];

    protected $casts = [
        'fecha_reunion' => 'date',
        'orden_del_dia' => 'array',
        'acuerdos' => 'array',
        'firmado_at' => 'datetime',
    ];

    public function proyecto(): BelongsTo
    {
        return $this->belongsTo(Proyecto::class);
    }

    public function participantes(): HasMany
    {
        return $this->hasMany(MinutaEntregaParticipante::class, 'minuta_id');
    }
}
