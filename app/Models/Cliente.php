<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class Cliente extends Model
{
    use SoftDeletes;

    protected $fillable = ['razon_social', 'alias_3letras', 'rfc', 'sector', 'segmento', 'activo'];

    protected $casts = ['activo' => 'boolean'];

    public function contactos(): HasMany
    {
        return $this->hasMany(ContactoCliente::class);
    }

    public function proyectos(): HasMany
    {
        return $this->hasMany(Proyecto::class);
    }
}
