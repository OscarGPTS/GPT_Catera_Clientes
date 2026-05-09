<?php

use App\Models\BitacoraDiaria;
use App\Models\Proyecto;
use App\Models\User;
use App\Services\Ejecucion\ReporteSemanalService;
use Carbon\CarbonImmutable;
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

it('generar reporte compila bitácoras de la semana', function () {
    $semanaInicio = CarbonImmutable::parse('2026-05-04')->startOfWeek();

    BitacoraDiaria::create([
        'proyecto_id' => $this->proyecto->id,
        'fecha' => $semanaInicio->addDays(1)->toDateString(),
        'relacion_actividades' => 'Lunes con avance normal.',
        'cargado_por_id' => $this->gp->id,
    ]);
    BitacoraDiaria::create([
        'proyecto_id' => $this->proyecto->id,
        'fecha' => $semanaInicio->addDays(3)->toDateString(),
        'relacion_actividades' => 'Hubo un retraso menor por lluvia.',
        'cargado_por_id' => $this->gp->id,
    ]);

    $r = app(ReporteSemanalService::class)->generar($this->proyecto, $semanaInicio, $this->gp->id);

    expect($r->contenido_html)->toContain('Lunes con avance normal')
        ->and($r->contenido_html)->toContain('retraso')
        ->and($r->contenido_html)->toContain('desviaciones detectadas')
        ->and($r->generado_at)->not->toBeNull();
});

it('regenerar mismo reporte no duplica registros', function () {
    $service = app(ReporteSemanalService::class);
    $semana = CarbonImmutable::parse('2026-05-04');

    $r1 = $service->generar($this->proyecto, $semana, $this->gp->id);
    $r2 = $service->generar($this->proyecto, $semana, $this->gp->id);

    expect($r1->id)->toBe($r2->id)
        ->and($this->proyecto->reportesSemanales()->count())->toBe(1);
});

it('crear via HTTP redirige al show', function () {
    $this->actingAs($this->gp)
        ->post(route('reportes.store', $this->proyecto), [
            'semana_inicio' => '2026-05-04',
        ])
        ->assertRedirect();

    expect($this->proyecto->reportesSemanales()->count())->toBe(1);
});

it('marcar como enviado requiere emails válidos', function () {
    $r = app(ReporteSemanalService::class)->generar($this->proyecto, CarbonImmutable::now(), $this->gp->id);

    $this->actingAs($this->gp)
        ->from(route('reportes.show', [$this->proyecto, $r]))
        ->post(route('reportes.enviar', [$this->proyecto, $r]), ['recipients' => 'noEsEmail'])
        ->assertSessionHasErrors(['recipients']);

    $this->actingAs($this->gp)
        ->post(route('reportes.enviar', [$this->proyecto, $r]), [
            'recipients' => 'cliente@example.com, gp@gpt.com',
        ])
        ->assertRedirect();

    expect($r->fresh()->enviado_at)->not->toBeNull()
        ->and($r->fresh()->recipients)->toBe(['cliente@example.com', 'gp@gpt.com']);
});

it('PDF endpoint devuelve archivo', function () {
    $r = app(ReporteSemanalService::class)->generar($this->proyecto, CarbonImmutable::now(), $this->gp->id);

    $this->actingAs($this->gp)
        ->get(route('reportes.pdf', [$this->proyecto, $r]))
        ->assertOk()
        ->assertHeader('content-type', 'application/pdf');
});
