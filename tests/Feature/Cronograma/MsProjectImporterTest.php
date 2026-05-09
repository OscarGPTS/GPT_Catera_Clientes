<?php

use App\Models\Proyecto;
use App\Models\User;
use App\Services\Proyectos\MsProject\CronogramaImporterService;
use App\Services\Proyectos\MsProject\MsProjectCsvParser;
use App\Services\Proyectos\MsProject\MsProjectXmlParser;
use Database\Seeders\ComercialCatalogosSeeder;
use Database\Seeders\RhRoleMappingSeeder;
use Database\Seeders\RolesPermissionsSeeder;
use Database\Seeders\SystemSettingsSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;

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

it('XmlParser parsea Project XML con tasks, fechas y predecesoras', function () {
    $xml = <<<'XML'
<?xml version="1.0" encoding="UTF-8" standalone="yes"?>
<Project xmlns="http://schemas.microsoft.com/project">
    <Tasks>
        <Task><UID>0</UID><ID>0</ID><Name>Resumen del proyecto</Name><OutlineLevel>0</OutlineLevel></Task>
        <Task>
            <UID>1</UID><ID>1</ID><Name>Movilización</Name>
            <Start>2026-05-01T08:00:00</Start>
            <Finish>2026-05-05T17:00:00</Finish>
            <PercentComplete>100</PercentComplete>
            <OutlineLevel>1</OutlineLevel>
        </Task>
        <Task>
            <UID>2</UID><ID>2</ID><Name>Soldadura</Name>
            <Start>2026-05-06T08:00:00</Start>
            <Finish>2026-05-15T17:00:00</Finish>
            <PercentComplete>50</PercentComplete>
            <OutlineLevel>1</OutlineLevel>
            <PredecessorLink><PredecessorUID>1</PredecessorUID></PredecessorLink>
        </Task>
    </Tasks>
</Project>
XML;
    $tmp = tempnam(sys_get_temp_dir(), 'mpp').'.xml';
    file_put_contents($tmp, $xml);

    $acts = (new MsProjectXmlParser)->parse($tmp);

    expect($acts)->toHaveCount(2)
        ->and($acts[0]['nombre'])->toBe('Movilización')
        ->and($acts[0]['fecha_inicio_planeada'])->toBe('2026-05-01')
        ->and($acts[0]['porcentaje_avance'])->toBe(100.0)
        ->and($acts[1]['predecesoras'])->toBe(['1']);

    unlink($tmp);
});

it('XmlParser descarta task UID=0 (resumen del proyecto)', function () {
    $xml = '<?xml version="1.0"?><Project><Tasks>
        <Task><UID>0</UID><ID>0</ID><Name>Resumen</Name></Task>
        <Task><UID>1</UID><ID>1</ID><Name>Real</Name><Start>2026-05-01</Start><Finish>2026-05-02</Finish></Task>
    </Tasks></Project>';
    $tmp = tempnam(sys_get_temp_dir(), 'mpp').'.xml';
    file_put_contents($tmp, $xml);

    expect((new MsProjectXmlParser)->parse($tmp))->toHaveCount(1);
    unlink($tmp);
});

it('CsvParser parsea encabezados en español', function () {
    $csv = <<<'CSV'
ID;Nombre;Comienzo;Fin;% Completado;Predecesoras;Nivel
1;Movilización;2026-05-01;2026-05-05;100;;1
2;Soldadura;2026-05-06;2026-05-15;50;1;1
3;Inspección NDT;2026-05-16;2026-05-20;0;2;1
CSV;
    $tmp = tempnam(sys_get_temp_dir(), 'mpp').'.csv';
    file_put_contents($tmp, $csv);

    $acts = (new MsProjectCsvParser)->parse($tmp);

    expect($acts)->toHaveCount(3)
        ->and($acts[0]['nombre'])->toBe('Movilización')
        ->and($acts[1]['predecesoras'])->toBe(['1'])
        ->and($acts[2]['porcentaje_avance'])->toBe(0.0);

    unlink($tmp);
});

