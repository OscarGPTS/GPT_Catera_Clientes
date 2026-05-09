<?php

namespace Database\Seeders;

use App\Models\CuentaBancaria;
use App\Models\EstadoCuenta;
use App\Models\Proyecto;
use App\Models\User;
use App\Services\Finanzas\CierreApprovalService;
use App\Services\Finanzas\ConciliacionService;
use App\Services\Finanzas\GeneradorCierreGerencialService;
use Illuminate\Database\Seeder;
use Illuminate\Support\Carbon;

class FinanzasSeeder extends Seeder
{
    public function run(): void
    {
        if (CuentaBancaria::count() > 0) {
            $this->command?->warn('FinanzasSeeder: ya hay cuentas, salteando.');

            return;
        }

        $bbva = CuentaBancaria::create([
            'banco' => 'bbva',
            'alias' => 'Operaciones MXN',
            'numero_cuenta_enmascarado' => '****1234',
            'clabe_enmascarada' => '012180001234567890',
            'moneda' => 'MXN',
            'activa' => true,
        ]);

        $banorte = CuentaBancaria::create([
            'banco' => 'banorte',
            'alias' => 'Dólares USD',
            'numero_cuenta_enmascarado' => '****9876',
            'clabe_enmascarada' => '072180009876543210',
            'moneda' => 'USD',
            'activa' => true,
        ]);

        // Sembrar un estado de cuenta del mes anterior para BBVA
        $mes = (int) now()->subMonth()->month;
        $año = (int) now()->subMonth()->year;
        $estado = EstadoCuenta::create([
            'cuenta_id' => $bbva->id,
            'mes' => $mes,
            'año' => $año,
            'parseado_at' => now(),
            'total_movimientos' => 0,
        ]);

        $proyectos = Proyecto::whereIn('estado', ['adjudicado_firmado', 'en_ejecucion', 'en_cierre', 'cerrado'])->get();

        $movimientos = [
            [
                'fecha' => Carbon::create($año, $mes, 5)->toDateString(),
                'descripcion' => 'TRANSFERENCIA RECIBIDA SEDENA OC-2026-001',
                'monto' => 500000,
                'tipo' => 'ingreso',
            ],
            [
                'fecha' => Carbon::create($año, $mes, 8)->toDateString(),
                'descripcion' => 'PAGO PROVEEDOR ACME STEEL',
                'monto' => 125000,
                'tipo' => 'egreso',
            ],
            [
                'fecha' => Carbon::create($año, $mes, 12)->toDateString(),
                'descripcion' => 'TRANSFERENCIA PEMEX REF '.($proyectos->first()?->cp_numero ?? 'CP-001/26'),
                'monto' => 250000,
                'tipo' => 'ingreso',
            ],
            [
                'fecha' => Carbon::create($año, $mes, 15)->toDateString(),
                'descripcion' => 'NÓMINA QUINCENA',
                'monto' => 380000,
                'tipo' => 'egreso',
            ],
            [
                'fecha' => Carbon::create($año, $mes, 18)->toDateString(),
                'descripcion' => 'CFE PAGO REF ANTICIPO',
                'monto' => 175000,
                'tipo' => 'ingreso',
            ],
            [
                'fecha' => Carbon::create($año, $mes, 22)->toDateString(),
                'descripcion' => 'PAGO RENTA OFICINA',
                'monto' => 45000,
                'tipo' => 'egreso',
            ],
            [
                'fecha' => Carbon::create($año, $mes, 26)->toDateString(),
                'descripcion' => 'TRANSFERENCIA FRM FERMACA',
                'monto' => 425000,
                'tipo' => 'ingreso',
            ],
            [
                'fecha' => Carbon::create($año, $mes, 28)->toDateString(),
                'descripcion' => 'COMISIONES BANCARIAS',
                'monto' => 1250,
                'tipo' => 'egreso',
            ],
        ];

        foreach ($movimientos as $mov) {
            $estado->movimientos()->create($mov);
        }

        $estado->update(['total_movimientos' => count($movimientos)]);

        // Auto-conciliar 2 movimientos altos usando el ConciliacionService (registra audit)
        $servicio = app(ConciliacionService::class);
        $admin = User::where('email', 'admin@gptservices.com')->first()
            ?? User::role('cfo')->first()
            ?? User::first();

        if ($admin) {
            // Movimiento que menciona CP-XXX/AA → conciliar con su match top
            foreach ($estado->movimientos as $m) {
                $sugerencias = $servicio->sugerencias($m, 1);
                $top = $sugerencias->first();
                if ($top && $top['score'] >= 100) {
                    $servicio->conciliar(
                        $m,
                        $top['proyecto']->id,
                        'F-'.str_pad((string) $m->id, 4, '0', STR_PAD_LEFT),
                        $admin->id,
                    );
                }
            }
        }

        // M12 · Generar cierre gerencial del mes anterior + cierre SAT, dejándolos en distintos status.
        if ($admin) {
            $generador = app(GeneradorCierreGerencialService::class);
            $approval = app(CierreApprovalService::class);

            $cierreGerencial = $generador->generar($año, $mes, $admin->id);
            $approval->aprobar($cierreGerencial, $admin->id);

            $cierreSat = $generador->generarSat($año, $mes, $admin->id);
            // SAT queda en borrador para que el usuario pueda ver el flujo completo.
            unset($cierreSat);
        }

        $this->command?->info("FinanzasSeeder: 2 cuentas + 1 estado con {$estado->total_movimientos} movimientos + 2 cierres mensuales.");
    }
}
