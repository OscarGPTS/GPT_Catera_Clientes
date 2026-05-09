<?php

namespace App\Services\Ejecucion;

use App\Models\Proyecto;
use App\Models\ReporteSemanal;
use App\Notifications\ReporteSemanalGeneradoNotification;
use Carbon\CarbonImmutable;
use Illuminate\Support\Facades\DB;
use RuntimeException;

/**
 * Genera el reporte semanal compilando bitácoras + cronograma + suministros + libro
 * en un bloque HTML guardado en `contenido_html`. Idempotente por (proyecto_id, semana_inicio).
 */
class ReporteSemanalService
{
    public function generar(Proyecto $proyecto, CarbonImmutable $semanaInicio, int $userId): ReporteSemanal
    {
        if (! in_array($proyecto->estado, ['en_ejecucion', 'en_cierre', 'cerrado'], true)) {
            throw new RuntimeException("El reporte semanal aplica al proyecto en ejecución. Estado actual: {$proyecto->estado}.");
        }

        $inicio = $semanaInicio->startOfWeek();
        $fin = $inicio->endOfWeek();

        return DB::transaction(function () use ($proyecto, $inicio, $fin, $userId) {
            $reporte = ReporteSemanal::where('proyecto_id', $proyecto->id)
                ->whereDate('semana_inicio', $inicio->toDateString())
                ->first();

            if (! $reporte) {
                $reporte = ReporteSemanal::create([
                    'proyecto_id' => $proyecto->id,
                    'semana_inicio' => $inicio->toDateString(),
                    'semana_fin' => $fin->toDateString(),
                ]);
            }

            $reporte->update([
                'contenido_html' => $this->compilarHtml($proyecto, $inicio, $fin),
                'generado_at' => now(),
            ]);

            $proyecto->recordEvent(
                tipo: 'reporte_semanal_generado',
                userId: $userId,
                payload: [
                    'reporte_id' => $reporte->id,
                    'semana' => $inicio->format('Y-m-d'),
                ],
            );

            // M9 · Notificar a GP y director_dn que el reporte está listo
            $destinatarios = collect([$proyecto->gerenteProyectos, $proyecto->directorDn])->filter()->unique('id');
            foreach ($destinatarios as $u) {
                $u->notify(new ReporteSemanalGeneradoNotification($reporte->fresh()));
            }

            return $reporte->fresh();
        });
    }

    public function marcarEnviado(ReporteSemanal $reporte, array $recipients): ReporteSemanal
    {
        $reporte->update([
            'recipients' => $recipients,
            'enviado_at' => now(),
        ]);

        return $reporte->fresh();
    }

    private function compilarHtml(Proyecto $proyecto, CarbonImmutable $inicio, CarbonImmutable $fin): string
    {
        $proyecto->loadMissing(['cliente', 'sublinea']);

        $bitacoras = $proyecto->bitacoras()
            ->whereBetween('fecha', [$inicio->toDateString(), $fin->toDateString()])
            ->orderBy('fecha')
            ->get();

        $cronograma = $proyecto->cronogramaVigente();
        $libro = $proyecto->libro;
        $listado = $proyecto->listadoSuministros;

        $html = '<h2>Reporte semanal · '.htmlspecialchars($proyecto->cp_numero ?? '').'</h2>';
        $html .= '<p><strong>Cliente:</strong> '.htmlspecialchars($proyecto->cliente?->razon_social ?? '—').
                 ' · <strong>Sublínea:</strong> '.htmlspecialchars($proyecto->sublinea?->codigo ?? '—').
                 ' · <strong>Periodo:</strong> '.$inicio->format('Y-m-d').' → '.$fin->format('Y-m-d').'</p>';

        $html .= '<h3>Resumen ejecutivo</h3>';
        $html .= '<ul>';
        if ($cronograma) {
            $avance = $cronograma->actividades()->avg('porcentaje_avance') ?? 0;
            $html .= '<li><strong>Cronograma v'.$cronograma->version.':</strong> '.number_format((float) $avance, 1).'% avance promedio.</li>';
        }
        if ($libro) {
            $html .= '<li><strong>Dossier (Libro):</strong> '.number_format((float) $libro->porcentaje_avance_global, 1).'% avance.</li>';
        }
        if ($listado) {
            $html .= '<li><strong>Suministros:</strong> '.number_format((float) $listado->porcentaje_avance_global, 1).'% avance, '.$listado->items()->count().' items.</li>';
        }
        $html .= '<li><strong>Bitácoras de la semana:</strong> '.$bitacoras->count().'.</li>';

        $desviaciones = $bitacoras->filter(fn ($b) => $b->tieneDesviacion());
        if ($desviaciones->isNotEmpty()) {
            $html .= '<li style="color:#b91c1c;"><strong>⚠ '.$desviaciones->count().' bitácoras con posibles desviaciones detectadas.</strong></li>';
        }
        $html .= '</ul>';

        $html .= '<h3>Bitácoras de la semana</h3>';
        if ($bitacoras->isEmpty()) {
            $html .= '<p><em>No se cargaron bitácoras esta semana.</em></p>';
        } else {
            foreach ($bitacoras as $b) {
                $marca = $b->tieneDesviacion() ? ' <span style="color:#b91c1c;">⚠</span>' : '';
                $html .= '<h4>'.$b->fecha->format('Y-m-d (l)').$marca.'</h4>';
                $html .= '<p>'.nl2br(htmlspecialchars($b->relacion_actividades)).'</p>';
                if ($b->vobo_cliente_nombre) {
                    $html .= '<p><em>VoBo: '.htmlspecialchars($b->vobo_cliente_nombre).' · '.htmlspecialchars((string) $b->vobo_cliente_organizacion).'</em></p>';
                }
            }
        }

        return $html;
    }
}
