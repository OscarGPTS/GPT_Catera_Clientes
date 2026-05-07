<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class BitacoraDiaria extends Model
{
    protected $table = 'bitacora_diaria';

    protected $fillable = [
        'proyecto_id', 'fecha', 'relacion_actividades',
        'personal_gpt', 'equipos_en_sitio', 'proveedores_subcontratistas',
        'vobo_cliente_nombre', 'vobo_cliente_organizacion', 'vobo_cliente_fecha',
        'vobo_cliente_firma_path', 'cargado_por_id', 'firmado_at',
    ];

    protected $casts = [
        'fecha' => 'date',
        'personal_gpt' => 'array',
        'equipos_en_sitio' => 'array',
        'proveedores_subcontratistas' => 'array',
        'vobo_cliente_fecha' => 'date',
        'firmado_at' => 'datetime',
    ];

    /**
     * D7. Detección naive de desviaciones por keywords.
     * En el reporte semanal o on-save, se evalúa para notificar al gerente.
     */
    public function tieneDesviacion(): bool
    {
        $keywords = ['retraso', 'no llegó', 'no llego', 'falla', 'incidente', 'paro', 'accidente'];
        $texto = strtolower($this->relacion_actividades ?? '');

        foreach ($keywords as $kw) {
            if (str_contains($texto, $kw)) {
                return true;
            }
        }

        return false;
    }

    public function proyecto(): BelongsTo
    {
        return $this->belongsTo(Proyecto::class);
    }
}
