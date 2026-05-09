<?php

namespace App\Http\Controllers\Finanzas;

use App\Http\Controllers\Controller;
use App\Models\CuentaBancaria;
use App\Services\Finanzas\FinanzasAuditService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class CuentasBancariasController extends Controller
{
    public function index()
    {
        $cuentas = CuentaBancaria::withCount(['estadosCuenta'])->orderBy('banco')->get();

        return view('finanzas.cuentas.index', [
            'cuentas' => $cuentas,
        ]);
    }

    public function store(Request $request, FinanzasAuditService $audit): RedirectResponse
    {
        $data = $request->validate([
            'banco' => ['required', Rule::in(['bbva', 'banorte', 'banamex', 'santander', 'hsbc', 'otro'])],
            'alias' => ['nullable', 'string', 'max:60'],
            'numero_cuenta_enmascarado' => ['required', 'string', 'max:30'],
            'clabe_enmascarada' => ['nullable', 'string', 'max:30'],
            'moneda' => ['required', Rule::in(['MXN', 'USD', 'EUR'])],
            'activa' => ['nullable', 'boolean'],
        ]);
        $data['activa'] = $request->boolean('activa', true);

        $cuenta = CuentaBancaria::create($data);
        $audit->registrar('cuenta_creada', $cuenta, $data);

        return back()->with('status', "Cuenta {$cuenta->banco} creada.");
    }

    public function update(Request $request, CuentaBancaria $cuenta, FinanzasAuditService $audit): RedirectResponse
    {
        $data = $request->validate([
            'alias' => ['nullable', 'string', 'max:60'],
            'numero_cuenta_enmascarado' => ['nullable', 'string', 'max:30'],
            'clabe_enmascarada' => ['nullable', 'string', 'max:30'],
            'moneda' => ['nullable', Rule::in(['MXN', 'USD', 'EUR'])],
            'activa' => ['nullable', 'boolean'],
        ]);

        $cuenta->update($data);
        $audit->registrar('cuenta_actualizada', $cuenta, $data);

        return back()->with('status', 'Cuenta actualizada.');
    }
}
