<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ReporteSemanal extends Model
{
    protected $fillable = [
        'proyecto_id', 'semana_inicio', 'semana_fin',
        'contenido_html', 'generado_at', 'enviado_at', 'recipients', 'pdf_path',
    ];

    protected $casts = [
        'semana_inicio' => 'date',
        'semana_fin' => 'date',
        'generado_at' => 'datetime',
        'enviado_at' => 'datetime',
        'recipients' => 'array',
    ];

    public function proyecto(): BelongsTo
    {
        return $this->belongsTo(Proyecto::class);
    }
}
