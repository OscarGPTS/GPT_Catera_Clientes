<?php

use App\Models\Cliente;
use App\Models\Proyecto;
use App\Models\Sublinea;
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

    $this->dgUser = User::factory()->create();
    $this->dgUser->assignRole('direccion_general');

    $this->commercialUser = User::factory()->create();
    $this->commercialUser->assignRole('comercial');

    $this->gerente = User::factory()->create(['name' => 'Fernando Test']);
    $this->gerente->assignRole('gerente_proyectos');
});

it('lista de oportunidades requiere autenticación', function () {
    $this->get('/oportunidades')->assertRedirect('/login');
});

it('un comercial puede ver el index', function () {
    Proyecto::factory()->count(3)->create();

    $this->actingAs($this->commercialUser)
        ->get('/oportunidades')
        ->assertOk()
        ->assertSee('Status de ofertas');
});

it('crear oportunidad asigna CP atómico y registra evento', function () {
    $cliente = Cliente::first();
    $sublinea = Sublinea::first();

    $payload = [
        'cliente_id' => $cliente->id,
        'sublinea_id' => $sublinea->id,
        'usuario_final' => 'CENAGAS',
        'sector' => 'Energía',
        'resumen_ejecutivo' => 'Hot Tap 30"x10" en ducto de Texmelucan, intervención sin paro.',
        'metodo_distribucion_plurianual' => 'dias_naturales',
        'monto_preliminar' => 96998.30,
        'moneda' => 'USD',
        'director_dn_id' => $this->dgUser->id,
    ];

    $this->actingAs($this->dgUser)
        ->post('/oportunidades', $payload)
        ->assertRedirect();

    $proyecto = Proyecto::where('usuario_final', 'CENAGAS')->first();

    expect($proyecto)->not->toBeNull()
        ->and($proyecto->cp_numero)->toMatch('/^CP-\d{3}\/\d{2}$/')
        ->and($proyecto->estado)->toBe('en_revision')
        ->and($proyecto->eventos()->where('tipo', 'cp_asignado')->exists())->toBeTrue();
});

it('rechaza resumen ejecutivo demasiado corto', function () {
    $cliente = Cliente::first();
    $sublinea = Sublinea::first();

    $this->actingAs($this->dgUser)
        ->from('/oportunidades/nueva')
        ->post('/oportunidades', [
            'cliente_id' => $cliente->id,
            'sublinea_id' => $sublinea->id,
            'resumen_ejecutivo' => 'corto',
            'metodo_distribucion_plurianual' => 'dias_naturales',
            'moneda' => 'USD',
            'director_dn_id' => $this->dgUser->id,
        ])
        ->assertSessionHasErrors(['resumen_ejecutivo']);

    expect(Proyecto::count())->toBe(0);
});

it('aprobar CP transiciona a cotizando, asigna gerente y registra evento', function () {
    $proyecto = Proyecto::factory()->create([
        'estado' => 'en_revision',
        'director_dn_id' => $this->dgUser->id,
    ]);

    $this->actingAs($this->dgUser)
        ->post(route('oportunidades.aprobarCp', $proyecto), [
            'gerente_proyectos_id' => $this->gerente->id,
            'comentario' => 'Aprobado por comité',
            'decision' => 'aprobar',
        ])
        ->assertRedirect();

    $proyecto->refresh();

    expect($proyecto->estado)->toBe('cotizando')
        ->and($proyecto->gerente_proyectos_id)->toBe($this->gerente->id)
        ->and($proyecto->eventos()->where('tipo', 'cp_aprobado')->exists())->toBeTrue();
});

it('rechazar CP transiciona a cancelado', function () {
    $proyecto = Proyecto::factory()->create([
        'estado' => 'en_revision',
        'director_dn_id' => $this->dgUser->id,
    ]);

    $this->actingAs($this->dgUser)
        ->post(route('oportunidades.aprobarCp', $proyecto), [
            'gerente_proyectos_id' => $this->gerente->id,
            'comentario' => 'Pipeline saturado',
            'decision' => 'rechazar',
        ]);

    expect($proyecto->fresh()->estado)->toBe('cancelado');
});

it('asignar equipo rellena ingenieros y registra evento', function () {
    $ic = User::factory()->create();
    $ic->assignRole('ingeniero_costos');
    $ip = User::factory()->create();
    $ip->assignRole('ingeniero_proyectos');

    $proyecto = Proyecto::factory()->create([
        'estado' => 'cotizando',
        'gerente_proyectos_id' => $this->gerente->id,
    ]);

    $this->actingAs($this->gerente)
        ->post(route('oportunidades.asignarEquipo', $proyecto), [
            'ingeniero_costos_id' => $ic->id,
            'ingeniero_proyectos_id' => $ip->id,
        ])
        ->assertRedirect();

    $proyecto->refresh();

    expect($proyecto->ingeniero_costos_id)->toBe($ic->id)
        ->and($proyecto->ingeniero_proyectos_id)->toBe($ip->id)
        ->and($proyecto->eventos()->where('tipo', 'equipo_asignado')->exists())->toBeTrue();
});

it('show renderiza la página de detalle', function () {
    $proyecto = Proyecto::factory()->create();

    $this->actingAs($this->commercialUser)
        ->get(route('oportunidades.show', $proyecto))
        ->assertOk()
        ->assertSee($proyecto->cp_numero ?? $proyecto->resumen_ejecutivo);
});

it('filtros: por estado solo devuelve los que coinciden', function () {
    Proyecto::factory()->estado('cotizando')->count(2)->create();
    Proyecto::factory()->estado('cerrado')->count(1)->create();

    $this->actingAs($this->commercialUser)
        ->get('/oportunidades?estado=cotizando')
        ->assertOk()
        ->assertSee('Cotizando');
});
