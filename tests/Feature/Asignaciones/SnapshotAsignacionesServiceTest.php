<?php

use App\Models\AsignacionPersona;
use App\Models\Cliente;
use App\Models\Proyecto;
use App\Models\Sublinea;
use App\Models\User;
use App\Services\Asignaciones\SnapshotAsignacionesService;
use Database\Seeders\ComercialCatalogosSeeder;
use Database\Seeders\RolesPermissionsSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

beforeEach(function () {
    $this->seed([RolesPermissionsSeeder::class, ComercialCatalogosSeeder::class]);
});

it('genera un snapshot para cada usuario del Departamento de Proyectos', function () {
    $user = User::factory()->create(['status' => 'active']);
    $user->assignRole('ingeniero_proyectos');

    Proyecto::create([
        'cliente_id' => Cliente::first()->id,
        'sublinea_id' => Sublinea::first()->id,
        'año' => 2026,
        'cp_numero' => 'CP-001/26',
        'estado' => 'cotizando',
        'ingeniero_proyectos_id' => $user->id,
    ]);

    Proyecto::create([
        'cliente_id' => Cliente::first()->id,
        'sublinea_id' => Sublinea::first()->id,
        'año' => 2026,
        'cp_numero' => 'CP-002/26',
        'dn_numero' => 'DN-001/26',
        'estado' => 'en_ejecucion',
        'ingeniero_proyectos_id' => $user->id,
    ]);

    $generated = app(SnapshotAsignacionesService::class)->generar(now()->year, now()->month);

    expect($generated)->toBe(1);

    $snap = AsignacionPersona::where('user_id', $user->id)->first();
    expect($snap)->not->toBeNull()
        ->and($snap->dn_activos)->toBe(1);
});

it('upsert: corre dos veces y deja un solo snapshot por user/mes/año', function () {
    $user = User::factory()->create();
    $user->assignRole('gerente_proyectos');

    $svc = app(SnapshotAsignacionesService::class);
    $svc->generar(2026, 5);
    $svc->generar(2026, 5);

    expect(AsignacionPersona::where('user_id', $user->id)->count())->toBe(1);
});
