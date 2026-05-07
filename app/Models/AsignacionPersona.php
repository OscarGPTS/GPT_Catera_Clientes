<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class AsignacionPersona extends Model
{
    protected $table = 'asignaciones_personas';

    protected $fillable = [
        'user_id', 'mes', 'año',
        'cp_asignados', 'cp_ejecutados', 'cp_remanentes', 'cp_residual_anterior',
        'dn_activos', 'dn_stand_by', 'dn_cerrados', 'dn_cancelados',
        'total_servicio', 'total_suministro',
        'gerencia_regional', 'generado_at',
    ];

    protected $casts = [
        'mes' => 'integer',
        'año' => 'integer',
        'generado_at' => 'datetime',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function totalProyectos(): int
    {
        return $this->cp_asignados + $this->dn_activos + $this->dn_stand_by;
    }

    public function colorCarga(): string
    {
        return match (true) {
            $this->totalProyectos() >= 10 => 'red',
            $this->totalProyectos() >= 8 => 'orange',
            $this->totalProyectos() >= 5 => 'amber',
            default => 'emerald',
        };
    }
}
