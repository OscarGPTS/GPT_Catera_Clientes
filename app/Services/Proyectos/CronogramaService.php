<?php

namespace App\Services\Proyectos;

use App\Models\Cronograma;
use App\Models\CronogramaActividad;
use App\Models\Proyecto;
use Illuminate\Support\Facades\DB;
use RuntimeException;

class CronogramaService
{
    public function crearVersion(Proyecto $proyecto, int $userId): Cronograma
    {
        if (! in_array($proyecto->estado, ['adjudicado_firmado', 'en_ejecucion', 'en_cierre', 'cerrado'], true)) {
            throw new RuntimeException("El cronograma se levanta entre adjudicado_firmado y cerrado. Estado actual: {$proyecto->estado}.");
        }

        return DB::transaction(function () use ($proyecto, $userId) {
            $ultima = $proyecto->cronogramas()->orderByDesc('version')->first();
            $version = ($ultima?->version ?? 0) + 1;

            $cron = Cronograma::create([
                'proyecto_id' => $proyecto->id,
                'version' => $version,
                'generado_por_id' => $userId,
                'fecha_inicio' => $proyecto->fecha_inicio_planeada ?? now()->toDateString(),
                'fecha_fin' => $proyecto->fecha_fin_planeada ?? now()->addMonths(3)->toDateString(),
            ]);

            // Si hay versión anterior, copiar las actividades como punto de partida.
            if ($ultima) {
                foreach ($ultima->actividades as $act) {
                    $cron->actividades()->create([
                        'codigo' => $act->codigo,
                        'nombre' => $act->nombre,
                        'parent_id' => null, // Hierarchy se reconstruye después.
                        'fecha_inicio_planeada' => $act->fecha_inicio_planeada,
                        'fecha_fin_planeada' => $act->fecha_fin_planeada,
                        'porcentaje_avance' => 0,
                        'predecesoras' => $act->predecesoras,
                    ]);
                }
            }

            $proyecto->recordEvent(
                tipo: 'cronograma_version_creada',
                userId: $userId,
                payload: ['version' => $version, 'cronograma_id' => $cron->id],
            );

            return $cron->fresh('actividades');
        });
    }

    public function agregarActividad(Cronograma $cronograma, array $data): CronogramaActividad
    {
        $actividad = $cronograma->actividades()->create([
            'codigo' => $data['codigo'] ?? null,
            'nombre' => $data['nombre'],
            'parent_id' => $data['parent_id'] ?? null,
            'fecha_inicio_planeada' => $data['fecha_inicio_planeada'] ?? null,
            'fecha_fin_planeada' => $data['fecha_fin_planeada'] ?? null,
            'porcentaje_avance' => $data['porcentaje_avance'] ?? 0,
            'predecesoras' => $data['predecesoras'] ?? null,
        ]);

        $this->recalcularExtremos($cronograma);

        return $actividad;
    }

    public function actualizarActividad(CronogramaActividad $actividad, array $data): CronogramaActividad
    {
        $actividad->update(array_filter([
            'codigo' => $data['codigo'] ?? null,
            'nombre' => $data['nombre'] ?? null,
            'fecha_inicio_planeada' => $data['fecha_inicio_planeada'] ?? null,
            'fecha_fin_planeada' => $data['fecha_fin_planeada'] ?? null,
            'fecha_inicio_real' => $data['fecha_inicio_real'] ?? null,
            'fecha_fin_real' => $data['fecha_fin_real'] ?? null,
            'porcentaje_avance' => $data['porcentaje_avance'] ?? null,
            'predecesoras' => $data['predecesoras'] ?? null,
        ], fn ($v) => $v !== null));

        $this->recalcularExtremos($actividad->cronograma);

        return $actividad->fresh();
    }

    public function eliminarActividad(CronogramaActividad $actividad): void
    {
        $cronograma = $actividad->cronograma;
        $actividad->delete();
        $this->recalcularExtremos($cronograma);
    }

    public function avanceGlobal(Cronograma $cronograma): float
    {
        $actividades = $cronograma->actividades()->whereNull('parent_id')->get();
        if ($actividades->isEmpty()) {
            return 0.0;
        }

        $suma = $actividades->sum(fn ($a) => (float) $a->porcentaje_avance);

        return round($suma / $actividades->count(), 2);
    }

    private function recalcularExtremos(Cronograma $cronograma): void
    {
        $min = $cronograma->actividades()->min('fecha_inicio_planeada');
        $max = $cronograma->actividades()->max('fecha_fin_planeada');

        $cronograma->update([
            'fecha_inicio' => $min,
            'fecha_fin' => $max,
        ]);
    }
}
