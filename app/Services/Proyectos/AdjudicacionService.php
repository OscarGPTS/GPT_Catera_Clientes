<?php

namespace App\Services\Proyectos;

use App\Models\Proyecto;
use App\Models\SystemSetting;
use App\Notifications\OcFirmadaNotification;
use App\Services\Libro\AperturaLibroService;
use Illuminate\Support\Facades\DB;
use RuntimeException;

/**
 * Maneja las transiciones de la fase comercial-comprometida del CP→DN:
 *   cotizado → presentado → adjudicado_pendiente → adjudicado_firmado → en_ejecucion
 *
 * Reglas de negocio:
 *   - presentar: requiere al menos una cotización emitida.
 *   - registrarAdjudicacion (pendiente): captura datos de OC del cliente.
 *   - firmarOc: marca OC firmada y genera DN-XXX/AA atómico.
 *   - iniciarEjecucion: respeta D10 (system_setting `minuta_entrega_obligatoria`):
 *     si es true, requiere minuta_entrega.status === 'firmada'. Abre el libro.
 */
class AdjudicacionService
{
    public function __construct(
        private readonly SecuenciasService $secuencias,
        private readonly AperturaLibroService $aperturaLibro,
    ) {}

    public function presentar(Proyecto $proyecto, int $userId, ?string $comentario = null): Proyecto
    {
        if ($proyecto->estado !== 'cotizado') {
            throw new RuntimeException("El CP debe estar en 'cotizado' para presentarlo. Estado actual: {$proyecto->estado}.");
        }

        if (! $proyecto->cotizaciones()->where('status', 'emitida')->exists()) {
            throw new RuntimeException('Se requiere al menos una cotización emitida antes de presentar.');
        }

        return DB::transaction(function () use ($proyecto, $userId, $comentario) {
            $estadoAnterior = $proyecto->estado;
            $proyecto->update(['estado' => 'presentado']);

            $proyecto->recordEvent(
                tipo: 'cotizacion_presentada',
                userId: $userId,
                comentario: $comentario,
                estadoAnterior: $estadoAnterior,
            );

            return $proyecto->fresh();
        });
    }

    public function registrarAdjudicacion(Proyecto $proyecto, int $userId, array $datosOc): Proyecto
    {
        if (! in_array($proyecto->estado, ['presentado', 'cotizado'], true)) {
            throw new RuntimeException("El CP debe estar en 'presentado' o 'cotizado' para registrar adjudicación. Estado actual: {$proyecto->estado}.");
        }

        return DB::transaction(function () use ($proyecto, $userId, $datosOc) {
            $estadoAnterior = $proyecto->estado;
            $proyecto->update(['estado' => 'adjudicado_pendiente']);

            $proyecto->recordEvent(
                tipo: 'adjudicacion_registrada',
                userId: $userId,
                payload: $datosOc,
                comentario: $datosOc['comentario'] ?? null,
                estadoAnterior: $estadoAnterior,
            );

            return $proyecto->fresh();
        });
    }

    public function firmarOc(Proyecto $proyecto, int $userId, array $datosFirma): Proyecto
    {
        if ($proyecto->estado !== 'adjudicado_pendiente') {
            throw new RuntimeException("El CP debe estar en 'adjudicado_pendiente' para firmar OC. Estado actual: {$proyecto->estado}.");
        }

        return DB::transaction(function () use ($proyecto, $userId, $datosFirma) {
            $estadoAnterior = $proyecto->estado;
            $año = (int) ($proyecto->año ?? now()->year);

            if (! $proyecto->dn_numero) {
                $dn = $this->secuencias->asignarDn($año);
                $proyecto->update(['dn_numero' => $dn]);
                $proyecto->recordEvent(
                    tipo: 'dn_asignado',
                    userId: $userId,
                    payload: ['dn_numero' => $dn],
                );
            }

            $proyecto->update(['estado' => 'adjudicado_firmado']);

            $proyecto->recordEvent(
                tipo: 'oc_firmada',
                userId: $userId,
                payload: $datosFirma,
                comentario: $datosFirma['comentario'] ?? null,
                estadoAnterior: $estadoAnterior,
            );

            // M9 · Notificar a GP y GO que el DN está asignado y deben levantar minuta
            $fresh = $proyecto->fresh();
            $destinatarios = collect([$fresh->gerenteProyectos, $fresh->gerenteOperaciones])->filter();
            foreach ($destinatarios as $u) {
                $u->notify(new OcFirmadaNotification($fresh));
            }

            return $fresh;
        });
    }

    public function iniciarEjecucion(Proyecto $proyecto, int $userId): Proyecto
    {
        if ($proyecto->estado !== 'adjudicado_firmado') {
            throw new RuntimeException("El CP debe estar en 'adjudicado_firmado' para iniciar ejecución. Estado actual: {$proyecto->estado}.");
        }

        $minutaObligatoria = (bool) SystemSetting::get('minuta_entrega_obligatoria', false);
        if ($minutaObligatoria) {
            $minuta = $proyecto->minutaEntrega;
            if (! $minuta || $minuta->status !== 'firmada') {
                throw new RuntimeException('La minuta de entrega CP→DN debe estar firmada antes de iniciar ejecución (D10).');
            }
        }

        return DB::transaction(function () use ($proyecto, $userId) {
            $estadoAnterior = $proyecto->estado;
            $proyecto->update(['estado' => 'en_ejecucion']);
            $this->aperturaLibro->abrirParaProyecto($proyecto->fresh());

            $proyecto->recordEvent(
                tipo: 'ejecucion_iniciada',
                userId: $userId,
                estadoAnterior: $estadoAnterior,
            );

            return $proyecto->fresh();
        });
    }

    public function marcarPerdido(Proyecto $proyecto, int $userId, string $razon): Proyecto
    {
        if (! in_array($proyecto->estado, ['presentado', 'cotizado'], true)) {
            throw new RuntimeException("Solo CPs en 'cotizado' o 'presentado' pueden marcarse como perdidos. Estado actual: {$proyecto->estado}.");
        }

        return DB::transaction(function () use ($proyecto, $userId, $razon) {
            $estadoAnterior = $proyecto->estado;
            $proyecto->update(['estado' => 'perdido']);

            $proyecto->recordEvent(
                tipo: 'cp_perdido',
                userId: $userId,
                payload: ['razon' => $razon],
                comentario: $razon,
                estadoAnterior: $estadoAnterior,
            );

            return $proyecto->fresh();
        });
    }
}
