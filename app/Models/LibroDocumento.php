<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class LibroDocumento extends Model
{
    protected $fillable = [
        'seccion_id', 'nombre', 'archivo_path', 'version',
        'mime_type', 'tamaño', 'subido_por_id', 'subido_at',
    ];

    protected $casts = ['subido_at' => 'datetime'];

    public function seccion(): BelongsTo
    {
        return $this->belongsTo(LibroSeccion::class, 'seccion_id');
    }

    public function subidoPor(): BelongsTo
    {
        return $this->belongsTo(User::class, 'subido_por_id');
    }
}
