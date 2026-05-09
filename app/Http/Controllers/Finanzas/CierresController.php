<?php

namespace App\Http\Controllers\Finanzas;

use App\Http\Controllers\Controller;
use App\Models\CierreMensual;
use App\Services\Finanzas\CierreApprovalService;
use App\Services\Finanzas\CierrePdfGenerator;
use App\Services\Finanzas\GeneradorCierreGerencialService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\Rule;
use RuntimeException;
use Symfony\Component\HttpFoundation\StreamedResponse;

class CierresController extends Controller
{
    public function index()
    {
        $cierres = CierreMensual::with(['generadoPor:id,name', 'aprobadoPor:id,name'])
            ->orderByDesc('año')
            ->orderByDesc('mes')
            ->orderBy('tipo')
            ->paginate(30);

        return view('finanzas.cierres.index', [
            'cierres' => $cierres,
            'mesActual' => (int) now()->subMonth()->month,
            'añoActual' => (int) now()->subMonth()->year,
        ]);
    }

    public function store(Request $request, GeneradorCierreGerencialService $generador): RedirectResponse
    {
        $data = $request->validate([
            'mes' => ['required', 'integer', 'between:1,12'],
            'año' => ['required', 'integer', 'between:2020,2050'],
            'tipo' => ['required', Rule::in(['contable_sat', 'gerencial_avance'])],
        ]);

        $cierre = $data['tipo'] === 'gerencial_avance'
            ? $generador->generar($data['año'], $data['mes'], $request->user()->id)
            : $generador->generarSat($data['año'], $data['mes'], $request->user()->id);

        return redirect()
            ->route('finanzas.cierres.show', $cierre)
            ->with('status', "Cierre {$data['tipo']} {$data['mes']}/{$data['año']} generado.");
    }

    public function show(CierreMensual $cierre)
    {
        $cierre->load([
            'secciones.lineas.proyecto:id,cp_numero,dn_numero,cliente_id',
            'secciones.lineas.proyecto.cliente:id,razon_social,alias_3letras',
            'generadoPor:id,name',
            'aprobadoPor:id,name',
        ]);

        return view('finanzas.cierres.show', [
            'cierre' => $cierre,
        ]);
    }

    public function regenerar(Request $request, CierreMensual $cierre, GeneradorCierreGerencialService $generador): RedirectResponse
    {
        if ($cierre->status !== 'borrador') {
            return back()->withErrors(['cierre' => 'Solo se puede regenerar un cierre en borrador.']);
        }

        if ($cierre->tipo === 'gerencial_avance') {
            $generador->generar($cierre->año, $cierre->mes, $request->user()->id);
        } else {
            $generador->generarSat($cierre->año, $cierre->mes, $request->user()->id);
        }

        return back()->with('status', 'Cierre regenerado con datos actuales.');
    }

    public function aprobar(Request $request, CierreMensual $cierre, CierreApprovalService $service): RedirectResponse
    {
        try {
            $service->aprobar($cierre, $request->user()->id);
        } catch (RuntimeException $e) {
            return back()->withErrors(['cierre' => $e->getMessage()]);
        }

        return back()->with('status', 'Cierre aprobado.');
    }

    public function cerrar(Request $request, CierreMensual $cierre, CierreApprovalService $service): RedirectResponse
    {
        try {
            $service->cerrar($cierre, $request->user()->id);
        } catch (RuntimeException $e) {
            return back()->withErrors(['cierre' => $e->getMessage()]);
        }

        return back()->with('status', 'Cierre marcado como cerrado (irreversible).');
    }

    public function regresar(Request $request, CierreMensual $cierre, CierreApprovalService $service): RedirectResponse
    {
        $data = $request->validate([
            'razon' => ['required', 'string', 'min:5', 'max:500'],
        ]);

        try {
            $service->regresarBorrador($cierre, $request->user()->id, $data['razon']);
        } catch (RuntimeException $e) {
            return back()->withErrors(['cierre' => $e->getMessage()]);
        }

        return back()->with('status', 'Cierre regresado a borrador.');
    }

    public function pdf(CierreMensual $cierre, CierrePdfGenerator $generator): StreamedResponse
    {
        if (! $cierre->pdf_path || ! Storage::disk('local')->exists($cierre->pdf_path)) {
            $generator->generar($cierre);
            $cierre->refresh();
        }

        return Storage::disk('local')->download(
            $cierre->pdf_path,
            sprintf('Cierre-%s-%02d-%d.pdf', $cierre->tipo, $cierre->mes, $cierre->año),
        );
    }
}
