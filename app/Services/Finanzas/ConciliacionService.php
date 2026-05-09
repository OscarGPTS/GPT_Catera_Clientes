<?php

namespace App\Services\Finanzas;

use App\Models\MovimientoBancario;
use App\Models\Proyecto;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

/**
 * Sugerencias automáticas de conciliación + acción de conciliar/desconciliar
 * con auditoría.
 */
class ConciliacionService
{
    public function __construct(private readonly FinanzasAuditService $audit) {}

    /**
     * Sugiere proyectos candidatos para conciliar un movimiento, ordenados por score.
     *
     * Heurística:
     *   - Match exacto de CP-XXX/AA o DN-XXX/AA en la descripción → score 100.
     *   - Match del alias del cliente (3 letras) en la descripción → score 60.
     *   - Match del monto vs cotización emitida (±2%) → score 40.
     *   - Combinación de los anteriores se suma (cap 100).
     *
     * @return Collection<int, array{proyecto: Proyecto, score: int, razones: array<string>}>
     */
    public function sugerencias(MovimientoBancario $mov, int $limit = 5): Collection
    {
        $desc = mb_strtoupper($mov->descripcion ?? '');
        $monto = (float) $mov->monto;

        $candidatos = Proyecto::with(['cliente:id,alias_3letras', 'cotizaciones' => fn ($q) => $q->where('status', 'emitida')])
            ->where('estado', '!=', 'perdido')
            ->get();

        return $candidatos->map(function (Proyecto $p) use ($desc, $monto) {
            $razones = [];
            $score = 0;

            if ($p->cp_numero && str_contains($desc, mb_strtoupper($p->cp_numero))) {
                $score += 100;
                $razones[] = "CP {$p->cp_numero} en descripción";
            }
            if ($p->dn_numero && str_contains($desc, mb_strtoupper($p->dn_numero))) {
                $score += 100;
                $razones[] = "DN {$p->dn_numero} en descripción";
            }

            $alias = $p->cliente?->alias_3letras;
            if ($alias && mb_strlen($alias) === 3 && str_contains($desc, mb_strtoupper($alias))) {
                $score += 60;
                $razones[] = "Alias {$alias} en descripción";
            }

            $cot = $p->cotizaciones->first();
            if ($cot && $monto > 0) {
                $diff = abs((float) $cot->precio_venta_final - $monto);
                $tolerancia = $monto * 0.02;
                if ($diff <= $tolerancia) {
                    $score += 40;
                    $razones[] = 'Monto coincide con cotización emitida (±2%)';
                }
            }

            $score = min($score, 100);

            return [
                'proyecto' => $p,
                'score' => $score,
                'razones' => $razones,
            ];
        })
            ->filter(fn ($c) => $c['score'] > 0)
            ->sortByDesc('score')
            ->take($limit)
            ->values();
    }

    public function conciliar(MovimientoBancario $mov, ?int $proyectoId, ?string $factura, int $userId): MovimientoBancario
    {
        return DB::transaction(function () use ($mov, $proyectoId, $factura, $userId) {
            $mov->update([
                'conciliado_con_proyecto_id' => $proyectoId,
                'conciliado_con_factura' => $factura,
                'conciliado_at' => now(),
            ]);

            $this->audit->registrar(
                'movimiento_conciliado',
                $mov,
                [
                    'proyecto_id' => $proyectoId,
                    'factura' => $factura,
                    'monto' => (float) $mov->monto,
                    'tipo' => $mov->tipo,
                ],
                $userId,
            );

            return $mov->fresh();
        });
    }

    public function desconciliar(MovimientoBancario $mov, int $userId): MovimientoBancario
    {
        return DB::transaction(function () use ($mov, $userId) {
            $payloadAnterior = [
                'proyecto_id' => $mov->conciliado_con_proyecto_id,
                'factura' => $mov->conciliado_con_factura,
            ];

            $mov->update([
                'conciliado_con_proyecto_id' => null,
                'conciliado_con_factura' => null,
                'conciliado_at' => null,
            ]);

            $this->audit->registrar('movimiento_desconciliado', $mov, $payloadAnterior, $userId);

            return $mov->fresh();
        });
    }
}
