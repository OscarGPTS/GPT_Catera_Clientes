<?php

namespace App\Services\Procura;

use App\Models\Proyecto;
use App\Models\SolicitudInterna;
use Illuminate\Support\Facades\DB;
use RuntimeException;

class SolicitudInternaService
{
    public function crear(Proyecto $proyecto, int $solicitanteId, array $data): SolicitudInterna
    {
        return DB::transaction(function () use ($proyecto, $solicitanteId, $data) {
            $solicitud = SolicitudInterna::create([
                'tipo' => $data['tipo'],
                'proyecto_id' => $proyecto->id,
                'cp_numero' => $proyecto->cp_numero,
                'codigo_formato' => $data['codigo_formato'] ?? null,
                'estado' => 'borrador',
                'solicitante_id' => $solicitanteId,
                'asignado_id' => $data['asignado_id'] ?? null,
                'fecha_solicitud' => now()->toDateString(),
                'fecha_respuesta_requerida' => $data['fecha_respuesta_requerida'] ?? null,
                'descripcion' => $data['descripcion'] ?? null,
            ]);

            foreach ($data['items'] ?? [] as $item) {
                if (! filled($item['descripcion'] ?? null)) {
                    continue;
                }
                $solicitud->items()->create($item);
            }

            return $solicitud->fresh('items');
        });
    }

    public function emitir(SolicitudInterna $solicitud, int $userId): SolicitudInterna
    {
        if ($solicitud->estado !== 'borrador') {
            throw new RuntimeException('Solo solicitudes en borrador pueden emitirse.');
        }

        if ($solicitud->items()->count() === 0) {
            throw new RuntimeException('La solicitud no tiene items.');
        }

        return DB::transaction(function () use ($solicitud, $userId) {
            $solicitud->update(['estado' => 'emitida']);

            $solicitud->proyecto->recordEvent(
                tipo: 'solicitud_interna_emitida',
                userId: $userId,
                payload: [
                    'solicitud_id' => $solicitud->id,
                    'tipo' => $solicitud->tipo,
                    'items' => $solicitud->items()->count(),
                ],
            );

            return $solicitud->fresh();
        });
    }

    public function tomar(SolicitudInterna $solicitud, int $userId): SolicitudInterna
    {
        if ($solicitud->estado !== 'emitida') {
            throw new RuntimeException("La solicitud debe estar 'emitida' para tomarla. Estado actual: {$solicitud->estado}.");
        }

        $solicitud->update([
            'estado' => 'en_proceso',
            'asignado_id' => $userId,
        ]);

        return $solicitud->fresh();
    }

    public function responder(SolicitudInterna $solicitud, int $userId, string $respuesta): SolicitudInterna
    {
        if (! in_array($solicitud->estado, ['emitida', 'en_proceso'], true)) {
            throw new RuntimeException("La solicitud no puede responderse en estado {$solicitud->estado}.");
        }

        return DB::transaction(function () use ($solicitud, $userId, $respuesta) {
            $solicitud->update([
                'estado' => 'respondida',
                'respuesta' => $respuesta,
                'fecha_respuesta_real' => now()->toDateString(),
                'asignado_id' => $solicitud->asignado_id ?? $userId,
            ]);

            $solicitud->proyecto->recordEvent(
                tipo: 'solicitud_interna_respondida',
                userId: $userId,
                payload: ['solicitud_id' => $solicitud->id, 'tipo' => $solicitud->tipo],
            );

            return $solicitud->fresh();
        });
    }

    public function cancelar(SolicitudInterna $solicitud, int $userId, ?string $razon = null): SolicitudInterna
    {
        if ($solicitud->estado === 'respondida') {
            throw new RuntimeException('No se puede cancelar una solicitud ya respondida.');
        }

        $solicitud->update(['estado' => 'cancelada']);

        $solicitud->proyecto->recordEvent(
            tipo: 'solicitud_interna_cancelada',
            userId: $userId,
            payload: ['solicitud_id' => $solicitud->id],
            comentario: $razon,
        );

        return $solicitud->fresh();
    }
}
