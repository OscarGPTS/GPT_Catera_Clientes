<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class ChatMensaje extends Model
{
    protected $table = 'chat_mensajes';

    protected $fillable = ['canal_id', 'user_id', 'parent_message_id', 'contenido', 'edited_at', 'attachments'];

    protected $casts = [
        'edited_at' => 'datetime',
        'attachments' => 'array',
    ];

    public function canal(): BelongsTo
    {
        return $this->belongsTo(ChatCanal::class, 'canal_id');
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function parent(): BelongsTo
    {
        return $this->belongsTo(self::class, 'parent_message_id');
    }

    public function replies(): HasMany
    {
        return $this->hasMany(self::class, 'parent_message_id');
    }

    public function menciones(): HasMany
    {
        return $this->hasMany(ChatMencion::class, 'mensaje_id');
    }
}
