<?php

use App\Models\Cliente;
use App\Models\LibroSeccion;
use App\Models\Proyecto;
use App\Models\Sublinea;
use App\Services\Libro\AperturaLibroService;
use Database\Seeders\ComercialCatalogosSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

beforeEach(function () {
    $this->seed(ComercialCatalogosSeeder::class);
});

it('abre el libro con las 10 secciones A-J', function () {
    $proyecto = Proyecto::create([
        'cliente_id' => Cliente::first()->id,
        'sublinea_id' => Sublinea::where('codigo', 'HTP')->first()->id,
        'año' => 2026,
    ]);

    $libro = app(AperturaLibroService::class)->abrirParaProyecto($proyecto);

    expect($libro->secciones)->toHaveCount(10)
        ->and($libro->secciones->pluck('codigo')->all())
        ->toBe(['A', 'B', 'C', 'D', 'E', 'F', 'G', 'H', 'I', 'J']);
});

it('siembra checklist específico para HTP en sección F', function () {
    $proyecto = Proyecto::create([
        'cliente_id' => Cliente::first()->id,
        'sublinea_id' => Sublinea::where('codigo', 'HTP')->first()->id,
        'año' => 2026,
    ]);

    $libro = app(AperturaLibroService::class)->abrirParaProyecto($proyecto);
    $seccionF = $libro->secciones->firstWhere('codigo', 'F');

    expect($seccionF->checklist->pluck('item_descripcion')->all())
        ->toContain('Certificados de soldadores asignados')
        ->and($seccionF->checklist->pluck('item_descripcion')->all())
        ->toContain('Certificados de operadores Hot Tap (T-101, TM-760, TM-1200)');
});

it('recalcula avance global cuando se completan items del checklist', function () {
    $proyecto = Proyecto::create([
        'cliente_id' => Cliente::first()->id,
        'sublinea_id' => Sublinea::where('codigo', 'SG')->first()->id,
        'año' => 2026,
    ]);

    $libro = app(AperturaLibroService::class)->abrirParaProyecto($proyecto);
    $seccion = $libro->secciones->first();

    $seccion->checklist->each(fn ($i) => $i->update(['completado' => true]));
    $seccion->recalcularAvance();
    $libro->refresh();

    expect((float) $seccion->fresh()->porcentaje_avance)->toBe(100.0)
        ->and($seccion->fresh()->estado)->toBe('completo')
        ->and((float) $libro->porcentaje_avance_global)->toBeGreaterThan(0);
});

it('es idempotente — abrir dos veces no duplica secciones', function () {
    $proyecto = Proyecto::create([
        'cliente_id' => Cliente::first()->id,
        'sublinea_id' => Sublinea::where('codigo', 'SG')->first()->id,
        'año' => 2026,
    ]);

    $svc = app(AperturaLibroService::class);
    $svc->abrirParaProyecto($proyecto);
    $svc->abrirParaProyecto($proyecto);

    expect(LibroSeccion::count())->toBe(10);
});
