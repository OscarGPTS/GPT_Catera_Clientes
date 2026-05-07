<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class LibroSeccionChecklist extends Model
{
    protected $table = 'libro_seccion_checklist';

    protected $fillable = [
        'seccion_id', 'item_descripcion', 'completado',
        'evidencia_documento_id', 'completado_por_id', 'completado_at',
    ];

    protected $casts = [
        'completado' => 'boolean',
        'completado_at' => 'datetime',
    ];

    public function seccion(): BelongsTo
    {
        return $this->belongsTo(LibroSeccion::class, 'seccion_id');
    }
}
