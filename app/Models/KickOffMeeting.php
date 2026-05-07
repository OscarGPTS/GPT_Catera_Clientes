<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class KickOffMeeting extends Model
{
    protected $fillable = [
        'proyecto_id', 'tipo', 'fecha', 'participantes', 'agenda', 'minuta',
        'minuta_pdf_path', 'cronograma_attached_id',
    ];

    protected $casts = [
        'fecha' => 'datetime',
        'participantes' => 'array',
    ];

    public function proyecto(): BelongsTo
    {
        return $this->belongsTo(Proyecto::class);
    }
}
