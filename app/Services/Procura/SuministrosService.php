<?php

namespace App\Services\Procura;

use App\Models\ListadoSuministros;
use App\Models\ListadoSuministrosItem;
use App\Models\Proyecto;
use Illuminate\Support\Facades\DB;

/**
 * Listado de Suministros — vista operativa por etapas (0-25, 26-50, 51-75, 76-100)
 * de cada renglón de procura. Cada item evoluciona por etapas:
 *   1) definicion (0-25%)
 *   2) cotizando (26-50%)
 *   3) ordenado (51-75%)
 *   4) entregado (76-100%)
 */
class SuministrosService
{
    private const ETAPAS_POR_STATUS = [
        'definicion' => '0-25',
        'cotizando' => '26-50',
        'ordenado' => '51-75',
        'en_transito' => '51-75',
        'entregado' => '76-100',
    ];

    private const AVANCE_POR_STATUS = [
        'definicion' => 10,
        'cotizando' => 40,
        'ordenado' => 60,
        'en_transito' => 75,
        'entregado' => 100,
    ];

    public function abrirParaProyecto(Proyecto $proyecto): ListadoSuministros
    {
        return ListadoSuministros::firstOrCreate(['proyecto_id' => $proyecto->id]);
    }

    public function importarDesdeBom(Proyecto $proyecto): int
    {
        $listado = $this->abrirParaProyecto($proyecto);
        $importados = 0;

        return DB::transaction(function () use ($proyecto, $listado, &$importados) {
            foreach ($proyecto->bomBoeItems as $item) {
                if ($listado->items()->where('descripcion', $item->descripcion)->exists()) {
                    continue;
                }

                $status = match ($item->status) {
                    'en_almacen', 'entregado' => 'entregado',
                    'en_transito' => 'en_transito',
                    'por_fabricar' => 'cotizando',
                    default => 'definicion',
                };

                $listado->items()->create([
                    'descripcion' => $item->descripcion,
                    'cantidad' => $item->cantidad,
                    'unidad' => $item->unidad,
                    'fecha_requerida' => $item->fecha_requerida,
                    'status' => $status,
                    'porcentaje_avance' => self::AVANCE_POR_STATUS[$status] ?? 10,
                    'etapa' => self::ETAPAS_POR_STATUS[$status] ?? '0-25',
                ]);

                $importados++;
            }

            $listado->recalcularAvance();

            return $importados;
        });
    }

    public function crearItem(Proyecto $proyecto, array $data): ListadoSuministrosItem
    {
        $listado = $this->abrirParaProyecto($proyecto);
        $status = $data['status'] ?? 'definicion';

        $item = $listado->items()->create([
            'descripcion' => $data['descripcion'],
            'cantidad' => $data['cantidad'] ?? 1,
            'unidad' => $data['unidad'] ?? null,
            'fecha_requerida' => $data['fecha_requerida'] ?? null,
            'status' => $status,
            'porcentaje_avance' => self::AVANCE_POR_STATUS[$status] ?? 10,
            'etapa' => self::ETAPAS_POR_STATUS[$status] ?? '0-25',
        ]);

        $listado->recalcularAvance();

        return $item;
    }

    public function actualizarItem(ListadoSuministrosItem $item, array $data): ListadoSuministrosItem
    {
        $newStatus = $data['status'] ?? $item->status;

        $item->update([
            'descripcion' => $data['descripcion'] ?? $item->descripcion,
            'cantidad' => $data['cantidad'] ?? $item->cantidad,
            'unidad' => $data['unidad'] ?? $item->unidad,
            'fecha_requerida' => $data['fecha_requerida'] ?? $item->fecha_requerida,
            'status' => $newStatus,
            'porcentaje_avance' => self::AVANCE_POR_STATUS[$newStatus] ?? $item->porcentaje_avance,
            'etapa' => self::ETAPAS_POR_STATUS[$newStatus] ?? $item->etapa,
        ]);

        $item->listado->recalcularAvance();

        return $item->fresh();
    }

    public function eliminarItem(ListadoSuministrosItem $item): void
    {
        $listado = $item->listado;
        $item->delete();
        $listado->recalcularAvance();
    }

    public static function statusOptions(): array
    {
        return [
            'definicion' => 'Definición (0-25%)',
            'cotizando' => 'Cotizando (26-50%)',
            'ordenado' => 'Ordenado (51-75%)',
            'en_transito' => 'En tránsito (51-75%)',
            'entregado' => 'Entregado (76-100%)',
        ];
    }
}
