<?php

use App\Models\Proyecto;
use App\Models\User;
use App\Services\Minutas\MinutaEntregaService;
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

    $this->director = User::factory()->create();
    $this->director->assignRole('direccion_general');

    $this->gp = User::factory()->create();
    $this->gp->assignRole('gerente_proyectos');

    $this->go = User::factory()->create();
    $this->go->assignRole('gerente_operaciones');

    $this->ip = User::factory()->create();
    $this->ip->assignRole('ingeniero_proyectos');

    $this->proyecto = Proyecto::factory()->create([
        'estado' => 'adjudicado_firmado',
        'dn_numero' => 'DN-001/26',
        'director_dn_id' => $this->director->id,
        'gerente_proyectos_id' => $this->gp->id,
        'gerente_operaciones_id' => $this->go->id,
        'ingeniero_proyectos_id' => $this->ip->id,
    ]);
});

it('abrir minuta crea la primera vez con participantes auto-sembrados', function () {
    $this->actingAs($this->gp)
        ->get(route('minutas.show', $this->proyecto))
        ->assertOk()
        ->assertSee('Minuta de Entrega');

    $minuta = $this->proyecto->minutaEntrega;

    expect($minuta)->not->toBeNull()
        ->and($minuta->status)->toBe('borrador')
        ->and($minuta->participantes->count())->toBe(4); // director, gp, go, ip
});

it('abrir minuta es idempotente', function () {
    $first = app(MinutaEntregaService::class)->crearOSeleccionar($this->proyecto, $this->gp->id);
    $second = app(MinutaEntregaService::class)->crearOSeleccionar($this->proyecto, $this->gp->id);

    expect($second->id)->toBe($first->id)
        ->and($this->proyecto->fresh()->minutaEntrega->id)->toBe($first->id);
});

it('actualizar minuta guarda orden_del_dia y acuerdos como JSON', function () {
    $minuta = app(MinutaEntregaService::class)->crearOSeleccionar($this->proyecto, $this->gp->id);

    $this->actingAs($this->gp)
        ->patch(route('minutas.update', $this->proyecto), [
            'fecha_reunion' => '2026-05-10',
            'modalidad' => 'presencial',
            'orden_del_dia' => ['Entrega CP', 'Asignación operativa'],
            'acuerdos' => [
                ['texto' => 'GO recibe DN', 'responsable' => 'GO', 'fecha' => '2026-05-12'],
            ],
        ])
        ->assertRedirect();

    $minuta->refresh();

    expect($minuta->modalidad)->toBe('presencial')
        ->and($minuta->orden_del_dia)->toBe(['Entrega CP', 'Asignación operativa'])
        ->and($minuta->acuerdos[0]['responsable'])->toBe('GO');
});

it('agregar y quitar participante actualiza la lista', function () {
    $minuta = app(MinutaEntregaService::class)->crearOSeleccionar($this->proyecto, $this->gp->id);
    $extra = User::factory()->create();

    $this->actingAs($this->gp)
        ->post(route('minutas.participantes.add', $this->proyecto), [
            'user_id' => $extra->id,
            'rol_en_minuta' => 'Observador',
        ])
        ->assertRedirect();

    expect($minuta->fresh()->participantes()->count())->toBe(5);

    $this->actingAs($this->gp)
        ->delete(route('minutas.participantes.remove', [$this->proyecto, $extra->id]))
        ->assertRedirect();

    expect($minuta->fresh()->participantes()->count())->toBe(4);
});

it('firmar individualmente no sella la minuta hasta que todos firmen', function () {
    $minuta = app(MinutaEntregaService::class)->crearOSeleccionar($this->proyecto, $this->gp->id);

    $this->actingAs($this->gp)
        ->post(route('minutas.firmar', $this->proyecto))
        ->assertRedirect();

    expect($minuta->fresh()->status)->toBe('borrador');
});

it('firma del último participante sella la minuta y registra evento', function () {
    $minuta = app(MinutaEntregaService::class)->crearOSeleccionar($this->proyecto, $this->gp->id);

    foreach ($minuta->participantes as $p) {
        $this->actingAs($p->user)->post(route('minutas.firmar', $this->proyecto));
    }

    $minuta->refresh();

    expect($minuta->status)->toBe('firmada')
        ->and($minuta->firmado_at)->not->toBeNull()
        ->and($this->proyecto->fresh()->eventos()->where('tipo', 'minuta_firmada')->exists())->toBeTrue();
});

it('un usuario que no es participante no puede firmar', function () {
    $intruso = User::factory()->create();
    app(MinutaEntregaService::class)->crearOSeleccionar($this->proyecto, $this->gp->id);

    $this->actingAs($intruso)
        ->from(route('minutas.show', $this->proyecto))
        ->post(route('minutas.firmar', $this->proyecto))
        ->assertSessionHasErrors(['firma']);
});

it('minuta firmada bloquea edición', function () {
    $service = app(MinutaEntregaService::class);
    $minuta = $service->crearOSeleccionar($this->proyecto, $this->gp->id);

    foreach ($minuta->participantes as $p) {
        $service->firmarPorUsuario($minuta->fresh(), $p->user_id);
    }

    $this->actingAs($this->gp)
        ->from(route('minutas.show', $this->proyecto))
        ->patch(route('minutas.update', $this->proyecto), [
            'fecha_reunion' => '2026-05-15',
            'modalidad' => 'mixta',
        ])
        ->assertSessionHasErrors(['minuta']);
});

it('PDF se genera al firmar y se puede descargar', function () {
    $service = app(MinutaEntregaService::class);
    $minuta = $service->crearOSeleccionar($this->proyecto, $this->gp->id);

    foreach ($minuta->participantes as $p) {
        $this->actingAs($p->user)->post(route('minutas.firmar', $this->proyecto));
    }

    $this->actingAs($this->gp)
        ->get(route('minutas.pdf', $this->proyecto))
        ->assertOk()
        ->assertHeader('content-type', 'application/pdf');
});
