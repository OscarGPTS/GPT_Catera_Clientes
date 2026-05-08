<?php

use App\Models\Proyecto;
use App\Models\User;
use App\Services\Proyectos\CronogramaService;
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
        'fecha_inicio_planeada' => '2026-05-01',
        'fecha_fin_planeada' => '2026-08-01',
    ]);
});

it('crear cronograma asigna v1', function () {
    $this->actingAs($this->gp)
        ->post(route('cronogramas.store', $this->proyecto))
        ->assertRedirect();

    $c = $this->proyecto->cronogramas()->first();
    expect($c->version)->toBe(1);
});

it('nueva versión copia actividades de la anterior con avance reseteado', function () {
    $service = app(CronogramaService::class);
    $v1 = $service->crearVersion($this->proyecto, $this->gp->id);
    $service->agregarActividad($v1, [
        'codigo' => '1', 'nombre' => 'Movilización',
        'fecha_inicio_planeada' => '2026-05-05',
        'fecha_fin_planeada' => '2026-05-12',
        'porcentaje_avance' => 80,
    ]);

    $v2 = $service->crearVersion($this->proyecto->fresh(), $this->gp->id);

    expect($v2->version)->toBe(2)
        ->and($v2->actividades()->count())->toBe(1)
        ->and((float) $v2->actividades()->first()->porcentaje_avance)->toBe(0.0);
});

it('agregar actividad recalcula los extremos del cronograma', function () {
    $service = app(CronogramaService::class);
    $cron = $service->crearVersion($this->proyecto, $this->gp->id);

    $this->actingAs($this->gp)
        ->post(route('cronogramas.actividades.store', [$this->proyecto, $cron]), [
            'nombre' => 'Procura',
            'fecha_inicio_planeada' => '2026-05-15',
            'fecha_fin_planeada' => '2026-06-01',
        ])
        ->assertRedirect();

    $cron->refresh();
    expect((string) $cron->fecha_inicio)->toContain('2026-05-15')
        ->and((string) $cron->fecha_fin)->toContain('2026-06-01');
});

it('actualizar avance recalcula avance global', function () {
    $service = app(CronogramaService::class);
    $cron = $service->crearVersion($this->proyecto, $this->gp->id);
    $a = $service->agregarActividad($cron, [
        'nombre' => 'A', 'fecha_inicio_planeada' => '2026-05-01', 'fecha_fin_planeada' => '2026-05-10', 'porcentaje_avance' => 0,
    ]);
    $service->agregarActividad($cron, [
        'nombre' => 'B', 'fecha_inicio_planeada' => '2026-05-11', 'fecha_fin_planeada' => '2026-05-20', 'porcentaje_avance' => 0,
    ]);

    expect($service->avanceGlobal($cron))->toBe(0.0);

    $this->actingAs($this->gp)
        ->patch(route('cronogramas.actividades.update', [$this->proyecto, $cron, $a]), [
            'porcentaje_avance' => 100,
        ]);

    expect($service->avanceGlobal($cron->fresh()))->toBe(50.0);
});

it('eliminar actividad recalcula extremos', function () {
    $service = app(CronogramaService::class);
    $cron = $service->crearVersion($this->proyecto, $this->gp->id);
    $a = $service->agregarActividad($cron, [
        'nombre' => 'X', 'fecha_inicio_planeada' => '2026-05-15', 'fecha_fin_planeada' => '2026-06-15',
    ]);

    $this->actingAs($this->gp)
        ->delete(route('cronogramas.actividades.destroy', [$this->proyecto, $cron, $a]))
        ->assertRedirect();

    expect($cron->fresh()->actividades()->count())->toBe(0);
});

it('rechaza crear cronograma si el proyecto está en cotizando', function () {
    $p = Proyecto::factory()->create(['estado' => 'cotizando']);

    $this->actingAs($this->gp)
        ->from(route('cronogramas.index', $p))
        ->post(route('cronogramas.store', $p))
        ->assertSessionHasErrors(['cronograma']);
});

it('valida fecha_fin después de fecha_inicio en actividades', function () {
    $cron = app(CronogramaService::class)->crearVersion($this->proyecto, $this->gp->id);

    $this->actingAs($this->gp)
        ->from(route('cronogramas.show', [$this->proyecto, $cron]))
        ->post(route('cronogramas.actividades.store', [$this->proyecto, $cron]), [
            'nombre' => 'Mal',
            'fecha_inicio_planeada' => '2026-06-01',
            'fecha_fin_planeada' => '2026-05-15',
        ])
        ->assertSessionHasErrors(['fecha_fin_planeada']);
});
