<?php

namespace App\Services\Cotizaciones;

use App\Models\Cotizacion;
use App\Models\Proyecto;
use App\ValueObjects\ResultadoCoss;
use Illuminate\Support\Facades\DB;
use RuntimeException;

class CotizacionService
{
    public function __construct(
        private readonly CalculadoraCoss $calculadora,
    ) {}

    public function crearBorrador(Proyecto $proyecto, ?int $userId = null): Cotizacion
    {
        return DB::transaction(function () use ($proyecto, $userId) {
            $ultima = $proyecto->cotizaciones()->orderByDesc('version')->first();
            $version = ($ultima?->version ?? 0) + 1;

            $cotizacion = Cotizacion::create([
                'proyecto_id' => $proyecto->id,
                'version' => $version,
                'status' => 'borrador',
                'moneda' => $proyecto->moneda ?? 'USD',
                'factor_indirectos' => $ultima?->factor_indirectos ?? 0.18,
                'factor_admin' => $ultima?->factor_admin ?? 0.08,
                'factor_utilidad' => $ultima?->factor_utilidad ?? 0.15,
                'generado_por_id' => $userId,
            ]);

            if ($ultima) {
                foreach ($ultima->partidas as $p) {
                    $cotizacion->partidas()->create([
                        'numero_partida' => $p->numero_partida,
                        'descripcion' => $p->descripcion,
                        'cantidad' => $p->cantidad,
                        'unidad' => $p->unidad,
                        'costo_unitario' => $p->costo_unitario,
                        'costo_total' => $p->costo_total,
                        'observaciones' => $p->observaciones,
                    ]);
                }
            }

            return $cotizacion->fresh('partidas');
        });
    }

    public function recalcular(Cotizacion $cotizacion): ResultadoCoss
    {
        $partidas = $cotizacion->partidas()->get()->map(fn ($p) => [
            'cantidad' => (float) $p->cantidad,
            'costo_unitario' => (float) $p->costo_unitario,
            'costo_total' => (float) $p->costo_total,
        ])->all();

        $resultado = $this->calculadora->calcular($partidas, [
            'indirectos' => (float) $cotizacion->factor_indirectos,
            'admin' => (float) $cotizacion->factor_admin,
            'utilidad' => (float) $cotizacion->factor_utilidad,
        ]);

        $precioFinal = (float) $cotizacion->precio_venta_final > 0
            ? (float) $cotizacion->precio_venta_final
            : $resultado->precioVenta;

        $margenFinal = $precioFinal > 0 ? $resultado->utilidad / $precioFinal : 0;

        $cotizacion->update([
            'costo_directo' => round($resultado->costoDirecto, 2),
            'precio_venta_calculado' => round($resultado->precioVenta, 2),
            'precio_venta_final' => round($precioFinal, 2),
            'margen_neto' => round($margenFinal, 4),
        ]);

        return $resultado;
    }

    /**
     * Pasa la cotización a 'emitida', sella fecha y monto_preliminar del proyecto.
     * Si el proyecto está en 'cotizando', lo mueve a 'cotizado'.
     */
    public function emitir(Cotizacion $cotizacion, int $userId): Cotizacion
    {
        return DB::transaction(function () use ($cotizacion, $userId) {
            if ($cotizacion->status === 'emitida') {
                return $cotizacion;
            }

            if ($cotizacion->status === 'cancelada') {
                throw new RuntimeException('No se puede emitir una cotización cancelada.');
            }

            if ($cotizacion->partidas()->count() === 0) {
                throw new RuntimeException('La cotización no tiene partidas.');
            }

            $this->recalcular($cotizacion);

            $cotizacion->update([
                'status' => 'emitida',
                'fecha_emision' => now(),
                'generado_por_id' => $cotizacion->generado_por_id ?? $userId,
            ]);

            $proyecto = $cotizacion->proyecto;
            $estadoAnterior = $proyecto->estado;

            $updates = [
                'monto_preliminar' => $cotizacion->precio_venta_final,
                'moneda' => $cotizacion->moneda,
            ];

            if ($proyecto->estado === 'cotizando') {
                $updates['estado'] = 'cotizado';
            }

            $proyecto->update($updates);

            $proyecto->recordEvent(
                tipo: 'cotizacion_emitida',
                userId: $userId,
                payload: [
                    'cotizacion_id' => $cotizacion->id,
                    'version' => $cotizacion->version,
                    'precio_venta_final' => (float) $cotizacion->precio_venta_final,
                    'margen_neto' => (float) $cotizacion->margen_neto,
                ],
                estadoAnterior: $estadoAnterior !== $proyecto->estado ? $estadoAnterior : null,
            );

            return $cotizacion->fresh();
        });
    }
}
