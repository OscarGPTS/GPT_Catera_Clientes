<?php

namespace App\Http\Controllers\Finanzas;

use App\Http\Controllers\Controller;
use App\Models\CuentaBancaria;
use App\Models\EstadoCuenta;
use App\Services\Finanzas\EstadoCuentaService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use RuntimeException;

class EstadosCuentaController extends Controller
{
    public function index(CuentaBancaria $cuenta)
    {
        $cuenta->load(['estadosCuenta' => fn ($q) => $q->orderByDesc('año')->orderByDesc('mes')]);

        return view('finanzas.estados.index', [
            'cuenta' => $cuenta,
            'estados' => $cuenta->estadosCuenta,
        ]);
    }

    public function store(Request $request, CuentaBancaria $cuenta, EstadoCuentaService $service): RedirectResponse
    {
        $data = $request->validate([
            'mes' => ['required', 'integer', 'between:1,12'],
            'año' => ['required', 'integer', 'between:2020,2050'],
            'archivo' => ['required', 'file', 'mimes:csv,txt', 'max:10240'],
        ]);

        try {
            $estado = $service->importar($cuenta, $data['mes'], $data['año'], $request->file('archivo'), $request->user()->id);
        } catch (RuntimeException $e) {
            return back()->withErrors(['archivo' => $e->getMessage()]);
        }

        return redirect()
            ->route('finanzas.estados.show', [$cuenta, $estado])
            ->with('status', "Estado {$estado->mes}/{$estado->año} importado: {$estado->total_movimientos} movimientos.");
    }

    public function show(CuentaBancaria $cuenta, EstadoCuenta $estado)
    {
        $this->ensureBelongsTo($cuenta, $estado);

        $estado->load(['movimientos' => fn ($q) => $q->orderBy('fecha')->orderBy('id'), 'movimientos.proyecto:id,cp_numero,dn_numero']);

        return view('finanzas.estados.show', [
            'cuenta' => $cuenta,
            'estado' => $estado,
            'totales' => [
                'ingresos' => $estado->movimientos->where('tipo', 'ingreso')->sum('monto'),
                'egresos' => $estado->movimientos->where('tipo', 'egreso')->sum('monto'),
                'no_conciliados' => $estado->movimientos->whereNull('conciliado_at')->count(),
            ],
        ]);
    }

    private function ensureBelongsTo(CuentaBancaria $cuenta, EstadoCuenta $estado): void
    {
        if ($estado->cuenta_id !== $cuenta->id) {
            abort(404);
        }
    }
}
