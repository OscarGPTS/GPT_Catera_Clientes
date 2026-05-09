<?php

use App\Models\CierreMensual;
use App\Models\FinanzasAudit;
use App\Models\Proyecto;
use App\Models\User;
use App\Services\Finanzas\CierreApprovalService;
use App\Services\Finanzas\GeneradorCierreGerencialService;
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

    // Sembrar algunos proyectos en distintos estados para tener líneas
    Proyecto::factory()->create([
        'estado' => 'cotizando', 'monto_preliminar' => 100_000,
        'fecha_inicio_planeada' => now()->subDays(60), 'fecha_fin_planeada' => now()->addDays(60),
    ]);
    Proyecto::factory()->create([
        'estado' => 'adjudicado_firmado', 'monto_preliminar' => 500_000,
        'fecha_inicio_planeada' => now()->subDays(60), 'fecha_fin_planeada' => now()->addDays(60),
    ]);
});

it('generar cierre gerencial crea 3 secciones', function () {
    $año = (int) now()->subMonth()->year;
    $mes = (int) now()->subMonth()->month;

    $cierre = app(GeneradorCierreGerencialService::class)->generar($año, $mes, $this->cfo->id);

    expect($cierre->tipo)->toBe('gerencial_avance')
        ->and($cierre->status)->toBe('borrador')
        ->and($cierre->secciones)->toHaveCount(3)
        ->and($cierre->secciones->pluck('codigo')->all())->toContain('sat_base', 'devengado', 'pipeline_ponderado');
});

it('generar cierre SAT solo crea 1 sección', function () {
    $año = (int) now()->subMonth()->year;
    $mes = (int) now()->subMonth()->month;

    $cierre = app(GeneradorCierreGerencialService::class)->generarSat($año, $mes, $this->cfo->id);

    expect($cierre->tipo)->toBe('contable_sat')
        ->and($cierre->secciones)->toHaveCount(1)
        ->and($cierre->secciones->first()->codigo)->toBe('sat_base');
});

it('regenerar reemplaza líneas anteriores', function () {
    $año = (int) now()->subMonth()->year;
    $mes = (int) now()->subMonth()->month;

    $servicio = app(GeneradorCierreGerencialService::class);
    $cierre1 = $servicio->generar($año, $mes, $this->cfo->id);
    $idsPrimeros = $cierre1->secciones->pluck('id')->all();

    $cierre2 = $servicio->generar($año, $mes, $this->cfo->id);

    expect($cierre1->id)->toBe($cierre2->id)
        ->and(array_intersect($idsPrimeros, $cierre2->secciones->pluck('id')->all()))->toBe([]);
});

it('aprobar transiciona borrador→aprobado y registra audit', function () {
    $cierre = app(GeneradorCierreGerencialService::class)->generar(2026, 5, $this->cfo->id);

    app(CierreApprovalService::class)->aprobar($cierre, $this->cfo->id);

    expect($cierre->fresh()->status)->toBe('aprobado')
        ->and($cierre->fresh()->aprobado_por_id)->toBe($this->cfo->id)
        ->and(FinanzasAudit::where('action', 'cierre_aprobado')->exists())->toBeTrue();
});

it('aprobar rechaza si ya está aprobado', function () {
    $cierre = app(GeneradorCierreGerencialService::class)->generar(2026, 5, $this->cfo->id);
    $service = app(CierreApprovalService::class);
    $service->aprobar($cierre, $this->cfo->id);

    expect(fn () => $service->aprobar($cierre->fresh(), $this->cfo->id))->toThrow(RuntimeException::class);
});

it('cerrar requiere aprobado previo', function () {
    $cierre = app(GeneradorCierreGerencialService::class)->generar(2026, 5, $this->cfo->id);
    $service = app(CierreApprovalService::class);

    expect(fn () => $service->cerrar($cierre, $this->cfo->id))->toThrow(RuntimeException::class);

    $service->aprobar($cierre, $this->cfo->id);
    $service->cerrar($cierre->fresh(), $this->cfo->id);

    expect($cierre->fresh()->status)->toBe('cerrado')
        ->and(FinanzasAudit::where('action', 'cierre_cerrado')->exists())->toBeTrue();
});

it('regresar a borrador sólo funciona si está aprobado, no cerrado', function () {
    $cierre = app(GeneradorCierreGerencialService::class)->generar(2026, 5, $this->cfo->id);
    $service = app(CierreApprovalService::class);
    $service->aprobar($cierre, $this->cfo->id);

    $service->regresarBorrador($cierre->fresh(), $this->cfo->id, 'Error en datos');

    expect($cierre->fresh()->status)->toBe('borrador')
        ->and($cierre->fresh()->aprobado_por_id)->toBeNull();

    // Cerrado no se puede regresar
    $service->aprobar($cierre->fresh(), $this->cfo->id);
    $service->cerrar($cierre->fresh(), $this->cfo->id);

    expect(fn () => $service->regresarBorrador($cierre->fresh(), $this->cfo->id, 'tarde'))
        ->toThrow(RuntimeException::class);
});

it('endpoint store crea cierre y redirige', function () {
    $this->actingAs($this->cfo)
        ->post(route('finanzas.cierres.store'), [
            'mes' => 5,
            'año' => 2026,
            'tipo' => 'gerencial_avance',
        ])
        ->assertRedirect();

    expect(CierreMensual::count())->toBe(1);
});

it('endpoint store rechaza tipo inválido', function () {
    $this->actingAs($this->cfo)
        ->from(route('finanzas.cierres'))
        ->post(route('finanzas.cierres.store'), [
            'mes' => 5,
            'año' => 2026,
            'tipo' => 'inexistente',
        ])
        ->assertSessionHasErrors(['tipo']);
});

it('PDF se genera al solicitarlo', function () {
    $cierre = app(GeneradorCierreGerencialService::class)->generar(2026, 5, $this->cfo->id);

    $this->actingAs($this->cfo)
        ->get(route('finanzas.cierres.pdf', $cierre))
        ->assertOk()
        ->assertHeader('content-type', 'application/pdf');
});

it('cierres requiere rol financiero', function () {
    $comercial = User::factory()->create();
    $comercial->assignRole('comercial');

    $this->actingAs($comercial)
        ->get(route('finanzas.cierres'))
        ->assertForbidden();
});

it('show renderiza con totales por sección', function () {
    $cierre = app(GeneradorCierreGerencialService::class)->generar(2026, 5, $this->cfo->id);

    $this->actingAs($this->cfo)
        ->get(route('finanzas.cierres.show', $cierre))
        ->assertOk()
        ->assertSee('Pipeline ponderado')
        ->assertSee('Devengado');
});
