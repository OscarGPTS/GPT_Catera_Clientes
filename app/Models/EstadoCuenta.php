<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class EstadoCuenta extends Model
{
    protected $table = 'estados_cuenta';

    protected $fillable = ['cuenta_id', 'mes', 'año', 'archivo_origen_path', 'parseado_at', 'total_movimientos'];

    protected $casts = [
        'parseado_at' => 'datetime',
    ];

    public function cuenta(): BelongsTo
    {
        return $this->belongsTo(CuentaBancaria::class, 'cuenta_id');
    }

    public function movimientos(): HasMany
    {
        return $this->hasMany(MovimientoBancario::class);
    }
}
