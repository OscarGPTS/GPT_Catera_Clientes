<?php

use App\Models\BomBoeItem;
use App\Models\Proyecto;
use App\Models\User;
use App\Services\Procura\SuministrosService;
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
    ]);
});

it('abrir listado es idempotente', function () {
    $service = app(SuministrosService::class);
    $a = $service->abrirParaProyecto($this->proyecto);
    $b = $service->abrirParaProyecto($this->proyecto);

    expect($a->id)->toBe($b->id);
});

it('importar desde BOM mapea status a etapas correctas', function () {
    $items = [
        ['status' => 'en_almacen', 'esperado_etapa' => '76-100'],
        ['status' => 'por_comprar', 'esperado_etapa' => '0-25'],
        ['status' => 'por_fabricar', 'esperado_etapa' => '26-50'],
        ['status' => 'en_transito', 'esperado_etapa' => '51-75'],
    ];

    foreach ($items as $i => $item) {
        BomBoeItem::create([
            'proyecto_id' => $this->proyecto->id,
            'tipo' => 'BOM',
            'descripcion' => "Item {$i}",
            'cantidad' => 1,
            'status' => $item['status'],
        ]);
    }

    $count = app(SuministrosService::class)->importarDesdeBom($this->proyecto->fresh());

    expect($count)->toBe(4);

    $listado = $this->proyecto->fresh()->listadoSuministros;
    foreach ($items as $i => $item) {
        $sumItem = $listado->items()->where('descripcion', "Item {$i}")->first();
        expect($sumItem->etapa)->toBe($item['esperado_etapa']);
    }
});

it('importar desde BOM no duplica items por descripción', function () {
    BomBoeItem::create([
        'proyecto_id' => $this->proyecto->id,
        'tipo' => 'BOM', 'descripcion' => 'Brida', 'cantidad' => 1, 'status' => 'por_comprar',
    ]);

    $service = app(SuministrosService::class);
    $service->importarDesdeBom($this->proyecto->fresh());
    $count = $service->importarDesdeBom($this->proyecto->fresh());

    expect($count)->toBe(0)
        ->and($this->proyecto->fresh()->listadoSuministros->items()->count())->toBe(1);
});

it('avance global se recalcula al actualizar status', function () {
    $service = app(SuministrosService::class);
    $listado = $service->abrirParaProyecto($this->proyecto);
    $a = $service->crearItem($this->proyecto, ['descripcion' => 'A', 'cantidad' => 1, 'status' => 'definicion']);
    $service->crearItem($this->proyecto, ['descripcion' => 'B', 'cantidad' => 1, 'status' => 'definicion']);

    expect((float) $listado->fresh()->porcentaje_avance_global)->toBe(10.0);

    $service->actualizarItem($a, ['status' => 'entregado']);

    expect((float) $listado->fresh()->porcentaje_avance_global)->toBe(55.0); // (100 + 10) / 2
});

it('crear item via HTTP persiste con etapa derivada del status', function () {
    $this->actingAs($this->gp)
        ->post(route('suministros.items.store', $this->proyecto), [
            'descripcion' => 'Cable',
            'cantidad' => 100,
            'unidad' => 'm',
            'status' => 'cotizando',
        ])
        ->assertRedirect();

    $item = $this->proyecto->fresh()->listadoSuministros->items()->first();

    expect($item->etapa)->toBe('26-50')
        ->and((int) $item->porcentaje_avance)->toBe(40);
});

it('eliminar item recalcula avance', function () {
    $service = app(SuministrosService::class);
    $listado = $service->abrirParaProyecto($this->proyecto);
    $a = $service->crearItem($this->proyecto, ['descripcion' => 'A', 'cantidad' => 1, 'status' => 'entregado']);
    $service->crearItem($this->proyecto, ['descripcion' => 'B', 'cantidad' => 1, 'status' => 'definicion']);

    expect((float) $listado->fresh()->porcentaje_avance_global)->toBe(55.0);

    $this->actingAs($this->gp)
        ->delete(route('suministros.items.destroy', [$this->proyecto, $a]))
        ->assertRedirect();

    expect((float) $listado->fresh()->porcentaje_avance_global)->toBe(10.0);
});