it('CsvParser parsea encabezados en inglés con coma', function () {
    $csv = "ID,Name,Start,Finish,% Complete\n1,Mobilization,2026-05-01,2026-05-05,100\n2,Welding,2026-05-06,2026-05-15,50";
    $tmp = tempnam(sys_get_temp_dir(), 'mpp').'.csv';
    file_put_contents($tmp, $csv);

    $acts = (new MsProjectCsvParser)->parse($tmp);

    expect($acts)->toHaveCount(2)
        ->and($acts[1]['nombre'])->toBe('Welding');

    unlink($tmp);
});

it('CsvParser rechaza archivo sin header Nombre/Name', function () {
    $tmp = tempnam(sys_get_temp_dir(), 'mpp').'.csv';
    file_put_contents($tmp, 'foo,bar,baz');

    expect(fn () => (new MsProjectCsvParser)->parse($tmp))->toThrow(RuntimeException::class);

    unlink($tmp);
});

it('importador rechaza .mpp binario con mensaje útil', function () {
    Storage::fake('local');

    $f = UploadedFile::fake()->createWithContent('proyecto.mpp', 'binarycontent');

    expect(fn () => app(CronogramaImporterService::class)->importar($this->proyecto, $f, $this->gp->id))
        ->toThrow(RuntimeException::class, '.mpp binario no soportado');
});

it('importador crea nueva versión con actividades y registra evento', function () {
    Storage::fake('local');

    $csv = "ID,Name,Start,Finish,% Complete\n1,Movilización,2026-05-01,2026-05-05,100\n2,Soldadura,2026-05-06,2026-05-15,50";
    $f = UploadedFile::fake()->createWithContent('cronograma.csv', $csv);

    $cron = app(CronogramaImporterService::class)->importar($this->proyecto, $f, $this->gp->id);

    expect($cron->actividades()->count())->toBe(2)
        ->and($cron->actividades()->where('codigo', '1')->first()?->nombre)->toBe('Movilización')
        ->and($cron->archivo_origen_path)->not->toBeNull()
        ->and($this->proyecto->fresh()->eventos()->where('tipo', 'cronograma_importado')->exists())->toBeTrue();
});

it('importador rechaza extensión no soportada', function () {
    Storage::fake('local');

    $f = UploadedFile::fake()->createWithContent('cronograma.docx', 'fake');

    expect(fn () => app(CronogramaImporterService::class)->importar($this->proyecto, $f, $this->gp->id))
        ->toThrow(RuntimeException::class);
});

it('importador rechaza si proyecto no es adjudicado_firmado o más', function () {
    $p = Proyecto::factory()->create(['estado' => 'cotizando']);

    Storage::fake('local');
    $f = UploadedFile::fake()->createWithContent('cronograma.csv', "ID,Name\n1,X");

    expect(fn () => app(CronogramaImporterService::class)->importar($p, $f, $this->gp->id))
        ->toThrow(RuntimeException::class);
});

it('endpoint /importar funciona vía HTTP', function () {
    Storage::fake('local');

    $csv = "ID,Name,Start,Finish,% Complete\n1,Movilización,2026-05-01,2026-05-05,100";
    $f = UploadedFile::fake()->createWithContent('cron.csv', $csv);

    $this->actingAs($this->gp)
        ->post(route('cronogramas.importar', $this->proyecto), ['archivo' => $f])
        ->assertRedirect();

    expect($this->proyecto->cronogramas()->count())->toBe(1);
});

it('endpoint /importar valida tipo de archivo', function () {
    Storage::fake('local');
    $f = UploadedFile::fake()->createWithContent('cron.docx', 'fake');

    $this->actingAs($this->gp)
        ->from(route('cronogramas.index', $this->proyecto))
        ->post(route('cronogramas.importar', $this->proyecto), ['archivo' => $f])
        ->assertSessionHasErrors(['archivo']);
});
