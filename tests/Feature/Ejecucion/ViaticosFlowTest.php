<?php

use App\Models\Proyecto;
use App\Models\SolicitudViaticos;
use App\Models\User;
use App\Services\Ejecucion\ViaticosService;
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

    $this->gp = User::factory()->create();
    $this->gp->assignRole('gerente_proyectos');
    $this->servgrales = User::factory()->create();
    $this->servgrales->assignRole('serv_generales');
    $this->dg = User::factory()->create();
    $this->dg->assignRole('direccion_general');

    $this->proyecto = Proyecto::factory()->create([
        'estado' => 'en_ejecucion',
        'gerente_proyectos_id' => $this->gp->id,
    ]);
});

function nuevaSolicitud(Proyecto $proyecto, User $solicitante): SolicitudViaticos
{
    return app(ViaticosService::class)->crear($proyecto, $solicitante->id, [
        'periodo_inicio' => '2026-05-01',
        'periodo_fin' => '2026-05-05',
        'justificacion' => 'Movilización a sitio.',
        'personal' => [['user_id' => $solicitante->id, 'dias' => 5]],
        'partidas' => [
            ['concepto' => 'hospedaje', 'monto_estimado' => 5000],
            ['concepto' => 'alimentos', 'monto_estimado' => 2500],
        ],
    ]);
}

it('crear solicitud guarda personal y partidas en borrador', function () {
    $this->actingAs($this->gp)
        ->post(route('viaticos.store', $this->proyecto), [
            'periodo_inicio' => '2026-05-01',
            'periodo_fin' => '2026-05-05',
            'personal' => [['user_id' => $this->gp->id, 'dias' => 5]],
            'partidas' => [['concepto' => 'hospedaje', 'monto_estimado' => 5000]],
        ])
        ->assertRedirect();

    $s = $this->proyecto->solicitudesViaticos()->first();
    expect($s->status)->toBe('borrador')
        ->and($s->personal()->count())->toBe(1)
        ->and($s->partidas()->count())->toBe(1);
});

it('flujo completo borrador → servgrales → direccion → aprobada', function () {
    $s = nuevaSolicitud($this->proyecto, $this->gp);

    $this->actingAs($this->gp)
        ->post(route('viaticos.emitir', [$this->proyecto, $s]))
        ->assertRedirect();
    expect($s->fresh()->status)->toBe('pendiente_servgrales');

    $this->actingAs($this->servgrales)
        ->post(route('viaticos.aprobar.servgrales', [$this->proyecto, $s]))
        ->assertRedirect();
    expect($s->fresh()->status)->toBe('pendiente_direccion')
        ->and($s->fresh()->aprobador_serv_grales_id)->toBe($this->servgrales->id);

    $this->actingAs($this->dg)
        ->post(route('viaticos.aprobar.direccion', [$this->proyecto, $s]))
        ->assertRedirect();
    expect($s->fresh()->status)->toBe('aprobada')
        ->and($s->fresh()->aprobado_at)->not->toBeNull()
        ->and($this->proyecto->fresh()->eventos()->where('tipo', 'viaticos_aprobados')->exists())->toBeTrue();
});

it('emitir sin personal o sin partidas falla', function () {
    $service = app(ViaticosService::class);
    $sinPersonal = $service->crear($this->proyecto, $this->gp->id, [
        'periodo_inicio' => '2026-05-01',
        'periodo_fin' => '2026-05-05',
        'partidas' => [['concepto' => 'hospedaje', 'monto_estimado' => 100]],
    ]);

    $this->actingAs($this->gp)
        ->from(route('viaticos.show', [$this->proyecto, $sinPersonal]))
        ->post(route('viaticos.emitir', [$this->proyecto, $sinPersonal]))
        ->assertSessionHasErrors(['viaticos']);
});

it('rechazar requiere razón mínima', function () {
    $s = nuevaSolicitud($this->proyecto, $this->gp);
    app(ViaticosService::class)->emitir($s, $this->gp->id);

    $this->actingAs($this->servgrales)
        ->from(route('viaticos.show', [$this->proyecto, $s]))
        ->post(route('viaticos.rechazar', [$this->proyecto, $s]), ['razon' => 'no'])
        ->assertSessionHasErrors(['razon']);

    $this->actingAs($this->servgrales)
        ->post(route('viaticos.rechazar', [$this->proyecto, $s]), [
            'razon' => 'Presupuesto excedido para el periodo.',
        ])
        ->assertRedirect();

    expect($s->fresh()->status)->toBe('rechazada');
});

it('registrar montos reales solo funciona en aprobada', function () {
    $s = nuevaSolicitud($this->proyecto, $this->gp);
    $service = app(ViaticosService::class);
    $service->emitir($s, $this->gp->id);
    $service->aprobarServGrales($s->fresh(), $this->servgrales->id);
    $service->aprobarDireccion($s->fresh(), $this->dg->id);

    $partida = $s->fresh()->partidas()->first();

    $this->actingAs($this->gp)
        ->patch(route('viaticos.reales', [$this->proyecto, $s]), [
            'partidas' => [$partida->id => 4800],
        ])
        ->assertRedirect();

    expect((float) $partida->fresh()->monto_real)->toBe(4800.0);
});

it('PDF endpoint devuelve archivo', function () {
    $s = nuevaSolicitud($this->proyecto, $this->gp);

    $this->actingAs($this->gp)
        ->get(route('viaticos.pdf', [$this->proyecto, $s]))
        ->assertOk()
        ->assertHeader('content-type', 'application/pdf');
});
