<?php

use App\Models\Proyecto;
use App\Models\SystemSetting;
use App\Models\User;
use App\Services\Cierre\CartaFiniquitoService;
use App\Services\Cierre\PostMortemService;
use App\Services\Libro\AperturaLibroService;
use App\Services\Libro\LibroService;
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
        'estado' => 'en_cierre',
        'gerente_proyectos_id' => $this->gp->id,
        'monto_preliminar' => 100_000,
        'fecha_inicio_planeada' => now()->subDays(60),
        'fecha_fin_planeada' => now()->subDays(5),
    ]);

    // Abrir libro y completarlo al 100%
    $libro = app(AperturaLibroService::class)->abrirParaProyecto($this->proyecto);
    foreach ($libro->secciones as $seccion) {
        foreach ($seccion->checklist as $item) {
            app(LibroService::class)->toggleChecklistItem($item, $this->gp->id);
        }
    }
    app(LibroService::class)->actualizarBloqueoCierre($libro->fresh());
});

it('show requiere proyecto en cierre o cerrado', function () {
    $p = Proyecto::factory()->create(['estado' => 'en_ejecucion']);
    $this->actingAs($this->gp)
        ->from(route('oportunidades.show', $p))
        ->get(route('cierre.show', $p))
        ->assertRedirect(route('oportunidades.show', $p))
        ->assertSessionHasErrors(['cierre']);
});

it('crear carta finiquito persiste con personal y equipos', function () {
    $this->actingAs($this->gp)
        ->post(route('cierre.carta.store', $this->proyecto), [
            'fecha_emision' => '2026-05-10',
            'observaciones' => 'Proyecto cerrado satisfactoriamente.',
            'personal_liberado' => [['nombre' => $this->gp->name, 'rol' => 'GP']],
            'equipos_liberados' => [['nombre' => 'Equipo X']],
        ])
        ->assertRedirect();

    $c = $this->proyecto->fresh()->cartaFiniquito;
    expect($c)->not->toBeNull()
        ->and($c->personal_liberado)->toHaveCount(1)
        ->and($c->equipos_liberados)->toHaveCount(1);
});

it('firmar GPT requiere libro al 100% (D11)', function () {
    SystemSetting::set('bloqueo_cierre_dossier_incompleto', true, 'boolean');

    // Romper el libro: poner una sección a 0%
    $seccion = $this->proyecto->libro->secciones()->first();
    foreach ($seccion->checklist as $item) {
        app(LibroService::class)->toggleChecklistItem($item, $this->gp->id); // toggle de 100→0
    }

    $service = app(CartaFiniquitoService::class);
    $carta = $service->crearOActualizar($this->proyecto, $this->gp->id, []);

    $this->actingAs($this->gp)
        ->from(route('cierre.show', $this->proyecto))
        ->post(route('cierre.carta.firmar.gpt', $this->proyecto))
        ->assertSessionHasErrors(['carta']);

    expect($carta->fresh()->firmado_gpt_at)->toBeNull();
});

it('firma GPT funciona con libro al 100%', function () {
    $service = app(CartaFiniquitoService::class);
    $service->crearOActualizar($this->proyecto, $this->gp->id, []);

    $this->actingAs($this->gp)
        ->post(route('cierre.carta.firmar.gpt', $this->proyecto))
        ->assertRedirect();

    expect($this->proyecto->fresh()->cartaFiniquito->firmado_gpt_at)->not->toBeNull();
});

it('cliente no puede firmar antes que GPT', function () {
    $service = app(CartaFiniquitoService::class);
    $service->crearOActualizar($this->proyecto, $this->gp->id, []);

    $this->actingAs($this->gp)
        ->from(route('cierre.show', $this->proyecto))
        ->post(route('cierre.carta.firmar.cliente', $this->proyecto), [
            'cliente_nombre' => 'Ing. Cliente',
        ])
        ->assertSessionHasErrors(['carta']);
});

it('flujo completo: crear carta, firmar GPT, firmar cliente, cerrar proyecto', function () {
    $service = app(CartaFiniquitoService::class);
    $service->crearOActualizar($this->proyecto, $this->gp->id, []);

    $service->firmarGpt($this->proyecto->fresh()->cartaFiniquito, $this->gp->id);
    $service->firmarCliente($this->proyecto->fresh()->cartaFiniquito, $this->gp->id, 'Ing. Cliente');

    $this->actingAs($this->gp)
        ->post(route('cierre.cerrar', $this->proyecto))
        ->assertRedirect();

    expect($this->proyecto->fresh()->estado)->toBe('cerrado')
        ->and($this->proyecto->fresh()->eventos()->where('tipo', 'proyecto_cerrado')->exists())->toBeTrue();
});

it('D12: si bloqueo_cierre_post_mortem_pendiente=true, cerrar requiere post-mortem', function () {
    SystemSetting::set('bloqueo_cierre_post_mortem_pendiente', true, 'boolean');

    $service = app(CartaFiniquitoService::class);
    $service->crearOActualizar($this->proyecto, $this->gp->id, []);
    $service->firmarGpt($this->proyecto->fresh()->cartaFiniquito, $this->gp->id);
    $service->firmarCliente($this->proyecto->fresh()->cartaFiniquito, $this->gp->id, 'Ing. Cliente');

    $this->actingAs($this->gp)
        ->from(route('cierre.show', $this->proyecto))
        ->post(route('cierre.cerrar', $this->proyecto))
        ->assertSessionHasErrors(['cerrar']);

    expect($this->proyecto->fresh()->estado)->toBe('en_cierre');

    // Después de crear el post-mortem, sí cierra
    app(PostMortemService::class)->crearOActualizar($this->proyecto, $this->gp->id, [
        'lecciones_aprendidas' => 'Test',
    ]);

    $this->actingAs($this->gp)
        ->post(route('cierre.cerrar', $this->proyecto))
        ->assertRedirect();

    expect($this->proyecto->fresh()->estado)->toBe('cerrado');
});

it('post-mortem calcula desviaciones de costo automáticamente', function () {
    $pm = app(PostMortemService::class)->crearOActualizar($this->proyecto, $this->gp->id, [
        'presupuesto_planeado' => 100_000,
        'presupuesto_real' => 110_000,
    ]);

    expect((float) $pm->desviaciones_costo)->toBe(0.1); // 10% sobrecosto
});

it('post-mortem usa avance del libro como proxy de calidad', function () {
    $pm = app(PostMortemService::class)->crearOActualizar($this->proyecto, $this->gp->id, []);

    expect((float) $pm->desviaciones_calidad)->toBe(1.0); // Libro al 100%
});

it('PDFs de carta y post-mortem se generan', function () {
    $service = app(CartaFiniquitoService::class);
    $service->crearOActualizar($this->proyecto, $this->gp->id, []);
    app(PostMortemService::class)->crearOActualizar($this->proyecto, $this->gp->id, [
        'lecciones_aprendidas' => 'Test',
    ]);

    $this->actingAs($this->gp)
        ->get(route('cierre.carta.pdf', $this->proyecto))
        ->assertOk()
        ->assertHeader('content-type', 'application/pdf');

    $this->actingAs($this->gp)
        ->get(route('cierre.postmortem.pdf', $this->proyecto))
        ->assertOk()
        ->assertHeader('content-type', 'application/pdf');
});
