<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ViaticosPartida extends Model
{
    protected $table = 'viaticos_partidas';

    protected $fillable = ['solicitud_id', 'concepto', 'monto_estimado', 'monto_real', 'observaciones'];

    protected $casts = [
        'monto_estimado' => 'decimal:2',
        'monto_real' => 'decimal:2',
    ];

    public function solicitud(): BelongsTo
    {
        return $this->belongsTo(SolicitudViaticos::class, 'solicitud_id');
    }
}
