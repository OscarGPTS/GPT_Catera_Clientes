<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ChatLectura extends Model
{
    protected $table = 'chat_lecturas';

    protected $fillable = ['canal_id', 'user_id', 'ultimo_mensaje_leido_id'];

    public function canal(): BelongsTo
    {
        return $this->belongsTo(ChatCanal::class, 'canal_id');
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
