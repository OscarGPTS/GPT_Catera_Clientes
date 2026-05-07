<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class PostMortem extends Model
{
    protected $table = 'post_mortem';

    protected $fillable = [
        'proyecto_id', 'fecha_sesion', 'participantes',
        'lecciones_aprendidas',
        'desviaciones_costo', 'desviaciones_tiempo', 'desviaciones_calidad',
        'presupuesto_planeado', 'presupuesto_real',
        'recomendaciones_mejora', 'pdf_path',
    ];

    protected $casts = [
        'fecha_sesion' => 'date',
        'participantes' => 'array',
        'recomendaciones_mejora' => 'array',
        'desviaciones_costo' => 'decimal:4',
        'desviaciones_tiempo' => 'decimal:4',
        'desviaciones_calidad' => 'decimal:4',
        'presupuesto_planeado' => 'decimal:2',
        'presupuesto_real' => 'decimal:2',
    ];

    public function proyecto(): BelongsTo
    {
        return $this->belongsTo(Proyecto::class);
    }
}
