<?php

use App\Models\Proyecto;
use App\Models\User;
use App\Notifications\CotizacionEmitidaNotification;
use App\Notifications\CpAprobadoNotification;
use App\Notifications\MinutaFirmadaNotification;
use App\Notifications\OcFirmadaNotification;
use App\Notifications\ReporteSemanalGeneradoNotification;
use App\Notifications\ViaticosAprobadosNotification;
use App\Services\Cotizaciones\CotizacionService;
use App\Services\Ejecucion\ReporteSemanalService;
use App\Services\Ejecucion\ViaticosService;
use App\Services\Minutas\MinutaEntregaService;
use App\Services\Proyectos\AdjudicacionService;
use Carbon\CarbonImmutable;
use Database\Seeders\ComercialCatalogosSeeder;
use Database\Seeders\RhRoleMappingSeeder;
use Database\Seeders\RolesPermissionsSeeder;
use Database\Seeders\SystemSettingsSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Notification;

uses(RefreshDatabase::class);

beforeEach(function () {
    $this->seed([
        SystemSettingsSeeder::class,
        RolesPermissionsSeeder::class,
        RhRoleMappingSeeder::class,
        ComercialCatalogosSeeder::class,
    ]);

    $this->dg = User::factory()->create();
    $this->dg->assignRole('direccion_general');

    $this->gp = User::factory()->create();
    $this->gp->assignRole('gerente_proyectos');

    $this->go = User::factory()->create();
    $this->go->assignRole('gerente_operaciones');

    $this->ic = User::factory()->create();
    $this->ic->assignRole('ingeniero_costos');
});

it('aprobar CP envía CpAprobadoNotification al GP asignado', function () {
    Notification::fake();

    $p = Proyecto::factory()->create(['estado' => 'en_revision', 'director_dn_id' => $this->dg->id]);

    $this->actingAs($this->dg)
        ->post(route('oportunidades.aprobarCp', $p), [
            'gerente_proyectos_id' => $this->gp->id,
            'comentario' => 'Aprobado',
            'decision' => 'aprobar',
        ]);

    Notification::assertSentTo($this->gp, CpAprobadoNotification::class);
});

it('emitir cotización envía CotizacionEmitidaNotification al director_dn', function () {
    Notification::fake();

    $p = Proyecto::factory()->create([
        'estado' => 'cotizando',
        'director_dn_id' => $this->dg->id,
        'gerente_proyectos_id' => $this->gp->id,
    ]);

    $cot = app(CotizacionService::class)->crearBorrador($p, $this->ic->id);
    $cot->partidas()->create([
        'numero_partida' => 1, 'descripcion' => 'X', 'cantidad' => 1, 'unidad' => 'pza',
        'costo_unitario' => 1000, 'costo_total' => 1000,
    ]);

    app(CotizacionService::class)->emitir($cot, $this->ic->id);

    Notification::assertSentTo($this->dg, CotizacionEmitidaNotification::class);
});

it('firmar OC envía OcFirmadaNotification a GP y GO', function () {
    Notification::fake();

    $p = Proyecto::factory()->create([
        'estado' => 'adjudicado_pendiente',
        'director_dn_id' => $this->dg->id,
        'gerente_proyectos_id' => $this->gp->id,
        'gerente_operaciones_id' => $this->go->id,
    ]);

    app(AdjudicacionService::class)->firmarOc($p, $this->dg->id, ['oc_firma_fecha' => '2026-04-20']);

    Notification::assertSentTo($this->gp, OcFirmadaNotification::class);
    Notification::assertSentTo($this->go, OcFirmadaNotification::class);
});

it('minuta firmada por todos envía MinutaFirmadaNotification a GP y director_dn', function () {
    $p = Proyecto::factory()->create([
        'estado' => 'adjudicado_firmado',
        'director_dn_id' => $this->dg->id,
        'gerente_proyectos_id' => $this->gp->id,
    ]);

    $service = app(MinutaEntregaService::class);
    $minuta = $service->crearOSeleccionar($p, $this->gp->id);

    Notification::fake();

    foreach ($minuta->participantes as $part) {
        $service->firmarPorUsuario($minuta->fresh(), $part->user_id);
    }

    Notification::assertSentTo($this->gp, MinutaFirmadaNotification::class);
    Notification::assertSentTo($this->dg, MinutaFirmadaNotification::class);
});

