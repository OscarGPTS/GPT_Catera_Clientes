<?php

use App\Models\LibroProyecto;
use App\Models\Proyecto;
use Database\Seeders\ComercialCatalogosSeeder;
use Database\Seeders\ProyectosTestSeeder;
use Database\Seeders\RhRoleMappingSeeder;
use Database\Seeders\RolesPermissionsSeeder;
use Database\Seeders\SystemSettingsSeeder;
use Database\Seeders\TestUsersSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

beforeEach(function () {
    $this->seed([
        SystemSettingsSeeder::class,
        RolesPermissionsSeeder::class,
        RhRoleMappingSeeder::class,
        ComercialCatalogosSeeder::class,
        TestUsersSeeder::class,
        ProyectosTestSeeder::class,
    ]);
});

it('crea 10 oportunidades cubriendo todos los estados del flujo', function () {
    expect(Proyecto::count())->toBe(10);

    foreach ([
        'en_revision', 'cotizando', 'cotizado', 'presentado',
        'adjudicado_pendiente', 'adjudicado_firmado',
        'en_ejecucion', 'en_cierre', 'cerrado', 'perdido',
    ] as $estado) {
        expect(Proyecto::where('estado', $estado)->exists())->toBeTrue("Falta proyecto en estado {$estado}");
    }
});

it('proyectos adjudicados tienen DN asignado', function () {
    $adjudicados = Proyecto::whereIn('estado', ['adjudicado_firmado', 'en_ejecucion', 'en_cierre', 'cerrado'])->get();

    expect($adjudicados)->not->toBeEmpty();

    foreach ($adjudicados as $p) {
        expect($p->dn_numero)->not->toBeNull("Proyecto {$p->cp_numero} en estado {$p->estado} no tiene DN");
    }
});

it('proyectos en ejecución tienen libro abierto con 10 secciones', function () {
    $proyectos = Proyecto::whereIn('estado', ['en_ejecucion', 'en_cierre', 'cerrado'])->get();

    foreach ($proyectos as $p) {
        $libro = LibroProyecto::where('proyecto_id', $p->id)->first();
        expect($libro)->not->toBeNull("Proyecto {$p->cp_numero} no tiene libro")
            ->and($libro->secciones()->count())->toBe(10);
    }
});

it('cada proyecto tiene al menos un evento en su timeline', function () {
    foreach (Proyecto::all() as $p) {
        expect($p->eventos()->count())->toBeGreaterThan(0, "Proyecto {$p->cp_numero} sin eventos");
    }
});

it('proyectos en cotizando+ tienen al menos una cotización con partidas', function () {
    $proyectos = Proyecto::whereIn('estado', [
        'cotizando', 'cotizado', 'presentado',
        'adjudicado_pendiente', 'adjudicado_firmado',
        'en_ejecucion', 'en_cierre', 'cerrado',
    ])->get();

    foreach ($proyectos as $p) {
        $cot = $p->cotizaciones()->first();
        expect($cot)->not->toBeNull("Proyecto {$p->cp_numero} en estado {$p->estado} no tiene cotización")
            ->and($cot->partidas()->count())->toBeGreaterThan(0, "Cotización del proyecto {$p->cp_numero} sin partidas");
    }
});

it('proyectos en cotizado+ tienen cotización emitida', function () {
    $proyectos = Proyecto::whereIn('estado', [
        'cotizado', 'presentado', 'adjudicado_pendiente', 'adjudicado_firmado',
        'en_ejecucion', 'en_cierre', 'cerrado',
    ])->get();

    foreach ($proyectos as $p) {
        $emitida = $p->cotizaciones()->where('status', 'emitida')->first();
        expect($emitida)->not->toBeNull("Proyecto {$p->cp_numero} sin cotización emitida")
            ->and((float) $emitida->precio_venta_final)->toBeGreaterThan(0);
    }
});

it('proyectos adjudicados tienen minuta CP→DN', function () {
    $proyectos = Proyecto::whereIn('estado', [
        'adjudicado_pendiente', 'adjudicado_firmado',
        'en_ejecucion', 'en_cierre', 'cerrado',
    ])->get();

    foreach ($proyectos as $p) {
        expect($p->minutaEntrega)->not->toBeNull("Proyecto {$p->cp_numero} sin minuta CP→DN");
    }
});

it('proyectos en ejecución+ tienen minuta firmada', function () {
    $proyectos = Proyecto::whereIn('estado', [
        'en_ejecucion', 'en_cierre', 'cerrado',
    ])->get();

    foreach ($proyectos as $p) {
        expect($p->minutaEntrega?->status)->toBe('firmada', "Proyecto {$p->cp_numero} en {$p->estado} sin minuta firmada");
    }
});

it('proyectos en ejecución+ tienen KOMs interno y cliente', function () {
    $proyectos = Proyecto::whereIn('estado', [
        'en_ejecucion', 'en_cierre', 'cerrado',
    ])->get();

    foreach ($proyectos as $p) {
        expect($p->koms()->where('tipo', 'kom_interno')->exists())->toBeTrue("Proyecto {$p->cp_numero} sin KOM interno")
            ->and($p->koms()->where('tipo', 'kom_cliente')->exists())->toBeTrue("Proyecto {$p->cp_numero} sin KOM cliente");
    }
});

it('proyectos en ejecución+ tienen cronograma con actividades', function () {
    $proyectos = Proyecto::whereIn('estado', [
        'en_ejecucion', 'en_cierre', 'cerrado',
    ])->get();

    foreach ($proyectos as $p) {
        $cron = $p->cronogramas()->first();
        expect($cron)->not->toBeNull("Proyecto {$p->cp_numero} sin cronograma")
            ->and($cron->actividades()->count())->toBeGreaterThan(0, "Cronograma del {$p->cp_numero} sin actividades");
    }
});

it('KOM cliente referencia el cronograma', function () {
    $proyectos = Proyecto::whereIn('estado', ['en_ejecucion', 'en_cierre', 'cerrado'])->get();

    foreach ($proyectos as $p) {
        $kom = $p->koms()->where('tipo', 'kom_cliente')->first();
        expect($kom?->cronograma_attached_id)->not->toBeNull("KOM cliente del {$p->cp_numero} sin cronograma adjunto");
    }
});

it('CPs son secuenciales y únicos', function () {
    $cps = Proyecto::pluck('cp_numero')->all();
    $año = (int) substr((string) now()->year, -2);

    expect($cps)->toHaveCount(10)
        ->and(array_unique($cps))->toHaveCount(10);

    foreach ($cps as $cp) {
        expect($cp)->toMatch("/^CP-\d{3}\/{$año}$/");
    }
});

it('es idempotente — correr el seeder dos veces no agrega más proyectos', function () {
    $countBefore = Proyecto::count();

    $this->seed(ProyectosTestSeeder::class);

    expect(Proyecto::count())->toBe($countBefore);
});
