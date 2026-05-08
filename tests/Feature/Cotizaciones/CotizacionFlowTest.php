<?php

use App\Models\Proyecto;
use App\Models\User;
use App\Services\Cotizaciones\CotizacionService;
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

    $this->ic = User::factory()->create();
    $this->ic->assignRole('ingeniero_costos');

    $this->gp = User::factory()->create();
    $this->gp->assignRole('gerente_proyectos');

    $this->proyecto = Proyecto::factory()->create([
        'estado' => 'cotizando',
        'gerente_proyectos_id' => $this->gp->id,
        'monto_preliminar' => 0,
    ]);
});

it('crear borrador genera cotización v1 con factores por defecto', function () {
    $this->actingAs($this->ic)
        ->post(route('cotizaciones.store', $this->proyecto))
        ->assertRedirect();

    $cot = $this->proyecto->cotizaciones()->first();

    expect($cot)->not->toBeNull()
        ->and($cot->version)->toBe(1)
        ->and($cot->status)->toBe('borrador')
        ->and((float) $cot->factor_indirectos)->toBe(0.18)
        ->and((float) $cot->factor_admin)->toBe(0.08)
        ->and((float) $cot->factor_utilidad)->toBe(0.15);
});

it('nueva versión copia partidas de la versión anterior', function () {
    $service = app(CotizacionService::class);
    $v1 = $service->crearBorrador($this->proyecto, $this->ic->id);
    $v1->partidas()->create([
        'numero_partida' => 1,
        'descripcion' => 'Tee Hot Tap 30x10',
        'cantidad' => 1,
        'unidad' => 'pza',
        'costo_unitario' => 15000,
        'costo_total' => 15000,
    ]);

    $v2 = $service->crearBorrador($this->proyecto, $this->ic->id);

    expect($v2->version)->toBe(2)
        ->and($v2->partidas()->count())->toBe(1)
        ->and($v2->partidas()->first()->descripcion)->toBe('Tee Hot Tap 30x10');
});

it('agregar partida y recalcular actualiza costo_directo y precio_venta', function () {
    $cot = app(CotizacionService::class)->crearBorrador($this->proyecto, $this->ic->id);

    $this->actingAs($this->ic)
        ->post(route('cotizaciones.partidas.store', [$this->proyecto, $cot]), [
            'descripcion' => 'Tee Hot Tap fabricada',
            'cantidad' => 1,
            'unidad' => 'pza',
            'costo_unitario' => 10000,
        ])->assertRedirect();

    $cot->refresh();

    // 10000 * 1.18 * 1.08 * 1.15 = 14,655.60
    expect((float) $cot->costo_directo)->toBe(10000.0)
        ->and((float) $cot->precio_venta_calculado)->toBe(14655.60)
        ->and((float) $cot->precio_venta_final)->toBe(14655.60);
});

it('validación rechaza partidas con cantidad o costo negativo', function () {
    $cot = app(CotizacionService::class)->crearBorrador($this->proyecto, $this->ic->id);

    $this->actingAs($this->ic)
        ->from(route('cotizaciones.edit', [$this->proyecto, $cot]))
        ->post(route('cotizaciones.partidas.store', [$this->proyecto, $cot]), [
            'descripcion' => 'Algo',
            'cantidad' => -5,
            'costo_unitario' => 100,
        ])
        ->assertSessionHasErrors(['cantidad']);

    expect($cot->partidas()->count())->toBe(0);
});

it('eliminar partida recalcula la cotización', function () {
    $service = app(CotizacionService::class);
    $cot = $service->crearBorrador($this->proyecto, $this->ic->id);
    $partida = $cot->partidas()->create([
        'numero_partida' => 1, 'descripcion' => 'X', 'cantidad' => 1, 'unidad' => 'pza',
        'costo_unitario' => 5000, 'costo_total' => 5000,
    ]);
    $service->recalcular($cot);

    expect((float) $cot->fresh()->costo_directo)->toBe(5000.0);

    $this->actingAs($this->ic)
        ->delete(route('cotizaciones.partidas.destroy', [$this->proyecto, $cot, $partida]))
        ->assertRedirect();

    expect((float) $cot->fresh()->costo_directo)->toBe(0.0);
});

