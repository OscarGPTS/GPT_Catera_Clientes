<?php

use App\Models\ChatMencion;
use App\Models\Proyecto;
use App\Models\User;
use App\Services\Chat\ChatService;
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

    $this->dg = User::factory()->create(['name' => 'Diana Garza']);
    $this->dg->assignRole('direccion_general');

    $this->gp = User::factory()->create(['name' => 'Fernando Basave']);
    $this->gp->assignRole('gerente_proyectos');

    $this->ip = User::factory()->create(['name' => 'Juan Sanchez']);
    $this->ip->assignRole('ingeniero_proyectos');

    $this->proyecto = Proyecto::factory()->create([
        'estado' => 'en_ejecucion',
        'director_dn_id' => $this->dg->id,
        'gerente_proyectos_id' => $this->gp->id,
        'ingeniero_proyectos_id' => $this->ip->id,
    ]);
});

it('canalParaProyecto crea canal con miembros del equipo y es idempotente', function () {
    $service = app(ChatService::class);

    $canal = $service->canalParaProyecto($this->proyecto);

    expect($canal->tipo)->toBe('proyecto')
        ->and($canal->contexto_id)->toBe($this->proyecto->id)
        ->and($canal->miembros->pluck('id')->all())->toContain($this->dg->id, $this->gp->id, $this->ip->id);

    $canal2 = $service->canalParaProyecto($this->proyecto);
    expect($canal2->id)->toBe($canal->id);
});

it('enviarMensaje persiste y crea entry de lectura para el autor', function () {
    $service = app(ChatService::class);
    $canal = $service->canalParaProyecto($this->proyecto);

    $msg = $service->enviarMensaje($canal, $this->gp->id, 'Equipo, arrancamos hoy.');

    expect($msg->contenido)->toBe('Equipo, arrancamos hoy.')
        ->and($msg->user_id)->toBe($this->gp->id)
        ->and($canal->lecturas()->where('user_id', $this->gp->id)->first()?->ultimo_mensaje_leido_id)->toBe($msg->id);
});

it('detecta menciones por nombre y crea registros', function () {
    $service = app(ChatService::class);
    $canal = $service->canalParaProyecto($this->proyecto);

    $msg = $service->enviarMensaje($canal, $this->gp->id, '@juan.sanchez por favor sube la bitácora');

    expect($msg->menciones->pluck('user_id')->all())->toContain($this->ip->id);
});

it('detecta menciones por parte local del email', function () {
    $service = app(ChatService::class);
    $canal = $service->canalParaProyecto($this->proyecto);

    $emailLocal = strstr($this->ip->email, '@', true);
    $msg = $service->enviarMensaje($canal, $this->gp->id, "@{$emailLocal} ¿cómo va el avance?");

    expect($msg->menciones()->where('user_id', $this->ip->id)->exists())->toBeTrue();
});

it('no se auto-menciona', function () {
    $service = app(ChatService::class);
    $canal = $service->canalParaProyecto($this->proyecto);

    $emailLocal = strstr($this->gp->email, '@', true);
    $msg = $service->enviarMensaje($canal, $this->gp->id, "@{$emailLocal} hablo conmigo mismo");

    expect($msg->menciones()->where('user_id', $this->gp->id)->exists())->toBeFalse();
});

it('mensajes no leídos se cuentan correctamente por usuario', function () {
    $service = app(ChatService::class);
    $canal = $service->canalParaProyecto($this->proyecto);

    $service->enviarMensaje($canal, $this->gp->id, 'Mensaje 1');
    $service->enviarMensaje($canal, $this->gp->id, 'Mensaje 2');

    expect($canal->mensajesNoLeidosPara($this->ip->id))->toBe(2)
        ->and($canal->mensajesNoLeidosPara($this->gp->id))->toBe(0);

    $service->marcarLeido($canal, $this->ip->id);

    expect($canal->mensajesNoLeidosPara($this->ip->id))->toBe(0);
});

it('endpoint /chat/proyecto/{p} crea canal y redirige', function () {
    $this->actingAs($this->gp)
        ->post(route('chat.proyecto', $this->proyecto))
        ->assertRedirect();

    expect($this->proyecto->fresh())->not->toBeNull();
});

it('canalesParaUsuario retorna solo canales del miembro', function () {
    $service = app(ChatService::class);
    $canal = $service->canalParaProyecto($this->proyecto);

    $extranio = User::factory()->create();

    expect($service->canalesParaUsuario($this->gp->id)->pluck('id')->all())->toContain($canal->id)
        ->and($service->canalesParaUsuario($extranio->id)->pluck('id')->all())->not->toContain($canal->id);
});

it('agregar mensaje desde NO miembro lo agrega como miembro automáticamente', function () {
    $service = app(ChatService::class);
    $canal = $service->canalParaProyecto($this->proyecto);

    $extra = User::factory()->create();
    expect($canal->miembros()->where('user_id', $extra->id)->exists())->toBeFalse();

    $service->enviarMensaje($canal, $extra->id, 'Hola, soy nuevo aquí.');

    expect($canal->fresh()->miembros()->where('user_id', $extra->id)->exists())->toBeTrue();
});

it('mención crea ChatMencion no leída para el usuario mencionado', function () {
    $service = app(ChatService::class);
    $canal = $service->canalParaProyecto($this->proyecto);

    $service->enviarMensaje($canal, $this->gp->id, '@juan.sanchez urgente');

    $m = ChatMencion::where('user_id', $this->ip->id)->first();
    expect($m)->not->toBeNull()
        ->and($m->leido_at)->toBeNull();
});
