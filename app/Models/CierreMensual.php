<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class CierreMensual extends Model
{
    protected $table = 'cierres_mensuales';

    protected $fillable = [
        'mes', 'año', 'tipo', 'fecha_corte', 'status',
        'generado_por_id', 'aprobado_por_id', 'aprobado_at',
        'observaciones', 'pdf_path',
    ];

    protected $casts = [
        'mes' => 'integer',
        'año' => 'integer',
        'fecha_corte' => 'date',
        'aprobado_at' => 'datetime',
    ];

    public function secciones(): HasMany
    {
        return $this->hasMany(CierreSeccion::class, 'cierre_id');
    }

    public function generadoPor(): BelongsTo
    {
        return $this->belongsTo(User::class, 'generado_por_id');
    }

    public function aprobadoPor(): BelongsTo
    {
        return $this->belongsTo(User::class, 'aprobado_por_id');
    }

    public function totalGeneral(): float
    {
        return (float) $this->secciones->sum('total');
    }

    public function periodoLabel(): string
    {
        return sprintf('%02d/%d', $this->mes, $this->año);
    }
}
