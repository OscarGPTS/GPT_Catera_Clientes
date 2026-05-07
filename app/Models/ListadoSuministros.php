<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class ListadoSuministros extends Model
{
    protected $table = 'listados_suministros';

    protected $fillable = ['proyecto_id', 'porcentaje_avance_global'];

    public function proyecto(): BelongsTo
    {
        return $this->belongsTo(Proyecto::class);
    }

    public function items(): HasMany
    {
        return $this->hasMany(ListadoSuministrosItem::class, 'listado_id');
    }

    public function recalcularAvance(): void
    {
        $items = $this->items()->get();
        if ($items->isEmpty()) {
            $this->porcentaje_avance_global = 0;
        } else {
            $this->porcentaje_avance_global = round($items->avg('porcentaje_avance'), 2);
        }
        $this->save();
    }
}
