<?php

namespace App\Http\Controllers\Finanzas;

use App\Http\Controllers\Controller;
use App\Models\CuentaBancaria;
use App\Models\EstadoCuenta;
use App\Models\MovimientoBancario;
use App\Services\Finanzas\ConciliacionService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

class ConciliacionController extends Controller
{
    public function show(CuentaBancaria $cuenta, EstadoCuenta $estado, MovimientoBancario $mov, ConciliacionService $service)
    {
        $this->ensureBelongsTo($cuenta, $estado, $mov);

        $mov->load(['proyecto:id,cp_numero,dn_numero']);

        return view('finanzas.conciliacion.show', [
            'cuenta' => $cuenta,
            'estado' => $estado,
            'mov' => $mov,
            'sugerencias' => $service->sugerencias($mov),
        ]);
    }

    public function conciliar(Request $request, CuentaBancaria $cuenta, EstadoCuenta $estado, MovimientoBancario $mov, ConciliacionService $service): RedirectResponse
    {
        $this->ensureBelongsTo($cuenta, $estado, $mov);

        $data = $request->validate([
            'proyecto_id' => ['nullable', 'exists:proyectos,id'],
            'factura' => ['nullable', 'string', 'max:60'],
        ]);

        if (! filled($data['proyecto_id'] ?? null) && ! filled($data['factura'] ?? null)) {
            return back()->withErrors(['conciliacion' => 'Debes elegir proyecto o capturar factura.']);
        }

        $service->conciliar($mov, $data['proyecto_id'] ?? null, $data['factura'] ?? null, $request->user()->id);

        return redirect()->route('finanzas.estados.show', [$cuenta, $estado])->with('status', 'Movimiento conciliado.');
    }

    public function desconciliar(Request $request, CuentaBancaria $cuenta, EstadoCuenta $estado, MovimientoBancario $mov, ConciliacionService $service): RedirectResponse
    {
        $this->ensureBelongsTo($cuenta, $estado, $mov);

        $service->desconciliar($mov, $request->user()->id);

        return back()->with('status', 'Conciliación removida.');
    }

    private function ensureBelongsTo(CuentaBancaria $cuenta, EstadoCuenta $estado, MovimientoBancario $mov): void
    {
        if ($estado->cuenta_id !== $cuenta->id || $mov->estado_cuenta_id !== $estado->id) {
            abort(404);
        }
    }
}
