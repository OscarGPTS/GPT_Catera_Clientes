<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class CierreSeccion extends Model
{
    protected $table = 'cierres_secciones';

    protected $fillable = ['cierre_id', 'codigo', 'total'];

    protected $casts = ['total' => 'decimal:2'];

    public function cierre(): BelongsTo
    {
        return $this->belongsTo(CierreMensual::class, 'cierre_id');
    }

    public function lineas(): HasMany
    {
        return $this->hasMany(CierreLinea::class, 'seccion_id');
    }
}
