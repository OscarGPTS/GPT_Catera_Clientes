<?php

namespace App\Services\Ejecucion;

use App\Models\BitacoraDiaria;
use App\Models\Proyecto;
use Illuminate\Support\Facades\DB;
use RuntimeException;

class BitacoraService
{
    public function crear(Proyecto $proyecto, int $userId, array $data): BitacoraDiaria
    {
        if (! in_array($proyecto->estado, ['en_ejecucion', 'en_cierre', 'cerrado'], true)) {
            throw new RuntimeException("La bitácora se levanta con el proyecto en ejecución. Estado actual: {$proyecto->estado}.");
        }

        if ($proyecto->bitacoras()->where('fecha', $data['fecha'])->exists()) {
            throw new RuntimeException('Ya existe una bitácora para esa fecha.');
        }

        return DB::transaction(function () use ($proyecto, $userId, $data) {
            $bitacora = $proyecto->bitacoras()->create([
                'fecha' => $data['fecha'],
                'relacion_actividades' => $data['relacion_actividades'],
                'personal_gpt' => $this->limpiarLista($data['personal_gpt'] ?? []),
                'equipos_en_sitio' => $this->limpiarLista($data['equipos_en_sitio'] ?? []),
                'proveedores_subcontratistas' => $this->limpiarLista($data['proveedores_subcontratistas'] ?? []),
                'cargado_por_id' => $userId,
            ]);

            $proyecto->recordEvent(
                tipo: 'bitacora_creada',
                userId: $userId,
                payload: [
                    'bitacora_id' => $bitacora->id,
                    'fecha' => (string) $bitacora->fecha,
                    'desviacion' => $bitacora->tieneDesviacion(),
                ],
            );

            return $bitacora;
        });
    }

    public function actualizar(BitacoraDiaria $bitacora, array $data): BitacoraDiaria
    {
        if ($bitacora->firmado_at) {
            throw new RuntimeException('No se puede editar una bitácora con VoBo del cliente.');
        }

        $bitacora->update([
            'relacion_actividades' => $data['relacion_actividades'] ?? $bitacora->relacion_actividades,
            'personal_gpt' => $this->limpiarLista($data['personal_gpt'] ?? $bitacora->personal_gpt ?? []),
            'equipos_en_sitio' => $this->limpiarLista($data['equipos_en_sitio'] ?? $bitacora->equipos_en_sitio ?? []),
            'proveedores_subcontratistas' => $this->limpiarLista($data['proveedores_subcontratistas'] ?? $bitacora->proveedores_subcontratistas ?? []),
        ]);

        return $bitacora->fresh();
    }

    public function registrarVoBoCliente(BitacoraDiaria $bitacora, array $data, int $userId): BitacoraDiaria
    {
        $bitacora->update([
            'vobo_cliente_nombre' => $data['vobo_cliente_nombre'],
            'vobo_cliente_organizacion' => $data['vobo_cliente_organizacion'] ?? null,
            'vobo_cliente_fecha' => $data['vobo_cliente_fecha'] ?? now()->toDateString(),
            'vobo_cliente_firma_path' => $data['vobo_cliente_firma_path'] ?? null,
            'firmado_at' => now(),
        ]);

        $bitacora->proyecto->recordEvent(
            tipo: 'bitacora_firmada_cliente',
            userId: $userId,
            payload: ['bitacora_id' => $bitacora->id, 'fecha' => (string) $bitacora->fecha],
        );

        return $bitacora->fresh();
    }

    public function eliminar(BitacoraDiaria $bitacora): void
    {
        if ($bitacora->firmado_at) {
            throw new RuntimeException('No se puede eliminar una bitácora ya firmada.');
        }

        $bitacora->delete();
    }

    /** @param array<int, string|array> $items */
    private function limpiarLista(array $items): array
    {
        return array_values(array_filter($items, fn ($i) => filled(is_array($i) ? ($i['nombre'] ?? null) : $i)));
    }
}
