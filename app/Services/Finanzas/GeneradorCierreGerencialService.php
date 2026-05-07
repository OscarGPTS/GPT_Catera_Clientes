<?php

namespace App\Services\Finanzas;

use App\Models\CierreLinea;
use App\Models\CierreMensual;
use App\Models\CierreSeccion;
use App\Models\MovimientoBancario;
use App\Models\Proyecto;
use Carbon\CarbonImmutable;
use Illuminate\Support\Facades\DB;

/**
 * D1. Genera cierre `gerencial_avance` con 3 secciones:
 *   1. sat_base — facturas/movimientos del mes.
 *   2. devengado — proyectos con OC firmada, prorrateados por días o hitos.
 *   3. pipeline_ponderado — proyectos no firmados × probabilidad de adjudicación.
 */
class GeneradorCierreGerencialService
{
    public function __construct(
        private readonly ProrrateadorPlurianual $prorrateador,
    ) {}

    public function generar(int $año, int $mes, ?int $userId = null): CierreMensual
    {
        return DB::transaction(function () use ($año, $mes, $userId) {
            $cierre = CierreMensual::updateOrCreate(
                ['mes' => $mes, 'año' => $año, 'tipo' => 'gerencial_avance'],
                [
                    'fecha_corte' => now(),
                    'status' => 'borrador',
                    'generado_por_id' => $userId,
                ],
            );

            $cierre->secciones()->delete();

            $desde = CarbonImmutable::create($año, $mes, 1);
            $hasta = $desde->endOfMonth();

            $satBase = $this->seccionSatBase($cierre, $desde, $hasta);
            $devengado = $this->seccionDevengado($cierre, $desde, $hasta);
            $pipeline = $this->seccionPipelinePonderado($cierre, $desde, $hasta);

            $satBase->update(['total' => $satBase->lineas()->sum('monto')]);
            $devengado->update(['total' => $devengado->lineas()->sum('monto')]);
            $pipeline->update(['total' => $pipeline->lineas()->sum('monto')]);

            return $cierre->fresh('secciones.lineas');
        });
    }

    public function generarSat(int $año, int $mes, ?int $userId = null): CierreMensual
    {
        return DB::transaction(function () use ($año, $mes, $userId) {
            $cierre = CierreMensual::updateOrCreate(
                ['mes' => $mes, 'año' => $año, 'tipo' => 'contable_sat'],
                [
                    'fecha_corte' => now(),
                    'status' => 'borrador',
                    'generado_por_id' => $userId,
                ],
            );

            $cierre->secciones()->delete();

            $desde = CarbonImmutable::create($año, $mes, 1);
            $hasta = $desde->endOfMonth();

            $sat = $this->seccionSatBase($cierre, $desde, $hasta);
            $sat->update(['total' => $sat->lineas()->sum('monto')]);

            return $cierre->fresh('secciones.lineas');
        });
    }

    private function seccionSatBase(CierreMensual $cierre, CarbonImmutable $desde, CarbonImmutable $hasta): CierreSeccion
    {
        $sat = CierreSeccion::create(['cierre_id' => $cierre->id, 'codigo' => 'sat_base', 'total' => 0]);

        $movs = MovimientoBancario::whereBetween('fecha', [$desde, $hasta])
            ->where('tipo', 'ingreso')
            ->whereNotNull('conciliado_con_factura')
            ->get();

        foreach ($movs as $m) {
            CierreLinea::create([
                'seccion_id' => $sat->id,
                'proyecto_id' => $m->conciliado_con_proyecto_id,
                'monto' => $m->monto,
                'porcentaje_aplicado' => 1.0,
                'observaciones' => $m->descripcion,
            ]);
        }

        return $sat;
    }

    private function seccionDevengado(CierreMensual $cierre, CarbonImmutable $desde, CarbonImmutable $hasta): CierreSeccion
    {
        $devengado = CierreSeccion::create(['cierre_id' => $cierre->id, 'codigo' => 'devengado', 'total' => 0]);

        $proyectos = Proyecto::whereIn('estado', ['adjudicado_firmado', 'en_ejecucion', 'en_cierre'])
            ->whereNotNull('monto_preliminar')
            ->get();

        foreach ($proyectos as $proyecto) {
            $porcentaje = $this->prorrateador->porcentajeEnPeriodo($proyecto, $desde, $hasta);
            if ($porcentaje <= 0) {
                continue;
            }

            CierreLinea::create([
                'seccion_id' => $devengado->id,
                'proyecto_id' => $proyecto->id,
                'monto' => round((float) $proyecto->monto_preliminar * $porcentaje, 2),
                'porcentaje_aplicado' => $porcentaje,
                'observaciones' => 'Devengado por días naturales del periodo',
            ]);
        }

        return $devengado;
    }

    private function seccionPipelinePonderado(CierreMensual $cierre, CarbonImmutable $desde, CarbonImmutable $hasta): CierreSeccion
    {
        $pipeline = CierreSeccion::create(['cierre_id' => $cierre->id, 'codigo' => 'pipeline_ponderado', 'total' => 0]);

        // Probabilidad heurística por estado — TODO M12: extraer a tabla configurable
        $probabilidades = [
            'cotizando' => 0.20,
            'cotizado' => 0.30,
            'presentado' => 0.50,
            'adjudicado_pendiente' => 0.85,
        ];

        $proyectos = Proyecto::whereIn('estado', array_keys($probabilidades))
            ->whereNotNull('monto_preliminar')
            ->get();

        foreach ($proyectos as $proyecto) {
            $prob = $probabilidades[$proyecto->estado];

            CierreLinea::create([
                'seccion_id' => $pipeline->id,
                'proyecto_id' => $proyecto->id,
                'monto' => round((float) $proyecto->monto_preliminar * $prob, 2),
                'porcentaje_aplicado' => $prob,
                'observaciones' => "Probabilidad heurística para estado {$proyecto->estado}",
            ]);
        }

        return $pipeline;
    }
}
