<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;

class RhRoleMapping extends Model
{
    protected $table = 'rh_role_mapping';

    protected $fillable = [
        'puesto_rh',
        'rol_sistema',
        'prioridad',
        'departamento_filter',
        'activo',
    ];

    protected $casts = [
        'prioridad' => 'integer',
        'activo' => 'boolean',
    ];

    public function scopeActive(Builder $query): Builder
    {
        return $query->where('activo', true);
    }

    public function scopeOrderByPrioridad(Builder $query): Builder
    {
        return $query->orderByDesc('prioridad');
    }
}
