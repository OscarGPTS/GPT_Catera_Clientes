<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class MovimientoBancario extends Model
{
    protected $table = 'movimientos_bancarios';

    protected $fillable = [
        'estado_cuenta_id', 'fecha', 'descripcion', 'monto', 'tipo',
        'conciliado_con_proyecto_id', 'conciliado_con_factura', 'conciliado_at',
    ];

    protected $casts = [
        'fecha' => 'date',
        'monto' => 'decimal:2',
        'conciliado_at' => 'datetime',
    ];

    public function estadoCuenta(): BelongsTo
    {
        return $this->belongsTo(EstadoCuenta::class);
    }

    public function proyecto(): BelongsTo
    {
        return $this->belongsTo(Proyecto::class, 'conciliado_con_proyecto_id');
    }

    public function scopeConciliado($query, bool $value = true)
    {
        return $value
            ? $query->whereNotNull('conciliado_at')
            : $query->whereNull('conciliado_at');
    }
}
