<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Cotizacion extends Model
{
    protected $table = 'cotizaciones';

    protected $fillable = [
        'proyecto_id', 'version',
        'costo_directo', 'factor_indirectos', 'factor_admin', 'factor_utilidad',
        'precio_venta_calculado', 'precio_venta_final', 'margen_neto',
        'moneda', 'status', 'generado_por_id', 'fecha_emision',
        'observaciones', 'pdf_path',
    ];

    protected $casts = [
        'costo_directo' => 'decimal:2',
        'factor_indirectos' => 'decimal:4',
        'factor_admin' => 'decimal:4',
        'factor_utilidad' => 'decimal:4',
        'precio_venta_calculado' => 'decimal:2',
        'precio_venta_final' => 'decimal:2',
        'margen_neto' => 'decimal:4',
        'fecha_emision' => 'datetime',
    ];

    public function proyecto(): BelongsTo
    {
        return $this->belongsTo(Proyecto::class);
    }

    public function partidas(): HasMany
    {
        return $this->hasMany(CotizacionPartida::class)->orderBy('numero_partida');
    }

    public function generadoPor(): BelongsTo
    {
        return $this->belongsTo(User::class, 'generado_por_id');
    }
}
