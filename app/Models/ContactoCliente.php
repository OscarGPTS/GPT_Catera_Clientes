<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ContactoCliente extends Model
{
    protected $table = 'contactos_cliente';

    protected $fillable = ['cliente_id', 'nombre', 'puesto', 'email', 'telefono', 'principal'];

    protected $casts = ['principal' => 'boolean'];

    public function cliente(): BelongsTo
    {
        return $this->belongsTo(Cliente::class);
    }
}
