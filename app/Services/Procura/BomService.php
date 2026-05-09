<?php

namespace App\Services\Procura;

use App\Models\BomBoeItem;
use App\Models\Proyecto;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use RuntimeException;

/**
 * BOM/BOE — Bill of Materials / Bill of Equipment.
 *
 * Heurística para clasificar partidas de la cotización:
 *   - Por defecto BOM (materiales y consumibles).
 *   - Si la descripción contiene palabras clave de equipo/máquina (renta, equipo,
 *     máquina, soldador certificado…), se clasifica como BOE.
 *
 * Status iniciales según descripción:
 *   - "renta", "renta de" → en_almacen (presumimos disponible).
 *   - "fabricar", "fabricado" → por_fabricar.
 *   - resto → por_comprar.
 */
class BomService
{
    private const PALABRAS_BOE = ['máquina', 'maquina', 'equipo', 'renta', 'jornada', 'soldador', 'cuadrilla', 'operador'];

    public function importarDesdeCotizacion(Proyecto $proyecto, int $userId): int
    {
        if (! in_array($proyecto->estado, ['adjudicado_firmado', 'en_ejecucion', 'en_cierre', 'cerrado'], true)) {
            throw new RuntimeException("El BOM/BOE se levanta tras adjudicacion firmada. Estado actual: {$proyecto->estado}.");
        }

        $cotizacion = $proyecto->cotizaciones()->where('status', 'emitida')->orderByDesc('version')->first();
        if (! $cotizacion) {
            throw new RuntimeException('No hay cotización emitida que sirva de base para el BOM/BOE.');
        }

        return DB::transaction(function () use ($proyecto, $userId, $cotizacion) {
            $importados = 0;

            foreach ($cotizacion->partidas as $partida) {
                $tipo = $this->clasificarTipo($partida->descripcion);
                $status = $this->statusInicial($partida->descripcion);

                BomBoeItem::create([
                    'proyecto_id' => $proyecto->id,
                    'tipo' => $tipo,
                    'descripcion' => $partida->descripcion,
                    'cantidad' => $partida->cantidad,
                    'unidad' => $partida->unidad,
                    'status' => $status,
                    'responsable_id' => $proyecto->ingeniero_proyectos_id ?? $proyecto->gerente_proyectos_id,
                ]);

                $importados++;
            }

            $proyecto->recordEvent(
                tipo: 'bom_importado',
                userId: $userId,
                payload: ['cotizacion_id' => $cotizacion->id, 'items_importados' => $importados],
            );

            return $importados;
        });
    }

    public function crearItem(Proyecto $proyecto, array $data): BomBoeItem
    {
        return $proyecto->bomBoeItems()->create($data);
    }

    public function actualizarItem(BomBoeItem $item, array $data): BomBoeItem
    {
        $item->update(array_filter([
            'tipo' => $data['tipo'] ?? null,
            'descripcion' => $data['descripcion'] ?? null,
            'cantidad' => $data['cantidad'] ?? null,
            'unidad' => $data['unidad'] ?? null,
            'status' => $data['status'] ?? null,
            'responsable_id' => $data['responsable_id'] ?? null,
            'fecha_requerida' => $data['fecha_requerida'] ?? null,
            'observaciones' => $data['observaciones'] ?? null,
        ], fn ($v) => $v !== null));

        return $item->fresh();
    }

    public function eliminarItem(BomBoeItem $item): void
    {
        $item->delete();
    }

    public function resumenPorStatus(Proyecto $proyecto): Collection
    {
        return $proyecto->bomBoeItems()
            ->selectRaw('status, count(*) as total')
            ->groupBy('status')
            ->pluck('total', 'status');
    }

    private function clasificarTipo(string $descripcion): string
    {
        $lower = mb_strtolower($descripcion);
        foreach (self::PALABRAS_BOE as $palabra) {
            if (str_contains($lower, $palabra)) {
                return 'BOE';
            }
        }

        return 'BOM';
    }

    private function statusInicial(string $descripcion): string
    {
        $lower = mb_strtolower($descripcion);

        if (str_contains($lower, 'renta')) {
            return 'en_almacen';
        }

        if (str_contains($lower, 'fabricar') || str_contains($lower, 'fabricado') || str_contains($lower, 'a medida')) {
            return 'por_fabricar';
        }

        return 'por_comprar';
    }
}
