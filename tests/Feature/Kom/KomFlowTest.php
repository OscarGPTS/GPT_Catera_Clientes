<?php

use App\Models\KickOffMeeting;
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
        'dn_numero' => 'DN-001/26',
    ]);
});

it('lista de KOMs requiere autenticación', function () {
    $this->get(route('koms.index', $this->proyecto))->assertRedirect('/login');
});

it('crear KOM interno valida tipo y fecha y registra evento', function () {
    $this->actingAs($this->gp)
        ->post(route('koms.store', $this->proyecto), [
            'tipo' => 'kom_interno',
            'fecha' => '2026-05-10T10:00',
            'agenda' => 'Revisar alcance',
        ])
        ->assertRedirect();

    $kom = $this->proyecto->koms()->first();

    expect($kom)->not->toBeNull()
        ->and($kom->tipo)->toBe('kom_interno')
        ->and($kom->agenda)->toBe('Revisar alcance')
        ->and($this->proyecto->fresh()->eventos()->where('tipo', 'kom_interno_creado')->exists())->toBeTrue();
});

it('rechaza crear KOM si el proyecto está en cotizando', function () {
    $p = Proyecto::factory()->create(['estado' => 'cotizando']);

    $this->actingAs($this->gp)
        ->from(route('koms.index', $p))
        ->post(route('koms.store', $p), [
            'tipo' => 'kom_interno',
            'fecha' => '2026-05-10T10:00',
        ])
        ->assertSessionHasErrors(['kom']);
});

it('actualizar KOM filtra participantes vacíos', function () {
    $kom = $this->proyecto->koms()->create([
        'tipo' => 'kom_cliente',
        'fecha' => '2026-05-10 10:00',
    ]);

    $this->actingAs($this->gp)
        ->patch(route('koms.update', [$this->proyecto, $kom]), [
            'fecha' => '2026-05-10T10:00',
            'agenda' => 'Nuevo',
            'participantes' => [
                ['nombre' => 'Juan', 'rol' => 'PM', 'empresa' => 'Cliente'],
                ['nombre' => '', 'rol' => '', 'empresa' => ''],
            ],
        ])
        ->assertRedirect();

    $kom->refresh();
    expect($kom->participantes)->toHaveCount(1)
        ->and($kom->participantes[0]['nombre'])->toBe('Juan');
});

it('PDF se genera al solicitarlo', function () {
    $kom = $this->proyecto->koms()->create([
        'tipo' => 'kom_interno',
        'fecha' => '2026-05-10 10:00',
        'agenda' => 'Test',
        'minuta' => 'Acuerdos',
    ]);

    $this->actingAs($this->gp)
        ->get(route('koms.pdf', [$this->proyecto, $kom]))
        ->assertOk()
        ->assertHeader('content-type', 'application/pdf');

    expect($kom->fresh()->minuta_pdf_path)->not->toBeNull();
});

it('eliminar KOM lo borra y redirige al index', function () {
    $kom = $this->proyecto->koms()->create([
        'tipo' => 'kom_interno',
        'fecha' => '2026-05-10 10:00',
    ]);

    $this->actingAs($this->gp)
        ->delete(route('koms.destroy', [$this->proyecto, $kom]))
        ->assertRedirect(route('koms.index', $this->proyecto));

    expect(KickOffMeeting::find($kom->id))->toBeNull();
});

it('un KOM de otro proyecto retorna 404', function () {
    $otroProyecto = Proyecto::factory()->create(['estado' => 'en_ejecucion']);
    $kom = $this->proyecto->koms()->create([
        'tipo' => 'kom_interno',
        'fecha' => '2026-05-10 10:00',
    ]);

    $this->actingAs($this->gp)
        ->get(route('koms.show', [$otroProyecto, $kom]))
        ->assertNotFound();
});
