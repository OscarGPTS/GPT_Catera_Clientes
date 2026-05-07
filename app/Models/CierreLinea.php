<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class CierreLinea extends Model
{
    protected $table = 'cierres_lineas';

    protected $fillable = ['seccion_id', 'proyecto_id', 'monto', 'porcentaje_aplicado', 'observaciones'];

    protected $casts = [
        'monto' => 'decimal:2',
        'porcentaje_aplicado' => 'decimal:4',
    ];

    public function seccion(): BelongsTo
    {
        return $this->belongsTo(CierreSeccion::class, 'seccion_id');
    }

    public function proyecto(): BelongsTo
    {
        return $this->belongsTo(Proyecto::class);
    }
}
