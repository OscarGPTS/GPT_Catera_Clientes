<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ChatMencion extends Model
{
    protected $table = 'chat_menciones';

    protected $fillable = ['mensaje_id', 'user_id', 'leido_at'];

    protected $casts = ['leido_at' => 'datetime'];

    public function mensaje(): BelongsTo
    {
        return $this->belongsTo(ChatMensaje::class, 'mensaje_id');
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
