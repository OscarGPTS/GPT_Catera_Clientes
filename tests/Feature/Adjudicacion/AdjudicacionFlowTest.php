<?php

use App\Models\LibroProyecto;
use App\Models\Proyecto;
use App\Models\SystemSetting;
use App\Models\User;
use App\Services\Cotizaciones\CotizacionService;
use App\Services\Proyectos\AdjudicacionService;
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

    $this->dgUser = User::factory()->create();
    $this->dgUser->assignRole('direccion_general');

    $this->gp = User::factory()->create();
    $this->gp->assignRole('gerente_proyectos');

    $this->ic = User::factory()->create();
    $this->ic->assignRole('ingeniero_costos');
});

function proyectoCotizado($gp, $ic, $director): Proyecto
{
    $p = Proyecto::factory()->create([
        'estado' => 'cotizando',
        'director_dn_id' => $director->id,
        'gerente_proyectos_id' => $gp->id,
        'ingeniero_costos_id' => $ic->id,
        'monto_preliminar' => 0,
    ]);

    $cot = app(CotizacionService::class)->crearBorrador($p, $ic->id);
    $cot->partidas()->create([
        'numero_partida' => 1, 'descripcion' => 'X', 'cantidad' => 1, 'unidad' => 'pza',
        'costo_unitario' => 50000, 'costo_total' => 50000,
    ]);
    app(CotizacionService::class)->emitir($cot, $ic->id);

    return $p->fresh();
}

it('presentar requiere cotización emitida', function () {
    $p = Proyecto::factory()->create(['estado' => 'cotizado']);

    $this->actingAs($this->dgUser)
        ->from(route('oportunidades.show', $p))
        ->post(route('adjudicacion.presentar', $p))
        ->assertSessionHasErrors(['transicion']);

    expect($p->fresh()->estado)->toBe('cotizado');
});

it('presentar transiciona cotizado→presentado y registra evento', function () {
    $p = proyectoCotizado($this->gp, $this->ic, $this->dgUser);

    $this->actingAs($this->dgUser)
        ->post(route('adjudicacion.presentar', $p), ['comentario' => 'Enviada por correo'])
        ->assertRedirect();

    $p->refresh();

    expect($p->estado)->toBe('presentado')
        ->and($p->eventos()->where('tipo', 'cotizacion_presentada')->exists())->toBeTrue();
});

it('registrar adjudicación transiciona presentado→adjudicado_pendiente con datos OC', function () {
    $p = proyectoCotizado($this->gp, $this->ic, $this->dgUser);
    app(AdjudicacionService::class)->presentar($p, $this->dgUser->id);

    $this->actingAs($this->dgUser)
        ->post(route('adjudicacion.registrar', $p), [
            'oc_referencia' => 'OC-CFE-2026-001',
            'oc_fecha' => '2026-04-15',
            'oc_monto' => 75000,
            'oc_moneda' => 'USD',
        ])
        ->assertRedirect();

    $p->refresh();

    expect($p->estado)->toBe('adjudicado_pendiente')
        ->and($p->dn_numero)->toBeNull(); // DN se asigna al firmar OC

    $evento = $p->eventos()->where('tipo', 'adjudicacion_registrada')->first();
    expect($evento)->not->toBeNull()
        ->and($evento->payload['oc_referencia'])->toBe('OC-CFE-2026-001');
});

it('firmar OC asigna DN atómico y transiciona a adjudicado_firmado', function () {
    $p = proyectoCotizado($this->gp, $this->ic, $this->dgUser);
    app(AdjudicacionService::class)->presentar($p, $this->dgUser->id);
    app(AdjudicacionService::class)->registrarAdjudicacion($p, $this->dgUser->id, ['oc_referencia' => 'OC-X']);

    $this->actingAs($this->dgUser)
        ->post(route('adjudicacion.firmar', $p), [
            'oc_firma_fecha' => '2026-04-20',
        ])
        ->assertRedirect();

    $p->refresh();

    expect($p->estado)->toBe('adjudicado_firmado')
        ->and($p->dn_numero)->toMatch('/^DN-\d{3}\/\d{2}$/')
        ->and($p->eventos()->where('tipo', 'dn_asignado')->exists())->toBeTrue()
        ->and($p->eventos()->where('tipo', 'oc_firmada')->exists())->toBeTrue();
});

it('iniciar ejecución abre el libro y registra evento', function () {
    $p = proyectoCotizado($this->gp, $this->ic, $this->dgUser);
    app(AdjudicacionService::class)->presentar($p, $this->dgUser->id);
    app(AdjudicacionService::class)->registrarAdjudicacion($p, $this->dgUser->id, ['oc_referencia' => 'OC-X']);
    app(AdjudicacionService::class)->firmarOc($p->fresh(), $this->dgUser->id, ['oc_firma_fecha' => '2026-04-20']);

    $this->actingAs($this->dgUser)
        ->post(route('adjudicacion.iniciar', $p))
        ->assertRedirect();

    $p->refresh();

    expect($p->estado)->toBe('en_ejecucion');

    $libro = LibroProyecto::where('proyecto_id', $p->id)->first();
    expect($libro)->not->toBeNull()
        ->and($libro->secciones()->count())->toBe(10);
});

it('D10: si minuta_entrega_obligatoria=true, iniciar ejecución requiere minuta firmada', function () {
    SystemSetting::set('minuta_entrega_obligatoria', true, 'boolean');

    $p = proyectoCotizado($this->gp, $this->ic, $this->dgUser);
    $svc = app(AdjudicacionService::class);
    $svc->presentar($p, $this->dgUser->id);
    $svc->registrarAdjudicacion($p, $this->dgUser->id, ['oc_referencia' => 'OC-X']);
    $svc->firmarOc($p->fresh(), $this->dgUser->id, ['oc_firma_fecha' => '2026-04-20']);

    $this->actingAs($this->dgUser)
        ->from(route('oportunidades.show', $p))
        ->post(route('adjudicacion.iniciar', $p))
        ->assertSessionHasErrors(['transicion']);

    expect($p->fresh()->estado)->toBe('adjudicado_firmado');
});

it('marcar perdido transiciona presentado→perdido y registra razón', function () {
    $p = proyectoCotizado($this->gp, $this->ic, $this->dgUser);
    app(AdjudicacionService::class)->presentar($p, $this->dgUser->id);

    $this->actingAs($this->dgUser)
        ->post(route('adjudicacion.perdido', $p), [
            'razon' => 'Cliente eligió a otro proveedor por precio',
        ])
        ->assertRedirect();

    $p->refresh();

    expect($p->estado)->toBe('perdido')
        ->and($p->eventos()->where('tipo', 'cp_perdido')->exists())->toBeTrue();
});

it('rechaza marcar perdido si el CP ya está adjudicado', function () {
    $p = proyectoCotizado($this->gp, $this->ic, $this->dgUser);
    $svc = app(AdjudicacionService::class);
    $svc->presentar($p, $this->dgUser->id);
    $svc->registrarAdjudicacion($p, $this->dgUser->id, ['oc_referencia' => 'OC-X']);

    $this->actingAs($this->dgUser)
        ->from(route('oportunidades.show', $p))
        ->post(route('adjudicacion.perdido', $p), ['razon' => 'tarde'])
        ->assertSessionHasErrors(['transicion']);
});
