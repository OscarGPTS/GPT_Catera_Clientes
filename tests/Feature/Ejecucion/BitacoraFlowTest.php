<?php

use App\Models\BitacoraDiaria;
use App\Models\Proyecto;
use App\Models\User;
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

    $this->proyecto = Proyecto::factory()->create([
        'estado' => 'en_ejecucion',
        'gerente_proyectos_id' => $this->gp->id,
    ]);
});

it('crear bitácora valida fecha y descripción mínima', function () {
    $this->actingAs($this->gp)
        ->from(route('bitacoras.index', $this->proyecto))
        ->post(route('bitacoras.store', $this->proyecto), [
            'fecha' => '2026-05-10',
            'relacion_actividades' => 'corto',
        ])
        ->assertSessionHasErrors(['relacion_actividades']);
});

it('crear bitácora persiste y registra evento', function () {
    $this->actingAs($this->gp)
        ->post(route('bitacoras.store', $this->proyecto), [
            'fecha' => '2026-05-10',
            'relacion_actividades' => 'Día normal con avance del 12% del plan semanal.',
        ])
        ->assertRedirect();

    $b = $this->proyecto->bitacoras()->first();
    expect($b)->not->toBeNull()
        ->and($b->cargado_por_id)->toBe($this->gp->id)
        ->and($this->proyecto->fresh()->eventos()->where('tipo', 'bitacora_creada')->exists())->toBeTrue();
});

it('rechaza dos bitácoras en la misma fecha', function () {
    $this->actingAs($this->gp)
        ->post(route('bitacoras.store', $this->proyecto), [
            'fecha' => '2026-05-10',
            'relacion_actividades' => 'Primera bitácora del día.',
        ]);

    $this->actingAs($this->gp)
        ->from(route('bitacoras.index', $this->proyecto))
        ->post(route('bitacoras.store', $this->proyecto), [
            'fecha' => '2026-05-10',
            'relacion_actividades' => 'Segunda bitácora del día (debería fallar).',
        ])
        ->assertSessionHasErrors(['bitacora']);

    expect($this->proyecto->bitacoras()->count())->toBe(1);
});

it('detecta desviación cuando hay keywords como "retraso" o "incidente"', function () {
    $b = BitacoraDiaria::create([
        'proyecto_id' => $this->proyecto->id,
        'fecha' => '2026-05-10',
        'relacion_actividades' => 'Hubo un retraso de 4 horas por falla del equipo principal.',
        'cargado_por_id' => $this->gp->id,
    ]);

    expect($b->tieneDesviacion())->toBeTrue();

    $b2 = BitacoraDiaria::create([
        'proyecto_id' => $this->proyecto->id,
        'fecha' => '2026-05-11',
        'relacion_actividades' => 'Avance normal sin novedades.',
        'cargado_por_id' => $this->gp->id,
    ]);

    expect($b2->tieneDesviacion())->toBeFalse();
});

it('VoBo del cliente firma la bitácora y bloquea edición', function () {
    $b = BitacoraDiaria::create([
        'proyecto_id' => $this->proyecto->id,
        'fecha' => '2026-05-10',
        'relacion_actividades' => 'Día con avance normal.',
        'cargado_por_id' => $this->gp->id,
    ]);

    $this->actingAs($this->gp)
        ->post(route('bitacoras.vobo', [$this->proyecto, $b]), [
            'vobo_cliente_nombre' => 'Ing. Juan Pérez',
            'vobo_cliente_organizacion' => 'CFE',
            'vobo_cliente_fecha' => '2026-05-10',
        ])
        ->assertRedirect();

    expect($b->fresh()->firmado_at)->not->toBeNull();

    // No se puede editar después
    $this->actingAs($this->gp)
        ->from(route('bitacoras.show', [$this->proyecto, $b]))
        ->patch(route('bitacoras.update', [$this->proyecto, $b]), [
            'relacion_actividades' => 'Texto modificado intentando editar firmada.',
        ])
        ->assertSessionHasErrors(['bitacora']);
});

it('PDF se genera al solicitarlo', function () {
    $b = BitacoraDiaria::create([
        'proyecto_id' => $this->proyecto->id,
        'fecha' => '2026-05-10',
        'relacion_actividades' => 'Día normal con avance.',
        'cargado_por_id' => $this->gp->id,
    ]);

    $this->actingAs($this->gp)
        ->get(route('bitacoras.pdf', [$this->proyecto, $b]))
        ->assertOk()
        ->assertHeader('content-type', 'application/pdf');
});

it('rechaza crear bitácora si proyecto no está en ejecución', function () {
    $p = Proyecto::factory()->create(['estado' => 'cotizando']);

    $this->actingAs($this->gp)
        ->from(route('oportunidades.show', $p))
        ->post(route('bitacoras.store', $p), [
            'fecha' => '2026-05-10',
            'relacion_actividades' => 'Bitácora prematura, debería fallar.',
        ])
        ->assertSessionHasErrors(['bitacora']);
});
