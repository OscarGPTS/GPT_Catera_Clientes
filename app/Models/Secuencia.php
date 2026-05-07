<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Secuencia extends Model
{
    protected $table = 'secuencias';

    protected $fillable = ['tipo', 'año', 'ultimo_consecutivo'];

    protected $casts = [
        'año' => 'integer',
        'ultimo_consecutivo' => 'integer',
    ];
}
