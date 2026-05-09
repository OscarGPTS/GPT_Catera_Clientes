<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;

class ChatCanal extends Model
{
    protected $table = 'chat_canales';

    protected $fillable = ['tipo', 'contexto_id', 'nombre', 'descripcion', 'creado_por_id'];

    public function miembros(): BelongsToMany
    {
        return $this->belongsToMany(User::class, 'chat_canal_miembros', 'canal_id', 'user_id')
            ->withPivot('rol_en_canal', 'joined_at')
            ->withTimestamps();
    }

    public function mensajes(): HasMany
    {
        return $this->hasMany(ChatMensaje::class, 'canal_id')->latest();
    }

    public function lecturas(): HasMany
    {
        return $this->hasMany(ChatLectura::class, 'canal_id');
    }

    public function creadoPor(): BelongsTo
    {
        return $this->belongsTo(User::class, 'creado_por_id');
    }

    /**
     * Cuenta mensajes no leídos para el usuario dado.
     */
    public function mensajesNoLeidosPara(int $userId): int
    {
        $lectura = $this->lecturas()->where('user_id', $userId)->first();
        $ultimoLeido = $lectura?->ultimo_mensaje_leido_id ?? 0;

        return $this->mensajes()
            ->where('id', '>', $ultimoLeido)
            ->where('user_id', '!=', $userId)
            ->count();
    }
}
