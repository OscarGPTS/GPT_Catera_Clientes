<?php

namespace App\Http\Controllers\Ejecucion;

use App\Http\Controllers\Controller;
use App\Models\BitacoraDiaria;
use App\Models\Proyecto;
use App\Services\Ejecucion\BitacoraPdfGenerator;
use App\Services\Ejecucion\BitacoraService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use RuntimeException;
use Symfony\Component\HttpFoundation\StreamedResponse;

class BitacoraController extends Controller
{
    public function index(Proyecto $proyecto)
    {
        $proyecto->load(['cliente:id,razon_social,alias_3letras']);
        $bitacoras = $proyecto->bitacoras()->with('cargadoPor:id,name')->paginate(30)->withQueryString();

        return view('bitacoras.index', [
            'proyecto' => $proyecto,
            'bitacoras' => $bitacoras,
        ]);
    }

    public function store(Request $request, Proyecto $proyecto, BitacoraService $service): RedirectResponse
    {
        $data = $request->validate([
            'fecha' => ['required', 'date'],
            'relacion_actividades' => ['required', 'string', 'min:10'],
            'personal_gpt' => ['nullable', 'array'],
            'personal_gpt.*.nombre' => ['nullable', 'string', 'max:120'],
            'personal_gpt.*.rol' => ['nullable', 'string', 'max:80'],
            'equipos_en_sitio' => ['nullable', 'array'],
            'equipos_en_sitio.*.nombre' => ['nullable', 'string', 'max:120'],
            'equipos_en_sitio.*.cantidad' => ['nullable', 'string', 'max:30'],
            'proveedores_subcontratistas' => ['nullable', 'array'],
            'proveedores_subcontratistas.*.nombre' => ['nullable', 'string', 'max:120'],
        ]);

        try {
            $b = $service->crear($proyecto, $request->user()->id, $data);
        } catch (RuntimeException $e) {
            return back()->withErrors(['bitacora' => $e->getMessage()]);
        }

        return redirect()->route('bitacoras.show', [$proyecto, $b])->with('status', 'Bitácora cargada.');
    }

    public function show(Proyecto $proyecto, BitacoraDiaria $bitacora)
    {
        $this->ensureBelongsTo($proyecto, $bitacora);

        $bitacora->load(['cargadoPor:id,name']);
        $proyecto->load(['cliente', 'sublinea']);

        return view('bitacoras.show', [
            'proyecto' => $proyecto,
            'bitacora' => $bitacora,
        ]);
    }

    public function update(Request $request, Proyecto $proyecto, BitacoraDiaria $bitacora, BitacoraService $service): RedirectResponse
    {
        $this->ensureBelongsTo($proyecto, $bitacora);

        $data = $request->validate([
            'relacion_actividades' => ['required', 'string', 'min:10'],
            'personal_gpt' => ['nullable', 'array'],
            'equipos_en_sitio' => ['nullable', 'array'],
            'proveedores_subcontratistas' => ['nullable', 'array'],
        ]);

        try {
            $service->actualizar($bitacora, $data);
        } catch (RuntimeException $e) {
            return back()->withErrors(['bitacora' => $e->getMessage()]);
        }

        return back()->with('status', 'Bitácora actualizada.');
    }

    public function vobo(Request $request, Proyecto $proyecto, BitacoraDiaria $bitacora, BitacoraService $service): RedirectResponse
    {
        $this->ensureBelongsTo($proyecto, $bitacora);

        $data = $request->validate([
            'vobo_cliente_nombre' => ['required', 'string', 'max:120'],
            'vobo_cliente_organizacion' => ['nullable', 'string', 'max:120'],
            'vobo_cliente_fecha' => ['nullable', 'date'],
        ]);

        $service->registrarVoBoCliente($bitacora, $data, $request->user()->id);

        return back()->with('status', 'VoBo del cliente registrado.');
    }

    public function destroy(Proyecto $proyecto, BitacoraDiaria $bitacora, BitacoraService $service): RedirectResponse
    {
        $this->ensureBelongsTo($proyecto, $bitacora);

        try {
            $service->eliminar($bitacora);
        } catch (RuntimeException $e) {
            return back()->withErrors(['bitacora' => $e->getMessage()]);
        }

        return redirect()->route('bitacoras.index', $proyecto)->with('status', 'Bitácora eliminada.');
    }

    public function pdf(Proyecto $proyecto, BitacoraDiaria $bitacora, BitacoraPdfGenerator $generator): StreamedResponse
    {
        $this->ensureBelongsTo($proyecto, $bitacora);

        $path = $generator->generar($bitacora);

        return Storage::disk('local')->download($path, "Bitacora-{$proyecto->cp_numero}-{$bitacora->fecha->format('Y-m-d')}.pdf");
    }

    private function ensureBelongsTo(Proyecto $proyecto, BitacoraDiaria $bitacora): void
    {
        if ($bitacora->proyecto_id !== $proyecto->id) {
            abort(404);
        }
    }
}