it('viáticos aprobados envía notificación al solicitante', function () {
    Notification::fake();

    $p = Proyecto::factory()->create([
        'estado' => 'en_ejecucion',
        'gerente_proyectos_id' => $this->gp->id,
    ]);

    $servgrales = User::factory()->create();
    $servgrales->assignRole('serv_generales');

    $service = app(ViaticosService::class);
    $sol = $service->crear($p, $this->gp->id, [
        'periodo_inicio' => '2026-05-01',
        'periodo_fin' => '2026-05-05',
        'personal' => [['user_id' => $this->gp->id, 'dias' => 5]],
        'partidas' => [['concepto' => 'hospedaje', 'monto_estimado' => 1000]],
    ]);
    $service->emitir($sol, $this->gp->id);
    $service->aprobarServGrales($sol->fresh(), $servgrales->id);
    $service->aprobarDireccion($sol->fresh(), $this->dg->id);

    Notification::assertSentTo($this->gp, ViaticosAprobadosNotification::class);
});

it('reporte semanal generado envía notificación a GP y director_dn', function () {
    Notification::fake();

    $p = Proyecto::factory()->create([
        'estado' => 'en_ejecucion',
        'director_dn_id' => $this->dg->id,
        'gerente_proyectos_id' => $this->gp->id,
    ]);

    app(ReporteSemanalService::class)->generar($p, CarbonImmutable::now(), $this->gp->id);

    Notification::assertSentTo($this->gp, ReporteSemanalGeneradoNotification::class);
    Notification::assertSentTo($this->dg, ReporteSemanalGeneradoNotification::class);
});

it('notificación se persiste en database con data útil', function () {
    $p = Proyecto::factory()->create([
        'estado' => 'en_revision',
        'director_dn_id' => $this->dg->id,
    ]);

    $this->actingAs($this->dg)
        ->post(route('oportunidades.aprobarCp', $p), [
            'gerente_proyectos_id' => $this->gp->id,
            'comentario' => 'Test',
            'decision' => 'aprobar',
        ]);

    $notif = $this->gp->fresh()->notifications()->first();

    expect($notif)->not->toBeNull()
        ->and($notif->type)->toBe(CpAprobadoNotification::class)
        ->and($notif->data['tipo'])->toBe('cp_aprobado')
        ->and($notif->data['cp_numero'])->toBe($p->cp_numero)
        ->and($notif->data['url'])->toContain('/oportunidades/');
});

it('endpoint /notificaciones requiere autenticación', function () {
    $this->get(route('notificaciones.index'))->assertRedirect('/login');
});

it('marcar leída individualmente actualiza read_at', function () {
    $p = Proyecto::factory()->create(['estado' => 'en_revision', 'director_dn_id' => $this->dg->id]);

    $this->actingAs($this->dg)
        ->post(route('oportunidades.aprobarCp', $p), [
            'gerente_proyectos_id' => $this->gp->id,
            'comentario' => 'Test',
            'decision' => 'aprobar',
        ]);

    $notif = $this->gp->fresh()->notifications()->first();
    expect($notif->read_at)->toBeNull();

    $this->actingAs($this->gp)
        ->patch(route('notificaciones.marcar', $notif->id))
        ->assertRedirect();

    expect($notif->fresh()->read_at)->not->toBeNull();
});

it('marcar todo leído marca todas como leídas', function () {
    $p = Proyecto::factory()->create(['estado' => 'en_revision', 'director_dn_id' => $this->dg->id]);

    $this->actingAs($this->dg)
        ->post(route('oportunidades.aprobarCp', $p), [
            'gerente_proyectos_id' => $this->gp->id,
            'comentario' => 'Test',
            'decision' => 'aprobar',
        ]);

    $this->actingAs($this->gp)
        ->post(route('notificaciones.marcar-todo'))
        ->assertRedirect();

    expect($this->gp->fresh()->unreadNotifications->count())->toBe(0);
});

it('index muestra notificaciones del usuario actual', function () {
    $p = Proyecto::factory()->create(['estado' => 'en_revision', 'director_dn_id' => $this->dg->id]);

    $this->actingAs($this->dg)
        ->post(route('oportunidades.aprobarCp', $p), [
            'gerente_proyectos_id' => $this->gp->id,
            'comentario' => 'Test',
            'decision' => 'aprobar',
        ]);

    $this->actingAs($this->gp)
        ->get(route('notificaciones.index'))
        ->assertOk()
        ->assertSee($p->cp_numero);
});

it('un usuario no ve notificaciones de otro', function () {
    $p = Proyecto::factory()->create(['estado' => 'en_revision', 'director_dn_id' => $this->dg->id]);

    $this->actingAs($this->dg)
        ->post(route('oportunidades.aprobarCp', $p), [
            'gerente_proyectos_id' => $this->gp->id,
            'comentario' => 'Test',
            'decision' => 'aprobar',
        ]);

    expect($this->ic->fresh()->notifications()->count())->toBe(0)
        ->and($this->gp->fresh()->notifications()->count())->toBe(1);
});
