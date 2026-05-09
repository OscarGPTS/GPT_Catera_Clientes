<?php

namespace App\Http\Controllers\Ejecucion;

use App\Http\Controllers\Controller;
use App\Models\Proyecto;
use App\Models\SolicitudViaticos;
use App\Models\User;
use App\Services\Ejecucion\ViaticosPdfGenerator;
use App\Services\Ejecucion\ViaticosService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\Rule;
use RuntimeException;
use Symfony\Component\HttpFoundation\StreamedResponse;

class ViaticosController extends Controller
{
    public function index(Proyecto $proyecto)
    {
        $proyecto->load(['cliente:id,razon_social,alias_3letras']);
        $solicitudes = $proyecto->solicitudesViaticos()->with(['solicitante:id,name', 'personal'])->get();

        return view('viaticos.index', [
            'proyecto' => $proyecto,
            'solicitudes' => $solicitudes,
            'usuarios' => User::orderBy('name')->get(['id', 'name']),
        ]);
    }

    public function store(Request $request, Proyecto $proyecto, ViaticosService $service): RedirectResponse
    {
        $data = $request->validate([
            'periodo_inicio' => ['required', 'date'],
            'periodo_fin' => ['required', 'date', 'after_or_equal:periodo_inicio'],
            'justificacion' => ['nullable', 'string', 'max:2000'],
            'personal' => ['nullable', 'array'],
            'personal.*.user_id' => ['nullable', 'exists:users,id'],
            'personal.*.dias' => ['nullable', 'integer', 'min:1', 'max:365'],
            'partidas' => ['nullable', 'array'],
            'partidas.*.concepto' => ['nullable', Rule::in(['hospedaje', 'alimentos', 'transporte', 'otros'])],
            'partidas.*.monto_estimado' => ['nullable', 'numeric', 'min:0'],
            'partidas.*.observaciones' => ['nullable', 'string', 'max:300'],
        ]);

        $solicitud = $service->crear($proyecto, $request->user()->id, $data);

        return redirect()->route('viaticos.show', [$proyecto, $solicitud])->with('status', 'Solicitud de viáticos creada en borrador.');
    }

    public function show(Proyecto $proyecto, SolicitudViaticos $solicitud)
    {
        $this->ensureBelongsTo($proyecto, $solicitud);

        $solicitud->load(['personal.user', 'partidas', 'solicitante:id,name', 'aprobadorServGrales:id,name', 'aprobadorDireccion:id,name']);

        return view('viaticos.show', [
            'proyecto' => $proyecto,
            'solicitud' => $solicitud,
            'usuarios' => User::orderBy('name')->get(['id', 'name']),
        ]);
    }

    public function emitir(Request $request, Proyecto $proyecto, SolicitudViaticos $solicitud, ViaticosService $service): RedirectResponse
    {
        $this->ensureBelongsTo($proyecto, $solicitud);

        try {
            $service->emitir($solicitud, $request->user()->id);
        } catch (RuntimeException $e) {
            return back()->withErrors(['viaticos' => $e->getMessage()]);
        }

        return back()->with('status', 'Solicitud emitida a Servicios Generales.');
    }

    public function aprobarServGrales(Request $request, Proyecto $proyecto, SolicitudViaticos $solicitud, ViaticosService $service): RedirectResponse
    {
        $this->ensureBelongsTo($proyecto, $solicitud);

        try {
            $service->aprobarServGrales($solicitud, $request->user()->id);
        } catch (RuntimeException $e) {
            return back()->withErrors(['viaticos' => $e->getMessage()]);
        }

        return back()->with('status', 'Aprobación de Servicios Generales registrada.');
    }

    public function aprobarDireccion(Request $request, Proyecto $proyecto, SolicitudViaticos $solicitud, ViaticosService $service): RedirectResponse
    {
        $this->ensureBelongsTo($proyecto, $solicitud);

        try {
            $service->aprobarDireccion($solicitud, $request->user()->id);
        } catch (RuntimeException $e) {
            return back()->withErrors(['viaticos' => $e->getMessage()]);
        }

        return back()->with('status', 'Solicitud aprobada por Dirección.');
    }

    public function rechazar(Request $request, Proyecto $proyecto, SolicitudViaticos $solicitud, ViaticosService $service): RedirectResponse
    {
        $this->ensureBelongsTo($proyecto, $solicitud);

        $data = $request->validate(['razon' => ['required', 'string', 'min:5', 'max:500']]);

        try {
            $service->rechazar($solicitud, $request->user()->id, $data['razon']);
        } catch (RuntimeException $e) {
            return back()->withErrors(['viaticos' => $e->getMessage()]);
        }

        return back()->with('status', 'Solicitud rechazada.');
    }

    public function registrarReales(Request $request, Proyecto $proyecto, SolicitudViaticos $solicitud, ViaticosService $service): RedirectResponse
    {
        $this->ensureBelongsTo($proyecto, $solicitud);

        $data = $request->validate([
            'partidas' => ['required', 'array'],
            'partidas.*' => ['nullable', 'numeric', 'min:0'],
        ]);

        try {
            $service->registrarMontoReal($solicitud, $data['partidas']);
        } catch (RuntimeException $e) {
            return back()->withErrors(['viaticos' => $e->getMessage()]);
        }

        return back()->with('status', 'Montos reales registrados.');
    }

    public function pdf(Proyecto $proyecto, SolicitudViaticos $solicitud, ViaticosPdfGenerator $generator): StreamedResponse
    {
        $this->ensureBelongsTo($proyecto, $solicitud);

        if (! $solicitud->pdf_path || ! Storage::disk('local')->exists($solicitud->pdf_path)) {
            $generator->generar($solicitud);
            $solicitud->refresh();
        }

        return Storage::disk('local')->download($solicitud->pdf_path, "Viaticos-{$proyecto->cp_numero}-{$solicitud->id}.pdf");
    }

    private function ensureBelongsTo(Proyecto $proyecto, SolicitudViaticos $solicitud): void
    {
        if ($solicitud->proyecto_id !== $proyecto->id) {
            abort(404);
        }
    }
}
