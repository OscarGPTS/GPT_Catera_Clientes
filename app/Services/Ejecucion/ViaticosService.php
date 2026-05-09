<?php

namespace App\Services\Ejecucion;

use App\Models\Proyecto;
use App\Models\SolicitudViaticos;
use App\Notifications\ViaticosAprobadosNotification;
use Illuminate\Support\Facades\DB;
use RuntimeException;

/**
 * Flujo de viáticos:
 *   borrador → pendiente_servgrales (al emitir) → pendiente_direccion (aprueba serv_grales)
 *   → aprobada (aprueba dirección) | rechazada (cualquier paso)
 */
class ViaticosService
{
    public function crear(Proyecto $proyecto, int $solicitanteId, array $data): SolicitudViaticos
    {
        return DB::transaction(function () use ($proyecto, $solicitanteId, $data) {
            $solicitud = SolicitudViaticos::create([
                'proyecto_id' => $proyecto->id,
                'periodo_inicio' => $data['periodo_inicio'],
                'periodo_fin' => $data['periodo_fin'],
                'justificacion' => $data['justificacion'] ?? null,
                'status' => 'borrador',
                'solicitante_id' => $solicitanteId,
            ]);

            foreach ($data['personal'] ?? [] as $p) {
                if (! filled($p['user_id'] ?? null)) {
                    continue;
                }
                $solicitud->personal()->create([
                    'user_id' => $p['user_id'],
                    'dias' => $p['dias'] ?? 1,
                ]);
            }

            foreach ($data['partidas'] ?? [] as $p) {
                if (! filled($p['concepto'] ?? null)) {
                    continue;
                }
                $solicitud->partidas()->create([
                    'concepto' => $p['concepto'],
                    'monto_estimado' => $p['monto_estimado'] ?? 0,
                    'observaciones' => $p['observaciones'] ?? null,
                ]);
            }

            return $solicitud->fresh(['personal.user', 'partidas']);
        });
    }

    public function emitir(SolicitudViaticos $solicitud, int $userId): SolicitudViaticos
    {
        if ($solicitud->status !== 'borrador') {
            throw new RuntimeException('Solo solicitudes en borrador pueden emitirse.');
        }

        if ($solicitud->personal()->count() === 0) {
            throw new RuntimeException('Agrega al menos un beneficiario antes de emitir.');
        }

        if ($solicitud->partidas()->count() === 0) {
            throw new RuntimeException('Agrega al menos una partida antes de emitir.');
        }

        $solicitud->update(['status' => 'pendiente_servgrales']);

        $solicitud->proyecto->recordEvent(
            tipo: 'viaticos_emitidos',
            userId: $userId,
            payload: ['solicitud_id' => $solicitud->id],
        );

        return $solicitud->fresh();
    }

    public function aprobarServGrales(SolicitudViaticos $solicitud, int $userId): SolicitudViaticos
    {
        if ($solicitud->status !== 'pendiente_servgrales') {
            throw new RuntimeException("La solicitud debe estar 'pendiente_servgrales'. Estado actual: {$solicitud->status}.");
        }

        $solicitud->update([
            'status' => 'pendiente_direccion',
            'aprobador_serv_grales_id' => $userId,
        ]);

        return $solicitud->fresh();
    }

    public function aprobarDireccion(SolicitudViaticos $solicitud, int $userId): SolicitudViaticos
    {
        if ($solicitud->status !== 'pendiente_direccion') {
            throw new RuntimeException("La solicitud debe estar 'pendiente_direccion'. Estado actual: {$solicitud->status}.");
        }

        $solicitud->update([
            'status' => 'aprobada',
            'aprobador_direccion_id' => $userId,
            'aprobado_at' => now(),
        ]);

        $solicitud->proyecto->recordEvent(
            tipo: 'viaticos_aprobados',
            userId: $userId,
            payload: ['solicitud_id' => $solicitud->id],
        );

        // M9 · Notificar al solicitante
        $solicitud->loadMissing(['proyecto', 'solicitante', 'partidas']);
        $solicitud->solicitante?->notify(new ViaticosAprobadosNotification($solicitud));

        return $solicitud->fresh();
    }

    public function rechazar(SolicitudViaticos $solicitud, int $userId, string $razon): SolicitudViaticos
    {
        if (! in_array($solicitud->status, ['pendiente_servgrales', 'pendiente_direccion'], true)) {
            throw new RuntimeException("La solicitud no puede rechazarse en estado {$solicitud->status}.");
        }

        $solicitud->update(['status' => 'rechazada']);

        $solicitud->proyecto->recordEvent(
            tipo: 'viaticos_rechazados',
            userId: $userId,
            payload: ['solicitud_id' => $solicitud->id],
            comentario: $razon,
        );

        return $solicitud->fresh();
    }

    public function registrarMontoReal(SolicitudViaticos $solicitud, array $partidasReal): SolicitudViaticos
    {
        if ($solicitud->status !== 'aprobada') {
            throw new RuntimeException('Solo solicitudes aprobadas pueden tener monto real registrado.');
        }

        foreach ($partidasReal as $partidaId => $real) {
            $partida = $solicitud->partidas()->where('id', $partidaId)->first();
            $partida?->update(['monto_real' => $real]);
        }

        return $solicitud->fresh(['partidas']);
    }
}
