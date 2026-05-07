<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Sublinea extends Model
{
    protected $fillable = ['codigo', 'nombre', 'descripcion'];

    public function proyectos(): HasMany
    {
        return $this->hasMany(Proyecto::class);
    }
}
