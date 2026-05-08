<?php

namespace App\Http\Controllers\Adjudicacion;

use App\Http\Controllers\Controller;
use App\Models\Proyecto;
use App\Services\Proyectos\AdjudicacionService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use RuntimeException;

class AdjudicacionController extends Controller
{
    public function presentar(Request $request, Proyecto $proyecto, AdjudicacionService $service): RedirectResponse
    {
        $data = $request->validate([
            'comentario' => ['nullable', 'string', 'max:2000'],
        ]);

        try {
            $service->presentar($proyecto, $request->user()->id, $data['comentario'] ?? null);
        } catch (RuntimeException $e) {
            return back()->withErrors(['transicion' => $e->getMessage()]);
        }

        return back()->with('status', "{$proyecto->cp_numero} marcado como presentado al cliente.");
    }

    public function registrarAdjudicacion(Request $request, Proyecto $proyecto, AdjudicacionService $service): RedirectResponse
    {
        $data = $request->validate([
            'oc_referencia' => ['required', 'string', 'max:80'],
            'oc_fecha' => ['nullable', 'date'],
            'oc_monto' => ['nullable', 'numeric', 'min:0'],
            'oc_moneda' => ['nullable', 'in:USD,MXN,EUR'],
            'comentario' => ['nullable', 'string', 'max:2000'],
        ]);

        try {
            $service->registrarAdjudicacion($proyecto, $request->user()->id, $data);
        } catch (RuntimeException $e) {
            return back()->withErrors(['transicion' => $e->getMessage()]);
        }

        return back()->with('status', "{$proyecto->cp_numero} adjudicado (pendiente firma de OC).");
    }

    public function firmarOc(Request $request, Proyecto $proyecto, AdjudicacionService $service): RedirectResponse
    {
        $data = $request->validate([
            'oc_firma_fecha' => ['required', 'date'],
            'oc_path' => ['nullable', 'string', 'max:255'],
            'comentario' => ['nullable', 'string', 'max:2000'],
        ]);

        try {
            $service->firmarOc($proyecto, $request->user()->id, $data);
        } catch (RuntimeException $e) {
            return back()->withErrors(['transicion' => $e->getMessage()]);
        }

        return back()->with('status', "OC firmada · DN asignado: {$proyecto->fresh()->dn_numero}.");
    }

    public function iniciarEjecucion(Request $request, Proyecto $proyecto, AdjudicacionService $service): RedirectResponse
    {
        try {
            $service->iniciarEjecucion($proyecto, $request->user()->id);
        } catch (RuntimeException $e) {
            return back()->withErrors(['transicion' => $e->getMessage()]);
        }

        return back()->with('status', "{$proyecto->cp_numero} / {$proyecto->fresh()->dn_numero} en ejecución. Libro de proyecto abierto.");
    }

    public function marcarPerdido(Request $request, Proyecto $proyecto, AdjudicacionService $service): RedirectResponse
    {
        $data = $request->validate([
            'razon' => ['required', 'string', 'min:5', 'max:500'],
        ]);

        try {
            $service->marcarPerdido($proyecto, $request->user()->id, $data['razon']);
        } catch (RuntimeException $e) {
            return back()->withErrors(['transicion' => $e->getMessage()]);
        }

        return back()->with('status', "{$proyecto->cp_numero} marcado como perdido.");
    }
}