it('emitir cotización transiciona proyecto cotizando→cotizado y sella monto', function () {
    $service = app(CotizacionService::class);
    $cot = $service->crearBorrador($this->proyecto, $this->ic->id);
    $cot->partidas()->create([
        'numero_partida' => 1, 'descripcion' => 'X', 'cantidad' => 1, 'unidad' => 'pza',
        'costo_unitario' => 50000, 'costo_total' => 50000,
    ]);

    $this->actingAs($this->gp)
        ->post(route('cotizaciones.emitir', [$this->proyecto, $cot]))
        ->assertRedirect();

    $cot->refresh();
    $this->proyecto->refresh();

    expect($cot->status)->toBe('emitida')
        ->and($cot->fecha_emision)->not->toBeNull()
        ->and($this->proyecto->estado)->toBe('cotizado')
        ->and((float) $this->proyecto->monto_preliminar)->toBe((float) $cot->precio_venta_final)
        ->and($this->proyecto->eventos()->where('tipo', 'cotizacion_emitida')->exists())->toBeTrue();
});

it('emitir sin partidas falla con validación', function () {
    $cot = app(CotizacionService::class)->crearBorrador($this->proyecto, $this->ic->id);

    $this->actingAs($this->gp)
        ->from(route('cotizaciones.edit', [$this->proyecto, $cot]))
        ->post(route('cotizaciones.emitir', [$this->proyecto, $cot]))
        ->assertSessionHasErrors(['emitir']);

    expect($cot->fresh()->status)->toBe('borrador');
});

it('cotización emitida no permite editar partidas', function () {
    $service = app(CotizacionService::class);
    $cot = $service->crearBorrador($this->proyecto, $this->ic->id);
    $cot->partidas()->create([
        'numero_partida' => 1, 'descripcion' => 'X', 'cantidad' => 1, 'unidad' => 'pza',
        'costo_unitario' => 100, 'costo_total' => 100,
    ]);
    $service->emitir($cot, $this->ic->id);

    $this->actingAs($this->ic)
        ->post(route('cotizaciones.partidas.store', [$this->proyecto, $cot]), [
            'descripcion' => 'Otra', 'cantidad' => 1, 'costo_unitario' => 100,
        ])
        ->assertForbidden();
});

it('actualizar factores recalcula precio_venta', function () {
    $service = app(CotizacionService::class);
    $cot = $service->crearBorrador($this->proyecto, $this->ic->id);
    $cot->partidas()->create([
        'numero_partida' => 1, 'descripcion' => 'X', 'cantidad' => 1, 'unidad' => 'pza',
        'costo_unitario' => 1000, 'costo_total' => 1000,
    ]);

    $this->actingAs($this->ic)
        ->patch(route('cotizaciones.update', [$this->proyecto, $cot]), [
            'moneda' => 'USD',
            'factor_indirectos' => 0.10,
            'factor_admin' => 0.05,
            'factor_utilidad' => 0.20,
            'precio_venta_final' => 0,
            'observaciones' => 'Test',
        ])
        ->assertRedirect();

    $cot->refresh();

    // 1000 * 1.10 * 1.05 * 1.20 = 1386
    expect((float) $cot->factor_indirectos)->toBe(0.10)
        ->and((float) $cot->precio_venta_calculado)->toBe(1386.0);
});

it('precio_venta_final override no se sobreescribe en recalcular', function () {
    $service = app(CotizacionService::class);
    $cot = $service->crearBorrador($this->proyecto, $this->ic->id);
    $cot->partidas()->create([
        'numero_partida' => 1, 'descripcion' => 'X', 'cantidad' => 1, 'unidad' => 'pza',
        'costo_unitario' => 1000, 'costo_total' => 1000,
    ]);

    // override manual a 2000
    $this->actingAs($this->ic)
        ->patch(route('cotizaciones.update', [$this->proyecto, $cot]), [
            'moneda' => 'USD',
            'factor_indirectos' => 0.18,
            'factor_admin' => 0.08,
            'factor_utilidad' => 0.15,
            'precio_venta_final' => 2000,
        ]);

    $cot->refresh();

    expect((float) $cot->precio_venta_final)->toBe(2000.0)
        // El margen se recalcula sobre el precio override
        ->and((float) $cot->margen_neto)->toBeGreaterThan(0);
});

it('listar cotizaciones requiere autenticación', function () {
    $this->get(route('cotizaciones.index', $this->proyecto))->assertRedirect('/login');
});

it('PDF download endpoint regenera si el archivo no existe', function () {
    $service = app(CotizacionService::class);
    $cot = $service->crearBorrador($this->proyecto, $this->ic->id);
    $cot->partidas()->create([
        'numero_partida' => 1, 'descripcion' => 'X', 'cantidad' => 1, 'unidad' => 'pza',
        'costo_unitario' => 100, 'costo_total' => 100,
    ]);
    $service->emitir($cot, $this->ic->id);

    $this->actingAs($this->ic)
        ->get(route('cotizaciones.pdf', [$this->proyecto, $cot]))
        ->assertOk()
        ->assertHeader('content-type', 'application/pdf');
});
