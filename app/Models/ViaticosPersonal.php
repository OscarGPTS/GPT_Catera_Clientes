<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ViaticosPersonal extends Model
{
    protected $table = 'viaticos_personal';

    protected $fillable = ['solicitud_id', 'user_id', 'dias'];

    public function solicitud(): BelongsTo
    {
        return $this->belongsTo(SolicitudViaticos::class, 'solicitud_id');
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
