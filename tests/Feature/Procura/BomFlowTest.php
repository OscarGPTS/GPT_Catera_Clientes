<?php

use App\Models\BomBoeItem;
use App\Models\Proyecto;
use App\Models\User;
use App\Services\Cotizaciones\CotizacionService;
use App\Services\Procura\BomService;
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

    $this->ic = User::factory()->create();
    $this->ic->assignRole('ingeniero_costos');

    $this->proyecto = Proyecto::factory()->create([
        'estado' => 'en_ejecucion',
        'gerente_proyectos_id' => $this->gp->id,
    ]);
});

function emitirCotizacionCon(array $partidas, Proyecto $proyecto, User $ic): void
{
    $cot = app(CotizacionService::class)->crearBorrador($proyecto, $ic->id);
    foreach ($partidas as $i => $p) {
        $cot->partidas()->create([
            'numero_partida' => $i + 1,
            'descripcion' => $p['desc'],
            'cantidad' => $p['cantidad'],
            'unidad' => $p['unidad'],
            'costo_unitario' => 100,
            'costo_total' => 100 * $p['cantidad'],
        ]);
    }
    app(CotizacionService::class)->emitir($cot, $ic->id);
}

it('importar BOM clasifica BOM vs BOE y aplica heurística de status', function () {
    emitirCotizacionCon([
        ['desc' => 'Tee Hot Tap fabricado a medida', 'cantidad' => 1, 'unidad' => 'pza'],
        ['desc' => 'Máquina Hot Tap (renta)', 'cantidad' => 3, 'unidad' => 'día'],
        ['desc' => 'Cuadrilla de soldadores', 'cantidad' => 5, 'unidad' => 'jornada'],
        ['desc' => 'Brida ANSI 600', 'cantidad' => 4, 'unidad' => 'pza'],
    ], $this->proyecto, $this->ic);

    $count = app(BomService::class)->importarDesdeCotizacion($this->proyecto->fresh(), $this->gp->id);

    expect($count)->toBe(4);

    $items = $this->proyecto->bomBoeItems()->get();

    expect($items->where('descripcion', 'Brida ANSI 600')->first()->tipo)->toBe('BOM')
        ->and($items->where('descripcion', 'Brida ANSI 600')->first()->status)->toBe('por_comprar')
        ->and($items->where('descripcion', 'Tee Hot Tap fabricado a medida')->first()->status)->toBe('por_fabricar')
        ->and($items->where('descripcion', 'Máquina Hot Tap (renta)')->first()->tipo)->toBe('BOE')
        ->and($items->where('descripcion', 'Máquina Hot Tap (renta)')->first()->status)->toBe('en_almacen')
        ->and($items->where('descripcion', 'Cuadrilla de soldadores')->first()->tipo)->toBe('BOE');
});

it('importar BOM falla si no hay cotización emitida', function () {
    $this->actingAs($this->gp)
        ->from(route('bom.index', $this->proyecto))
        ->post(route('bom.importar', $this->proyecto))
        ->assertSessionHasErrors(['bom']);
});

it('importar BOM registra evento bom_importado', function () {
    emitirCotizacionCon([['desc' => 'Brida', 'cantidad' => 1, 'unidad' => 'pza']], $this->proyecto, $this->ic);

    $this->actingAs($this->gp)
        ->post(route('bom.importar', $this->proyecto))
        ->assertRedirect();

    expect($this->proyecto->fresh()->eventos()->where('tipo', 'bom_importado')->exists())->toBeTrue();
});

it('agregar item manualmente persiste con cast decimal', function () {
    $this->actingAs($this->gp)
        ->post(route('bom.store', $this->proyecto), [
            'tipo' => 'BOM',
            'descripcion' => 'Brida adicional',
            'cantidad' => 2.5,
            'unidad' => 'pza',
            'status' => 'por_comprar',
        ])
        ->assertRedirect();

    expect($this->proyecto->bomBoeItems()->count())->toBe(1);
});

it('actualizar status del item', function () {
    $item = BomBoeItem::create([
        'proyecto_id' => $this->proyecto->id,
        'tipo' => 'BOM',
        'descripcion' => 'X',
        'cantidad' => 1,
        'status' => 'por_comprar',
    ]);

    $this->actingAs($this->gp)
        ->patch(route('bom.update', [$this->proyecto, $item]), ['status' => 'en_transito'])
        ->assertRedirect();

    expect($item->fresh()->status)->toBe('en_transito');
});

it('eliminar item lo borra', function () {
    $item = BomBoeItem::create([
        'proyecto_id' => $this->proyecto->id,
        'tipo' => 'BOM', 'descripcion' => 'X', 'cantidad' => 1, 'status' => 'por_comprar',
    ]);

    $this->actingAs($this->gp)
        ->delete(route('bom.destroy', [$this->proyecto, $item]))
        ->assertRedirect();

    expect(BomBoeItem::find($item->id))->toBeNull();
});

it('item de otro proyecto retorna 404', function () {
    $otroProy = Proyecto::factory()->create(['estado' => 'en_ejecucion']);
    $item = BomBoeItem::create([
        'proyecto_id' => $this->proyecto->id,
        'tipo' => 'BOM', 'descripcion' => 'X', 'cantidad' => 1, 'status' => 'por_comprar',
    ]);

    $this->actingAs($this->gp)
        ->patch(route('bom.update', [$otroProy, $item]), ['status' => 'entregado'])
        ->assertNotFound();
});
