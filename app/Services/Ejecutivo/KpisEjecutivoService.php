<?php

namespace App\Services\Ejecutivo;

use App\Models\Proyecto;
use App\Models\SystemSetting;

/**
 * Sección 13.1 del plan: KPIs del dashboard ejecutivo.
 */
class KpisEjecutivoService
{
    public function snapshot(int $año): array
    {
        $proyectos = Proyecto::query()->where('año', $año)->get();

        $pipeline = $proyectos->whereIn('estado', ['cotizando', 'cotizado', 'presentado', 'adjudicado_pendiente']);
        $adjudicados = $proyectos->whereIn('estado', ['adjudicado_firmado', 'en_ejecucion', 'en_cierre', 'cerrado']);
        $presentados = $proyectos->whereNotIn('estado', ['en_revision', 'cancelado']);

        $hitRateConteo = $presentados->count() > 0
            ? round($adjudicados->count() / $presentados->count(), 4)
            : 0;

        $montoOfertado = (float) $presentados->sum('monto_preliminar');
        $montoAdjudicado = (float) $adjudicados->sum('monto_preliminar');
        $hitRateMonto = $montoOfertado > 0 ? round($montoAdjudicado / $montoOfertado, 4) : 0;

        $concentracion = $pipeline->groupBy(fn ($p) => $p->cliente?->alias_3letras ?? '—')
            ->map(fn ($g) => round($g->sum('monto_preliminar'), 2))
            ->sortDesc();

        $totalPipeline = (float) $pipeline->sum('monto_preliminar');
        $sedenaShare = $totalPipeline > 0
            ? round((float) ($concentracion->get('SDN') ?? 0) / $totalPipeline, 4)
            : 0;

        $umbral = (int) (SystemSetting::get('concentracion_cliente_alerta_umbral') ?? 50) / 100;

        $alertasConcentracion = $concentracion
            ->filter(fn ($monto) => $totalPipeline > 0 && ($monto / $totalPipeline) >= $umbral)
            ->map(fn ($monto, $alias) => [
                'cliente' => $alias,
                'monto' => $monto,
                'porcentaje' => $totalPipeline > 0 ? round($monto / $totalPipeline, 4) : 0,
            ])
            ->values();

        return [
            'pipeline_monto' => $totalPipeline,
            'pipeline_conteo' => $pipeline->count(),
            'adjudicado_monto' => $montoAdjudicado,
            'adjudicado_conteo' => $adjudicados->count(),
            'hit_rate_conteo' => $hitRateConteo,
            'hit_rate_monto' => $hitRateMonto,
            'concentracion_clientes' => $concentracion->toArray(),
            'sedena_share' => $sedenaShare,
            'alertas_concentracion' => $alertasConcentracion,
            'total_proyectos_año' => $proyectos->count(),
            'umbral_alerta' => $umbral,
        ];
    }
}
