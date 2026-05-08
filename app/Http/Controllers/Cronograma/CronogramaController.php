<?php

namespace App\Http\Controllers\Cronograma;

use App\Http\Controllers\Controller;
use App\Models\Cronograma;
use App\Models\CronogramaActividad;
use App\Models\Proyecto;
use App\Services\Proyectos\CronogramaService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use RuntimeException;

class CronogramaController extends Controller
{
    public function index(Proyecto $proyecto)
    {
        $proyecto->load(['cliente:id,razon_social,alias_3letras', 'sublinea:id,codigo,nombre']);
        $cronogramas = $proyecto->cronogramas()->with('generadoPor:id,name')->get();

        return view('cronogramas.index', [
            'proyecto' => $proyecto,
            'cronogramas' => $cronogramas,
        ]);
    }

    public function store(Request $request, Proyecto $proyecto, CronogramaService $service): RedirectResponse
    {
        try {
            $cron = $service->crearVersion($proyecto, $request->user()->id);
        } catch (RuntimeException $e) {
            return back()->withErrors(['cronograma' => $e->getMessage()]);
        }

        return redirect()
            ->route('cronogramas.show', [$proyecto, $cron])
            ->with('status', "Cronograma v{$cron->version} creado.");
    }

    public function show(Proyecto $proyecto, Cronograma $cronograma, CronogramaService $service)
    {
        $this->ensureBelongsTo($proyecto, $cronograma);

        $cronograma->load(['actividades.parent']);
        $proyecto->load(['cliente', 'sublinea']);

        return view('cronogramas.show', [
            'proyecto' => $proyecto,
            'cronograma' => $cronograma,
            'avanceGlobal' => $service->avanceGlobal($cronograma),
        ]);
    }

    public function storeActividad(Request $request, Proyecto $proyecto, Cronograma $cronograma, CronogramaService $service): RedirectResponse
    {
        $this->ensureBelongsTo($proyecto, $cronograma);

        $data = $request->validate([
            'codigo' => ['nullable', 'string', 'max:30'],
            'nombre' => ['required', 'string', 'min:3', 'max:200'],
            'parent_id' => ['nullable', 'exists:cronograma_actividades,id'],
            'fecha_inicio_planeada' => ['nullable', 'date'],
            'fecha_fin_planeada' => ['nullable', 'date', 'after_or_equal:fecha_inicio_planeada'],
            'porcentaje_avance' => ['nullable', 'numeric', 'min:0', 'max:100'],
            'predecesoras' => ['nullable', 'array'],
        ]);

        $service->agregarActividad($cronograma, $data);

        return back()->with('status', 'Actividad agregada.');
    }

    public function updateActividad(Request $request, Proyecto $proyecto, Cronograma $cronograma, CronogramaActividad $actividad, CronogramaService $service): RedirectResponse
    {
        $this->ensureBelongsTo($proyecto, $cronograma);
        if ($actividad->cronograma_id !== $cronograma->id) {
            abort(404);
        }

        $data = $request->validate([
            'codigo' => ['nullable', 'string', 'max:30'],
            'nombre' => ['nullable', 'string', 'min:3', 'max:200'],
            'fecha_inicio_planeada' => ['nullable', 'date'],
            'fecha_fin_planeada' => ['nullable', 'date', 'after_or_equal:fecha_inicio_planeada'],
            'fecha_inicio_real' => ['nullable', 'date'],
            'fecha_fin_real' => ['nullable', 'date', 'after_or_equal:fecha_inicio_real'],
            'porcentaje_avance' => ['nullable', 'numeric', 'min:0', 'max:100'],
        ]);

        $service->actualizarActividad($actividad, $data);

        return back()->with('status', 'Actividad actualizada.');
    }

    public function destroyActividad(Proyecto $proyecto, Cronograma $cronograma, CronogramaActividad $actividad, CronogramaService $service): RedirectResponse
    {
        $this->ensureBelongsTo($proyecto, $cronograma);
        if ($actividad->cronograma_id !== $cronograma->id) {
            abort(404);
        }

        $service->eliminarActividad($actividad);

        return back()->with('status', 'Actividad eliminada.');
    }

    private function ensureBelongsTo(Proyecto $proyecto, Cronograma $cronograma): void
    {
        if ($cronograma->proyecto_id !== $proyecto->id) {
            abort(404);
        }
    }
}
