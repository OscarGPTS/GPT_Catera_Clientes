<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class CartaFiniquito extends Model
{
    protected $table = 'cartas_finiquito';

    protected $fillable = [
        'proyecto_id', 'fecha_emision',
        'personal_liberado', 'equipos_liberados', 'observaciones',
        'pdf_path', 'firmado_cliente_at', 'firmado_gpt_at',
    ];

    protected $casts = [
        'fecha_emision' => 'date',
        'personal_liberado' => 'array',
        'equipos_liberados' => 'array',
        'firmado_cliente_at' => 'datetime',
        'firmado_gpt_at' => 'datetime',
    ];

    public function proyecto(): BelongsTo
    {
        return $this->belongsTo(Proyecto::class);
    }
}
