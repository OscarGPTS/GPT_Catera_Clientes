<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class SolicitudInternaItem extends Model
{
    protected $table = 'solicitudes_internas_items';

    protected $fillable = ['solicitud_id', 'descripcion', 'cantidad', 'unidad', 'especificacion', 'observaciones'];

    public function solicitud(): BelongsTo
    {
        return $this->belongsTo(SolicitudInterna::class, 'solicitud_id');
    }
}
