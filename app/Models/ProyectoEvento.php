<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ProyectoEvento extends Model
{
    protected $table = 'proyecto_eventos';

    protected $fillable = ['proyecto_id', 'user_id', 'tipo', 'estado_anterior', 'estado_nuevo', 'payload', 'comentario'];

    protected $casts = ['payload' => 'array'];

    public function proyecto(): BelongsTo
    {
        return $this->belongsTo(Proyecto::class);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
