<?php

namespace App\Http\Controllers\Procura;

use App\Http\Controllers\Controller;
use App\Models\Proyecto;
use App\Models\SolicitudInterna;
use App\Models\User;
use App\Services\Procura\SolicitudInternaService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use RuntimeException;

class SolicitudesInternasController extends Controller
{
    public function index(Proyecto $proyecto)
    {
        $proyecto->load(['cliente:id,razon_social,alias_3letras']);
        $solicitudes = $proyecto->solicitudesInternas()
            ->with(['solicitante:id,name', 'asignado:id,name', 'items'])
            ->get();

        return view('solicitudes.index', [
            'proyecto' => $proyecto,
            'solicitudes' => $solicitudes,
            'asignables' => User::role(['compras', 'ingenieria_diseño'])->orderBy('name')->get(['id', 'name']),
        ]);
    }

    public function store(Request $request, Proyecto $proyecto, SolicitudInternaService $service): RedirectResponse
    {
        $data = $request->validate([
            'tipo' => ['required', Rule::in(['requisicion_compras', 'orden_trabajo_ingenieria'])],
            'codigo_formato' => ['nullable', 'string', 'max:60'],
            'asignado_id' => ['nullable', 'exists:users,id'],
            'fecha_respuesta_requerida' => ['nullable', 'date'],
            'descripcion' => ['nullable', 'string', 'max:2000'],
            'items' => ['nullable', 'array'],
            'items.*.descripcion' => ['nullable', 'string', 'max:500'],
            'items.*.cantidad' => ['nullable', 'numeric', 'min:0.0001'],
            'items.*.unidad' => ['nullable', 'string', 'max:20'],
            'items.*.especificacion' => ['nullable', 'string', 'max:500'],
        ]);

        $solicitud = $service->crear($proyecto, $request->user()->id, $data);

        return redirect()
            ->route('solicitudes.show', [$proyecto, $solicitud])
            ->with('status', 'Solicitud creada en borrador.');
    }

    public function show(Proyecto $proyecto, SolicitudInterna $solicitud)
    {
        $this->ensureBelongsTo($proyecto, $solicitud);

        $solicitud->load(['items', 'solicitante:id,name', 'asignado:id,name']);

        return view('solicitudes.show', [
            'proyecto' => $proyecto,
            'solicitud' => $solicitud,
            'asignables' => User::role(['compras', 'ingenieria_diseño'])->orderBy('name')->get(['id', 'name']),
        ]);
    }

    public function emitir(Request $request, Proyecto $proyecto, SolicitudInterna $solicitud, SolicitudInternaService $service): RedirectResponse
    {
        $this->ensureBelongsTo($proyecto, $solicitud);

        try {
            $service->emitir($solicitud, $request->user()->id);
        } catch (RuntimeException $e) {
            return back()->withErrors(['solicitud' => $e->getMessage()]);
        }

        return back()->with('status', 'Solicitud emitida.');
    }

    public function tomar(Request $request, Proyecto $proyecto, SolicitudInterna $solicitud, SolicitudInternaService $service): RedirectResponse
    {
        $this->ensureBelongsTo($proyecto, $solicitud);

        try {
            $service->tomar($solicitud, $request->user()->id);
        } catch (RuntimeException $e) {
            return back()->withErrors(['solicitud' => $e->getMessage()]);
        }

        return back()->with('status', 'Solicitud tomada.');
    }

    public function responder(Request $request, Proyecto $proyecto, SolicitudInterna $solicitud, SolicitudInternaService $service): RedirectResponse
    {
        $this->ensureBelongsTo($proyecto, $solicitud);

        $data = $request->validate([
            'respuesta' => ['required', 'string', 'min:5', 'max:2000'],
        ]);

        try {
            $service->responder($solicitud, $request->user()->id, $data['respuesta']);
        } catch (RuntimeException $e) {
            return back()->withErrors(['solicitud' => $e->getMessage()]);
        }

        return back()->with('status', 'Solicitud respondida.');
    }

    public function cancelar(Request $request, Proyecto $proyecto, SolicitudInterna $solicitud, SolicitudInternaService $service): RedirectResponse
    {
        $this->ensureBelongsTo($proyecto, $solicitud);

        $data = $request->validate([
            'razon' => ['nullable', 'string', 'max:500'],
        ]);

        try {
            $service->cancelar($solicitud, $request->user()->id, $data['razon'] ?? null);
        } catch (RuntimeException $e) {
            return back()->withErrors(['solicitud' => $e->getMessage()]);
        }

        return back()->with('status', 'Solicitud cancelada.');
    }

    private function ensureBelongsTo(Proyecto $proyecto, SolicitudInterna $solicitud): void
    {
        if ($solicitud->proyecto_id !== $proyecto->id) {
            abort(404);
        }
    }
}
