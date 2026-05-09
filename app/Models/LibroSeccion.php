<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class LibroSeccion extends Model
{
    protected $table = 'libro_secciones';

    protected $fillable = [
        'libro_id', 'codigo', 'nombre', 'descripcion',
        'porcentaje_avance', 'estado', 'responsable_id', 'observaciones',
    ];

    protected $casts = ['porcentaje_avance' => 'decimal:2'];

    public function libro(): BelongsTo
    {
        return $this->belongsTo(LibroProyecto::class, 'libro_id');
    }

    public function responsable(): BelongsTo
    {
        return $this->belongsTo(User::class, 'responsable_id');
    }

    public function checklist(): HasMany
    {
        return $this->hasMany(LibroSeccionChecklist::class, 'seccion_id');
    }

    public function documentos(): HasMany
    {
        return $this->hasMany(LibroDocumento::class, 'seccion_id');
    }

    public function recalcularAvance(): void
    {
        $items = $this->checklist()->get();

        if ($items->isEmpty()) {
            $this->porcentaje_avance = 0;
            $this->estado = 'pendiente';
        } else {
            $completados = $items->where('completado', true)->count();
            $this->porcentaje_avance = round(($completados / $items->count()) * 100, 2);

            $this->estado = match (true) {
                (float) $this->porcentaje_avance >= 100 => 'completo',
                (float) $this->porcentaje_avance > 0 => 'en_proceso',
                default => 'pendiente',
            };
        }

        $this->save();

        $this->libro?->recalcularAvance();
    }
}
