<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class MinutaEntregaParticipante extends Model
{
    protected $table = 'minuta_entrega_participantes';

    protected $fillable = ['minuta_id', 'user_id', 'rol_en_minuta', 'firma_pendiente', 'firmado_at'];

    protected $casts = [
        'firma_pendiente' => 'boolean',
        'firmado_at' => 'datetime',
    ];

    public function minuta(): BelongsTo
    {
        return $this->belongsTo(MinutaEntrega::class, 'minuta_id');
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
