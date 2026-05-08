<?php

namespace App\Services\Proyectos;

use App\Models\KickOffMeeting;
use App\Models\Proyecto;
use Illuminate\Support\Facades\DB;
use RuntimeException;

/**
 * Gestiona KOMs (interno y con cliente). Cada proyecto puede tener N koms del mismo tipo
 * (re-convocatorias), pero el "vigente" es el más reciente. La minuta del KOM cliente
 * típicamente cierra con un cronograma adjunto.
 */
class KomService
{
    public function crear(Proyecto $proyecto, int $userId, array $data): KickOffMeeting
    {
        if (! in_array($proyecto->estado, ['adjudicado_firmado', 'en_ejecucion', 'en_cierre', 'cerrado'], true)) {
            throw new RuntimeException("El KOM se levanta entre adjudicado_firmado y cerrado. Estado actual: {$proyecto->estado}.");
        }

        return DB::transaction(function () use ($proyecto, $userId, $data) {
            $kom = KickOffMeeting::create([
                'proyecto_id' => $proyecto->id,
                'tipo' => $data['tipo'],
                'fecha' => $data['fecha'],
                'participantes' => $data['participantes'] ?? [],
                'agenda' => $data['agenda'] ?? null,
                'minuta' => $data['minuta'] ?? null,
            ]);

            $proyecto->recordEvent(
                tipo: $data['tipo'] === 'kom_interno' ? 'kom_interno_creado' : 'kom_cliente_creado',
                userId: $userId,
                payload: ['kom_id' => $kom->id, 'fecha' => (string) $kom->fecha],
            );

            return $kom;
        });
    }

    public function actualizar(KickOffMeeting $kom, array $data): KickOffMeeting
    {
        $kom->update(array_filter([
            'fecha' => $data['fecha'] ?? null,
            'participantes' => $data['participantes'] ?? null,
            'agenda' => $data['agenda'] ?? null,
            'minuta' => $data['minuta'] ?? null,
            'cronograma_attached_id' => $data['cronograma_attached_id'] ?? null,
        ], fn ($v) => $v !== null));

        return $kom->fresh();
    }

    public function eliminar(KickOffMeeting $kom): void
    {
        $kom->delete();
    }
}
