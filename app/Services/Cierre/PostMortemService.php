<?php

namespace App\Services\Cierre;

use App\Models\PostMortem;
use App\Models\Proyecto;
use Illuminate\Support\Facades\DB;
use RuntimeException;

class PostMortemService
{
    public function crearOActualizar(Proyecto $proyecto, int $userId, array $data): PostMortem
    {
        if (! in_array($proyecto->estado, ['en_cierre', 'cerrado'], true)) {
            throw new RuntimeException("El post-mortem se levanta cuando el proyecto está en cierre. Estado actual: {$proyecto->estado}.");
        }

        return DB::transaction(function () use ($proyecto, $userId, $data) {
            $pm = $proyecto->postMortem;

            $datos = [
                'fecha_sesion' => $data['fecha_sesion'] ?? now()->toDateString(),
                'participantes' => $this->limpiarParticipantes($data['participantes'] ?? []),
                'lecciones_aprendidas' => $data['lecciones_aprendidas'] ?? null,
                'presupuesto_planeado' => $data['presupuesto_planeado'] ?? $proyecto->monto_preliminar ?? 0,
                'presupuesto_real' => $data['presupuesto_real'] ?? null,
                'recomendaciones_mejora' => $this->limpiarRecomendaciones($data['recomendaciones_mejora'] ?? []),
            ];

            // Calcular desviaciones automáticamente cuando hay datos.
            $datos = array_merge($datos, $this->calcularDesviaciones($proyecto, $datos));

            if (! $pm) {
                $pm = $proyecto->postMortem()->create($datos);

                $proyecto->recordEvent(
                    tipo: 'post_mortem_creado',
                    userId: $userId,
                    payload: ['post_mortem_id' => $pm->id],
                );
            } else {
                $pm->update($datos);
            }

            return $pm->fresh();
        });
    }

    /**
     * Calcula desviaciones de costo / tiempo / calidad como ratios:
     *   costo  = (real - planeado) / planeado     (positivo = sobreejecución)
     *   tiempo = (fin_real - fin_planeado_dias) / dias_planeados
     *   calidad = (libro%/100) — proxy del cumplimiento del dossier
     *
     * Retorna sólo las claves para las que pudo calcular.
     */
    public function calcularDesviaciones(Proyecto $proyecto, array $datos): array
    {
        $out = [];

        $planeado = (float) ($datos['presupuesto_planeado'] ?? 0);
        $real = (float) ($datos['presupuesto_real'] ?? 0);
        if ($planeado > 0 && $real > 0) {
            $out['desviaciones_costo'] = round(($real - $planeado) / $planeado, 4);
        }

        if ($proyecto->fecha_inicio_planeada && $proyecto->fecha_fin_planeada) {
            $diasPlan = $proyecto->fecha_inicio_planeada->diffInDays($proyecto->fecha_fin_planeada);
            $eventoCierre = $proyecto->eventos()->where('tipo', 'proyecto_cerrado')->latest()->first();
            $cierreFecha = $eventoCierre?->created_at ?? now();

            if ($diasPlan > 0) {
                $diasReales = $proyecto->fecha_inicio_planeada->diffInDays($cierreFecha);
                $out['desviaciones_tiempo'] = round(($diasReales - $diasPlan) / $diasPlan, 4);
            }
        }

        $libro = $proyecto->libro;
        if ($libro) {
            $out['desviaciones_calidad'] = round((float) $libro->porcentaje_avance_global / 100, 4);
        }

        return $out;
    }

    private function limpiarParticipantes(array $items): array
    {
        return array_values(array_filter($items, fn ($i) => filled(is_array($i) ? ($i['nombre'] ?? null) : $i)));
    }

    private function limpiarRecomendaciones(array $items): array
    {
        return array_values(array_filter($items, fn ($i) => filled(is_array($i) ? ($i['texto'] ?? null) : $i)));
    }
}
