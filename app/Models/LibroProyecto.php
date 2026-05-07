<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class LibroProyecto extends Model
{
    protected $table = 'libros_proyecto';

    protected $fillable = [
        'proyecto_id', 'fecha_apertura', 'fecha_cierre_estimado', 'fecha_cierre_real',
        'porcentaje_avance_global', 'bloqueado_para_cierre',
        'pdf_consolidado_path', 'pdf_consolidado_md5',
    ];

    protected $casts = [
        'fecha_apertura' => 'date',
        'fecha_cierre_estimado' => 'date',
        'fecha_cierre_real' => 'date',
        'porcentaje_avance_global' => 'decimal:2',
        'bloqueado_para_cierre' => 'boolean',
    ];

    public function proyecto(): BelongsTo
    {
        return $this->belongsTo(Proyecto::class);
    }

    public function secciones(): HasMany
    {
        return $this->hasMany(LibroSeccion::class, 'libro_id')->orderBy('codigo');
    }

    public function recalcularAvance(): void
    {
        $secciones = $this->secciones()->get();

        if ($secciones->isEmpty()) {
            $this->porcentaje_avance_global = 0;
        } else {
            $this->porcentaje_avance_global = round($secciones->avg('porcentaje_avance'), 2);
        }

        $this->save();
    }
}
