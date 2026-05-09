<?php

namespace App\Services\Minutas;

use App\Models\MinutaEntrega;
use App\Models\Proyecto;
use App\Notifications\MinutaFirmadaNotification;
use Illuminate\Support\Facades\DB;
use RuntimeException;

/**
 * D10. Minuta de Entrega CP→DN. Cuando system_setting `minuta_entrega_obligatoria`
 * es true, esta minuta firmada es prerequisito para pasar a 'en_ejecucion'.
 */
class MinutaEntregaService
{
    public function crearOSeleccionar(Proyecto $proyecto, int $userId): MinutaEntrega
    {
        if (! in_array($proyecto->estado, ['adjudicado_pendiente', 'adjudicado_firmado', 'en_ejecucion', 'en_cierre', 'cerrado'], true)) {
            throw new RuntimeException('La minuta CP→DN se levanta tras la adjudicación.');
        }

        return DB::transaction(function () use ($proyecto, $userId) {
            $minuta = MinutaEntrega::firstOrCreate(
                ['proyecto_id' => $proyecto->id],
                [
                    'fecha_reunion' => now()->toDateString(),
                    'modalidad' => 'virtual',
                    'status' => 'borrador',
                ],
            );

            if ($minuta->wasRecentlyCreated) {
                $this->sembrarParticipantes($proyecto, $minuta);
                $proyecto->recordEvent(
                    tipo: 'minuta_creada',
                    userId: $userId,
                    payload: ['minuta_id' => $minuta->id],
                );
            }

            return $minuta->fresh('participantes.user');
        });
    }

    public function actualizar(MinutaEntrega $minuta, array $data): MinutaEntrega
    {
        if ($minuta->status === 'firmada') {
            throw new RuntimeException('No se puede editar una minuta firmada.');
        }

        $minuta->update(array_filter([
            'fecha_reunion' => $data['fecha_reunion'] ?? null,
            'hora_inicio' => $data['hora_inicio'] ?? null,
            'hora_fin' => $data['hora_fin'] ?? null,
            'modalidad' => $data['modalidad'] ?? null,
            'orden_del_dia' => $data['orden_del_dia'] ?? null,
            'acuerdos' => $data['acuerdos'] ?? null,
        ], fn ($v) => $v !== null));

        return $minuta->fresh();
    }

    public function agregarParticipante(MinutaEntrega $minuta, int $userId, ?string $rol = null): void
    {
        if ($minuta->status === 'firmada') {
            throw new RuntimeException('No se puede modificar una minuta firmada.');
        }

        $minuta->participantes()->updateOrCreate(
            ['user_id' => $userId],
            ['rol_en_minuta' => $rol, 'firma_pendiente' => true],
        );
    }

    public function quitarParticipante(MinutaEntrega $minuta, int $userId): void
    {
        if ($minuta->status === 'firmada') {
            throw new RuntimeException('No se puede modificar una minuta firmada.');
        }

        $minuta->participantes()->where('user_id', $userId)->delete();
    }

    public function firmarPorUsuario(MinutaEntrega $minuta, int $userId): MinutaEntrega
    {
        $participante = $minuta->participantes()->where('user_id', $userId)->first();

        if (! $participante) {
            throw new RuntimeException('El usuario no es participante de esta minuta.');
        }

        return DB::transaction(function () use ($minuta, $userId, $participante) {
            $participante->update([
                'firma_pendiente' => false,
                'firmado_at' => now(),
            ]);

            // Si todos firmaron, sellar la minuta
            $pendientes = $minuta->participantes()->where('firma_pendiente', true)->count();
            if ($pendientes === 0) {
                $minuta->update([
                    'status' => 'firmada',
                    'firmado_at' => now(),
                ]);

                $minuta->proyecto->recordEvent(
                    tipo: 'minuta_firmada',
                    userId: $userId,
                    payload: ['minuta_id' => $minuta->id],
                );

                // M9 · Notificar a GP y director_dn
                $proyecto = $minuta->proyecto;
                $destinatarios = collect([$proyecto->gerenteProyectos, $proyecto->directorDn])->filter()->unique('id');
                foreach ($destinatarios as $u) {
                    $u->notify(new MinutaFirmadaNotification($minuta->fresh()));
                }
            }

            return $minuta->fresh('participantes.user');
        });
    }

    private function sembrarParticipantes(Proyecto $proyecto, MinutaEntrega $minuta): void
    {
        $candidatos = array_filter([
            ['id' => $proyecto->director_dn_id, 'rol' => 'Director DN (entrega CP)'],
            ['id' => $proyecto->gerente_proyectos_id, 'rol' => 'Gerente de Proyectos (entrega CP)'],
            ['id' => $proyecto->gerente_operaciones_id, 'rol' => 'Gerente de Operaciones (recibe DN)'],
            ['id' => $proyecto->ingeniero_proyectos_id, 'rol' => 'Ingeniero de Proyectos (recibe DN)'],
        ], fn ($p) => ! empty($p['id']));

        foreach ($candidatos as $c) {
            $minuta->participantes()->updateOrCreate(
                ['user_id' => $c['id']],
                ['rol_en_minuta' => $c['rol'], 'firma_pendiente' => true],
            );
        }
    }
}
