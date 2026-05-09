<?php

use App\Models\Proyecto;
use App\Models\User;
use App\Services\Ejecutivo\ReporteEjecutivoPdfGenerator;
use App\Services\Ejecutivo\ResumenEjecutivoExcelExporter;
use App\Services\Libro\AperturaLibroService;
use App\Services\Libro\DossierConsolidadoGenerator;
use App\Services\Proyectos\FichaProyectoPdfGenerator;
use Database\Seeders\ComercialCatalogosSeeder;
use Database\Seeders\RhRoleMappingSeeder;
use Database\Seeders\RolesPermissionsSeeder;
use Database\Seeders\SystemSettingsSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Storage;
use PhpOffice\PhpSpreadsheet\Reader\Xlsx;

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

    $this->proyecto = Proyecto::factory()->create([
        'estado' => 'en_ejecucion',
        'director_dn_id' => $this->dg->id,
        'gerente_proyectos_id' => $this->gp->id,
        'cp_numero' => 'CP-001/26',
        'monto_preliminar' => 500_000,
    ]);
});

it('FichaProyectoPdfGenerator genera PDF para un proyecto', function () {
    Storage::fake('local');

    $path = app(FichaProyectoPdfGenerator::class)->generar($this->proyecto);

    expect(Storage::disk('local')->exists($path))->toBeTrue()
        ->and($path)->toContain('fichas/');
});

it('endpoint /oportunidades/{p}/ficha devuelve PDF', function () {
    $this->actingAs($this->gp)
        ->get(route('oportunidades.ficha', $this->proyecto))
        ->assertOk()
        ->assertHeader('content-type', 'application/pdf');
});

it('DossierConsolidadoGenerator genera PDF y persiste md5 + path en libro', function () {
    Storage::fake('local');

    $libro = app(AperturaLibroService::class)->abrirParaProyecto($this->proyecto);

    $path = app(DossierConsolidadoGenerator::class)->generar($libro);

    expect(Storage::disk('local')->exists($path))->toBeTrue()
        ->and($libro->fresh()->pdf_consolidado_path)->toBe($path)
        ->and($libro->fresh()->pdf_consolidado_md5)->not->toBeNull();
});

it('endpoint /libro/dossier requiere libro abierto', function () {
    $sinLibro = Proyecto::factory()->create(['estado' => 'cotizando']);

    $this->actingAs($this->gp)
        ->get(route('libro.dossier', $sinLibro))
        ->assertNotFound();

    app(AperturaLibroService::class)->abrirParaProyecto($this->proyecto);

    $this->actingAs($this->gp)
        ->get(route('libro.dossier', $this->proyecto))
        ->assertOk()
        ->assertHeader('content-type', 'application/pdf');
});

it('ReporteEjecutivoPdfGenerator genera PDF del año', function () {
    Storage::fake('local');

    $path = app(ReporteEjecutivoPdfGenerator::class)->generar(2026);

    expect(Storage::disk('local')->exists($path))->toBeTrue()
        ->and($path)->toBe('ejecutivo/reporte-ejecutivo-2026.pdf');
});

it('endpoint /ejecutivo/pdf devuelve PDF (con autorización)', function () {
    $this->actingAs($this->dg)
        ->get(route('ejecutivo.pdf', ['año' => 2026]))
        ->assertOk()
        ->assertHeader('content-type', 'application/pdf');
});

it('endpoint /ejecutivo/excel devuelve XLSX (con autorización)', function () {
    $resp = $this->actingAs($this->dg)
        ->get(route('ejecutivo.excel', ['año' => 2026]))
        ->assertOk();

    $contentType = $resp->headers->get('content-type');
    expect($contentType)->toContain('spreadsheetml');
});

it('ResumenEjecutivoExcelExporter genera xlsx con 3 hojas', function () {
    Storage::fake('local');

    $path = app(ResumenEjecutivoExcelExporter::class)->exportar(2026);

    expect(Storage::disk('local')->exists($path))->toBeTrue()
        ->and($path)->toBe('ejecutivo/resumen-ejecutivo-2026.xlsx');

    // Verificar que es un archivo XLSX válido leyéndolo
    $reader = new Xlsx;
    $sb = $reader->load(Storage::disk('local')->path($path));

    expect($sb->getSheetCount())->toBe(3)
        ->and($sb->getSheet(0)->getTitle())->toBe('KPIs')
        ->and($sb->getSheet(1)->getTitle())->toBe('Proyectos')
        ->and($sb->getSheet(2)->getTitle())->toBe('Concentración');
});

it('ejecutivo PDF/Excel rechaza usuarios sin rol financiero/ejecutivo', function () {
    $comercial = User::factory()->create();
    $comercial->assignRole('comercial');

    $this->actingAs($comercial)
        ->get(route('ejecutivo.pdf'))
        ->assertForbidden();

    $this->actingAs($comercial)
        ->get(route('ejecutivo.excel'))
        ->assertForbidden();
});

it('Excel contiene hoja KPIs con encabezado y al menos los KPIs base', function () {
    Storage::fake('local');

    $path = app(ResumenEjecutivoExcelExporter::class)->exportar(2026);

    $reader = new Xlsx;
    $sb = $reader->load(Storage::disk('local')->path($path));
    $sheet = $sb->getSheetByName('KPIs');

    expect($sheet->getCell('A1')->getValue())->toContain('RESUMEN EJECUTIVO 2026');

    $textos = [];
    foreach ($sheet->getRowIterator() as $row) {
        foreach ($row->getCellIterator() as $cell) {
            $textos[] = (string) $cell->getValue();
        }
    }
    $blob = implode(' ', $textos);

    expect($blob)->toContain('Pipeline')
        ->and($blob)->toContain('Hit rate')
        ->and($blob)->toContain('SEDENA');
});
