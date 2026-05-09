<?php

use App\Models\Proyecto;
use App\Models\User;
use App\Services\Procura\SolicitudInternaService;
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

    $this->compras = User::factory()->create();
    $this->compras->assignRole('compras');

    $this->proyecto = Proyecto::factory()->create([
        'estado' => 'en_ejecucion',
        'gerente_proyectos_id' => $this->gp->id,
    ]);
});

it('crear solicitud guarda items y queda en borrador', function () {
    $this->actingAs($this->gp)
        ->post(route('solicitudes.store', $this->proyecto), [
            'tipo' => 'requisicion_compras',
            'asignado_id' => $this->compras->id,
            'descripcion' => 'Necesito procura de bridas',
            'items' => [
                ['descripcion' => 'Brida ANSI 600', 'cantidad' => 4, 'unidad' => 'pza'],
                ['descripcion' => 'Empaque', 'cantidad' => 4, 'unidad' => 'pza'],
                ['descripcion' => '', 'cantidad' => null], // se debe filtrar
            ],
        ])
        ->assertRedirect();

    $solicitud = $this->proyecto->solicitudesInternas()->first();

    expect($solicitud->estado)->toBe('borrador')
        ->and($solicitud->items()->count())->toBe(2)
        ->and($solicitud->cp_numero)->toBe($this->proyecto->cp_numero);
});

it('emitir registra evento y rechaza si no hay items', function () {
    $service = app(SolicitudInternaService::class);
    $vacia = $service->crear($this->proyecto, $this->gp->id, [
        'tipo' => 'requisicion_compras',
        'items' => [],
    ]);

    $this->actingAs($this->gp)
        ->from(route('solicitudes.show', [$this->proyecto, $vacia]))
        ->post(route('solicitudes.emitir', [$this->proyecto, $vacia]))
        ->assertSessionHasErrors(['solicitud']);

    // Ahora con items
    $sol = $service->crear($this->proyecto, $this->gp->id, [
        'tipo' => 'requisicion_compras',
        'items' => [['descripcion' => 'X', 'cantidad' => 1]],
    ]);

    $this->actingAs($this->gp)
        ->post(route('solicitudes.emitir', [$this->proyecto, $sol]))
        ->assertRedirect();

    expect($sol->fresh()->estado)->toBe('emitida')
        ->and($this->proyecto->fresh()->eventos()->where('tipo', 'solicitud_interna_emitida')->exists())->toBeTrue();
});

it('tomar transiciona emitida→en_proceso y asigna al usuario', function () {
    $service = app(SolicitudInternaService::class);
    $sol = $service->crear($this->proyecto, $this->gp->id, [
        'tipo' => 'requisicion_compras',
        'items' => [['descripcion' => 'X', 'cantidad' => 1]],
    ]);
    $service->emitir($sol, $this->gp->id);

    $this->actingAs($this->compras)
        ->post(route('solicitudes.tomar', [$this->proyecto, $sol]))
        ->assertRedirect();

    $sol->refresh();
    expect($sol->estado)->toBe('en_proceso')
        ->and($sol->asignado_id)->toBe($this->compras->id);
});

it('responder requiere texto mínimo y registra evento', function () {
    $service = app(SolicitudInternaService::class);
    $sol = $service->crear($this->proyecto, $this->gp->id, [
        'tipo' => 'requisicion_compras',
        'items' => [['descripcion' => 'X', 'cantidad' => 1]],
    ]);
    $service->emitir($sol, $this->gp->id);

    $this->actingAs($this->compras)
        ->from(route('solicitudes.show', [$this->proyecto, $sol]))
        ->post(route('solicitudes.responder', [$this->proyecto, $sol]), ['respuesta' => 'no'])
        ->assertSessionHasErrors(['respuesta']);

    $this->actingAs($this->compras)
        ->post(route('solicitudes.responder', [$this->proyecto, $sol]), [
            'respuesta' => 'OC liberada con proveedor X, entrega lunes.',
        ])
        ->assertRedirect();

    $sol->refresh();
    expect($sol->estado)->toBe('respondida')
        ->and($sol->fecha_respuesta_real)->not->toBeNull()
        ->and($this->proyecto->fresh()->eventos()->where('tipo', 'solicitud_interna_respondida')->exists())->toBeTrue();
});

it('cancelar funciona en borrador y emitida pero no en respondida', function () {
    $service = app(SolicitudInternaService::class);
    $sol = $service->crear($this->proyecto, $this->gp->id, [
        'tipo' => 'requisicion_compras',
        'items' => [['descripcion' => 'X', 'cantidad' => 1]],
    ]);

    $this->actingAs($this->gp)
        ->post(route('solicitudes.cancelar', [$this->proyecto, $sol]), ['razon' => 'No requerido'])
        ->assertRedirect();

    expect($sol->fresh()->estado)->toBe('cancelada');

    // Una respondida no puede cancelarse
    $sol2 = $service->crear($this->proyecto, $this->gp->id, [
        'tipo' => 'requisicion_compras',
        'items' => [['descripcion' => 'X', 'cantidad' => 1]],
    ]);
    $service->emitir($sol2, $this->gp->id);
    $service->responder($sol2, $this->compras->id, 'Respondida');

    $this->actingAs($this->gp)
        ->from(route('solicitudes.show', [$this->proyecto, $sol2]))
        ->post(route('solicitudes.cancelar', [$this->proyecto, $sol2]), ['razon' => 'tarde'])
        ->assertSessionHasErrors(['solicitud']);
});

it('listar solicitudes requiere autenticación', function () {
    $this->get(route('solicitudes.index', $this->proyecto))->assertRedirect('/login');
});
