<?php

use App\Models\CuentaBancaria;
use App\Models\EstadoCuenta;
use App\Models\FinanzasAudit;
use App\Models\Proyecto;
use App\Models\User;
use App\Services\Cotizaciones\CotizacionService;
use App\Services\Finanzas\ConciliacionService;
use Database\Seeders\ComercialCatalogosSeeder;
use Database\Seeders\RhRoleMappingSeeder;
use Database\Seeders\RolesPermissionsSeeder;
use Database\Seeders\SystemSettingsSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

beforeEach(function () {
    $this->seed([
        SystemSettingsSeeder::class,
        RolesPermissionsSeeder::class,
        RhRoleMappingSeeder::class,
        ComercialCatalogosSeeder::class,
    ]);

    $this->cfo = User::factory()->create();
    $this->cfo->assignRole('cfo');

    $this->ic = User::factory()->create();
    $this->ic->assignRole('ingeniero_costos');

    $this->cuenta = CuentaBancaria::create([
        'banco' => 'bbva',
        'numero_cuenta_enmascarado' => '****1234',
        'moneda' => 'MXN',
    ]);

    $this->estado = EstadoCuenta::create([
        'cuenta_id' => $this->cuenta->id,
        'mes' => 5,
        'año' => 2026,
        'parseado_at' => now(),
        'total_movimientos' => 0,
    ]);

    $this->proyecto = Proyecto::factory()->create([
        'estado' => 'cotizando',
        'cp_numero' => 'CP-001/26',
        'gerente_proyectos_id' => $this->cfo->id,
    ]);
});

it('sugerencias incluyen el proyecto cuyo CP aparece en descripción', function () {
    $mov = $this->estado->movimientos()->create([
        'fecha' => '2026-05-10',
        'descripcion' => 'TRANSFERENCIA REF CP-001/26',
        'monto' => 50000,
        'tipo' => 'ingreso',
    ]);

    $sug = app(ConciliacionService::class)->sugerencias($mov);

    expect($sug)->toHaveCount(1)
        ->and($sug->first()['proyecto']->id)->toBe($this->proyecto->id)
        ->and($sug->first()['score'])->toBeGreaterThanOrEqual(100);
});

it('sugerencias incluyen alias del cliente', function () {
    $cliente = $this->proyecto->cliente;
    $mov = $this->estado->movimientos()->create([
        'fecha' => '2026-05-10',
        'descripcion' => "PAGO {$cliente->alias_3letras} CONCEPTO X",
        'monto' => 50000,
        'tipo' => 'ingreso',
    ]);

    $sug = app(ConciliacionService::class)->sugerencias($mov);

    expect($sug->first()['score'])->toBeGreaterThanOrEqual(60);
});

it('sugerencias incluyen match por monto cuando hay cotización emitida ±2%', function () {
    $cot = app(CotizacionService::class)->crearBorrador($this->proyecto, $this->ic->id);
    $cot->partidas()->create([
        'numero_partida' => 1, 'descripcion' => 'X', 'cantidad' => 1, 'unidad' => 'pza',
        'costo_unitario' => 100000, 'costo_total' => 100000,
    ]);
    app(CotizacionService::class)->emitir($cot, $this->ic->id);

    $mov = $this->estado->movimientos()->create([
        'fecha' => '2026-05-10',
        'descripcion' => 'PAGO sin referencia',
        'monto' => (float) $this->proyecto->fresh()->monto_preliminar,
        'tipo' => 'ingreso',
    ]);

    $sug = app(ConciliacionService::class)->sugerencias($mov);

    expect($sug->first()['score'])->toBeGreaterThanOrEqual(40);
});

it('conciliar registra audit con payload', function () {
    $mov = $this->estado->movimientos()->create([
        'fecha' => '2026-05-10',
        'descripcion' => 'X',
        'monto' => 100,
        'tipo' => 'ingreso',
    ]);

    $this->actingAs($this->cfo)
        ->post(route('finanzas.conciliacion.conciliar', [$this->cuenta, $this->estado, $mov]), [
            'proyecto_id' => $this->proyecto->id,
            'factura' => 'F-9999',
        ])
        ->assertRedirect();

    expect($mov->fresh()->conciliado_at)->not->toBeNull()
        ->and($mov->fresh()->conciliado_con_proyecto_id)->toBe($this->proyecto->id);

    $audit = FinanzasAudit::where('action', 'movimiento_conciliado')->first();
    expect($audit)->not->toBeNull()
        ->and($audit->user_id)->toBe($this->cfo->id)
        ->and($audit->payload['factura'])->toBe('F-9999');
});

it('conciliar sin proyecto ni factura falla', function () {
    $mov = $this->estado->movimientos()->create([
        'fecha' => '2026-05-10', 'descripcion' => 'X', 'monto' => 100, 'tipo' => 'ingreso',
    ]);

    $this->actingAs($this->cfo)
        ->from(route('finanzas.conciliacion.show', [$this->cuenta, $this->estado, $mov]))
        ->post(route('finanzas.conciliacion.conciliar', [$this->cuenta, $this->estado, $mov]), [])
        ->assertSessionHasErrors(['conciliacion']);
});

it('desconciliar limpia campos y registra audit', function () {
    $mov = $this->estado->movimientos()->create([
        'fecha' => '2026-05-10', 'descripcion' => 'X', 'monto' => 100, 'tipo' => 'ingreso',
        'conciliado_con_proyecto_id' => $this->proyecto->id,
        'conciliado_at' => now(),
    ]);

    $this->actingAs($this->cfo)
        ->delete(route('finanzas.conciliacion.desconciliar', [$this->cuenta, $this->estado, $mov]))
        ->assertRedirect();

    expect($mov->fresh()->conciliado_at)->toBeNull()
        ->and($mov->fresh()->conciliado_con_proyecto_id)->toBeNull();

    expect(FinanzasAudit::where('action', 'movimiento_desconciliado')->exists())->toBeTrue();
});

it('cuentas index requiere rol financiero (middleware finanzas)', function () {
    $randomUser = User::factory()->create();
    $randomUser->assignRole('comercial');

    $this->actingAs($randomUser)
        ->get(route('finanzas.cuentas'))
        ->assertForbidden();

    $this->actingAs($this->cfo)
        ->get(route('finanzas.cuentas'))
        ->assertOk();
});

it('store cuenta crea registro con audit', function () {
    $this->actingAs($this->cfo)
        ->post(route('finanzas.cuentas.store'), [
            'banco' => 'santander',
            'alias' => 'Test',
            'numero_cuenta_enmascarado' => '****0000',
            'moneda' => 'MXN',
        ])
        ->assertRedirect();

    expect(CuentaBancaria::where('banco', 'santander')->exists())->toBeTrue()
        ->and(FinanzasAudit::where('action', 'cuenta_creada')->exists())->toBeTrue();
});

it('show conciliación muestra sugerencias rankeadas', function () {
    $mov = $this->estado->movimientos()->create([
        'fecha' => '2026-05-10',
        'descripcion' => 'TRANSFERENCIA REF CP-001/26',
        'monto' => 50000,
        'tipo' => 'ingreso',
    ]);

    $this->actingAs($this->cfo)
        ->get(route('finanzas.conciliacion.show', [$this->cuenta, $this->estado, $mov]))
        ->assertOk()
        ->assertSee('CP-001/26')
        ->assertSee('Sugerencias automáticas');
});
