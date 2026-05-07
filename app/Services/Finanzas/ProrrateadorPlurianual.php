<?php

namespace App\Services\Finanzas;

use App\Models\Proyecto;
use Carbon\CarbonImmutable;

/**
 * D8. Prorratea ingresos / costos de proyectos plurianuales por:
 *   - dias_naturales: días del periodo / días totales del proyecto.
 *   - hitos: TODO M12.2 — definir tabla de hitos por proyecto.
 */
class ProrrateadorPlurianual
{
    public function porcentajeEnPeriodo(Proyecto $proyecto, CarbonImmutable $desde, CarbonImmutable $hasta): float
    {
        if (! $proyecto->fecha_inicio_planeada || ! $proyecto->fecha_fin_planeada) {
            return 0.0;
        }

        $inicio = CarbonImmutable::parse($proyecto->fecha_inicio_planeada);
        $fin = CarbonImmutable::parse($proyecto->fecha_fin_planeada);

        if ($fin->lessThan($inicio) || $hasta->lessThan($desde)) {
            return 0.0;
        }

        $diasTotales = $inicio->diffInDays($fin) + 1;
        if ($diasTotales <= 0) {
            return 0.0;
        }

        $solapamientoIni = $desde->greaterThan($inicio) ? $desde : $inicio;
        $solapamientoFin = $hasta->lessThan($fin) ? $hasta : $fin;

        if ($solapamientoFin->lessThan($solapamientoIni)) {
            return 0.0;
        }

        $diasSolapados = $solapamientoIni->diffInDays($solapamientoFin) + 1;

        return min(1.0, max(0.0, $diasSolapados / $diasTotales));
    }
}
