<?php

use App\Models\LibroDocumento;
use App\Models\LibroProyecto;
use App\Models\Proyecto;
use App\Models\SystemSetting;
use App\Models\User;
use App\Services\Libro\AperturaLibroService;
use App\Services\Libro\LibroService;
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

    $this->qhse = User::factory()->create();
    $this->qhse->assignRole('qhse');

    $this->proyecto = Proyecto::factory()->create([
        'estado' => 'en_ejecucion',
        'gerente_proyectos_id' => $this->gp->id,
    ]);

    $this->libro = app(AperturaLibroService::class)->abrirParaProyecto($this->proyecto);
});

it('show requiere proyecto en ejecución+', function () {
    $p = Proyecto::factory()->create(['estado' => 'cotizando']);

    $this->actingAs($this->gp)
        ->from(route('oportunidades.show', $p))
        ->get(route('libro.show', $p))
        ->assertRedirect(route('oportunidades.show', $p))
        ->assertSessionHasErrors(['libro']);
});

it('show abre acordeón con 10 secciones', function () {
    $this->actingAs($this->gp)
        ->get(route('libro.show', $this->proyecto))
        ->assertOk()
        ->assertSee('Libro de Proyecto')
        ->assertSee('Cronograma de Actividades')
        ->assertSee('Misceláneos');
});

it('toggle de item recalcula avance de la sección y del libro', function () {
    $seccion = $this->libro->secciones()->where('codigo', 'A')->first();
    $items = $seccion->checklist;
    expect($items)->not->toBeEmpty();

    $primero = $items->first();

    $this->actingAs($this->gp)
        ->patch(route('libro.items.toggle', [$this->proyecto, $primero]))
        ->assertRedirect();

    $seccion->refresh();
    $primero->refresh();

    expect($primero->completado)->toBeTrue()
        ->and($primero->completado_por_id)->toBe($this->gp->id)
        ->and((float) $seccion->porcentaje_avance)->toBeGreaterThan(0);
});

it('agregar item al checklist persiste y recalcula', function () {
    $seccion = $this->libro->secciones()->where('codigo', 'C')->first();
    $countAntes = $seccion->checklist()->count();

    $this->actingAs($this->gp)
        ->post(route('libro.items.store', [$this->proyecto, $seccion]), [
            'item_descripcion' => 'Permiso adicional del cliente final',
        ])
        ->assertRedirect();

    expect($seccion->checklist()->count())->toBe($countAntes + 1);
});

it('eliminar item recalcula', function () {
    $seccion = $this->libro->secciones()->where('codigo', 'A')->first();
    $item = $seccion->checklist()->first();

    $this->actingAs($this->gp)
        ->delete(route('libro.items.destroy', [$this->proyecto, $item]))
        ->assertRedirect();

    expect($seccion->checklist()->whereKey($item->id)->exists())->toBeFalse();
});

it('actualizar sección guarda responsable y observaciones', function () {
    $seccion = $this->libro->secciones()->where('codigo', 'H')->first();

    $this->actingAs($this->gp)
        ->patch(route('libro.secciones.update', [$this->proyecto, $seccion]), [
            'responsable_id' => $this->qhse->id,
            'observaciones' => 'QHSE actualiza esta sección semanalmente.',
        ])
        ->assertRedirect();

    $seccion->refresh();
    expect($seccion->responsable_id)->toBe($this->qhse->id)
        ->and($seccion->observaciones)->toBe('QHSE actualiza esta sección semanalmente.');
});

it('subir documento crea archivo y opcionalmente vincula a item', function () {
    Storage::fake('local');

    $seccion = $this->libro->secciones()->where('codigo', 'F')->first();
    $item = $seccion->checklist()->first();

    $this->actingAs($this->gp)
        ->post(route('libro.documentos.upload', [$this->proyecto, $seccion]), [
            'archivo' => UploadedFile::fake()->create('certificado.pdf', 200),
            'link_checklist_id' => $item->id,
        ])
        ->assertRedirect();

    $doc = $seccion->documentos()->first();
    expect($doc)->not->toBeNull()
        ->and($doc->subido_por_id)->toBe($this->gp->id)
        ->and($item->fresh()->evidencia_documento_id)->toBe($doc->id)
        ->and($item->fresh()->completado)->toBeTrue();

    Storage::disk('local')->assertExists($doc->archivo_path);
});

it('subir mismo nombre incrementa la versión', function () {
    Storage::fake('local');

    $seccion = $this->libro->secciones()->where('codigo', 'F')->first();

    foreach (range(1, 3) as $_) {
        $this->actingAs($this->gp)
            ->post(route('libro.documentos.upload', [$this->proyecto, $seccion]), [
                'archivo' => UploadedFile::fake()->create('plan.pdf', 100),
            ]);
    }

    $versions = $seccion->documentos()->where('nombre', 'plan.pdf')->orderBy('version')->pluck('version')->all();
    expect($versions)->toBe([1, 2, 3]);
});

it('descargar documento devuelve archivo', function () {
    Storage::fake('local');

    $seccion = $this->libro->secciones()->where('codigo', 'F')->first();
    $this->actingAs($this->gp)
        ->post(route('libro.documentos.upload', [$this->proyecto, $seccion]), [
            'archivo' => UploadedFile::fake()->create('cert.pdf', 100),
        ]);

    $doc = $seccion->documentos()->first();

    $this->actingAs($this->gp)
        ->get(route('libro.documentos.descargar', [$this->proyecto, $doc]))
        ->assertOk();
});

it('eliminar documento limpia el archivo y la referencia en checklist', function () {
    Storage::fake('local');

    $seccion = $this->libro->secciones()->where('codigo', 'F')->first();
    $item = $seccion->checklist()->first();

    $this->actingAs($this->gp)
        ->post(route('libro.documentos.upload', [$this->proyecto, $seccion]), [
            'archivo' => UploadedFile::fake()->create('cert.pdf', 100),
            'link_checklist_id' => $item->id,
        ]);

    $doc = $seccion->documentos()->first();

    $this->actingAs($this->gp)
        ->delete(route('libro.documentos.destroy', [$this->proyecto, $doc]))
        ->assertRedirect();

    expect(LibroDocumento::find($doc->id))->toBeNull()
        ->and($item->fresh()->evidencia_documento_id)->toBeNull();

    Storage::disk('local')->assertMissing($doc->archivo_path);
});

it('D11 bloqueo se evalúa según el setting bloqueo_cierre_dossier_incompleto', function () {
    $service = app(LibroService::class);

    SystemSetting::set('bloqueo_cierre_dossier_incompleto', true, 'boolean');
    $eval = $service->evaluarBloqueoCierre($this->libro);
    expect($eval['bloqueado'])->toBeTrue()
        ->and(count($eval['razones']))->toBe(10); // las 10 secciones a 0%

    // Si desactivamos la regla
    SystemSetting::set('bloqueo_cierre_dossier_incompleto', false, 'boolean');
    $eval2 = $service->evaluarBloqueoCierre($this->libro);
    expect($eval2['bloqueado'])->toBeFalse()
        ->and($eval2['razones'])->toBeEmpty();
});

it('completar 100% del libro libera el bloqueo de cierre', function () {
    SystemSetting::set('bloqueo_cierre_dossier_incompleto', true, 'boolean');
    $service = app(LibroService::class);

    foreach ($this->libro->secciones as $seccion) {
        foreach ($seccion->checklist as $item) {
            $service->toggleChecklistItem($item, $this->gp->id);
        }
    }

    $libro = LibroProyecto::find($this->libro->id);
    $service->actualizarBloqueoCierre($libro);

    expect((float) $libro->fresh()->porcentaje_avance_global)->toBe(100.0)
        ->and($libro->fresh()->bloqueado_para_cierre)->toBeFalse();
});

it('item de otro proyecto retorna 404', function () {
    $otroP = Proyecto::factory()->create(['estado' => 'en_ejecucion']);
    app(AperturaLibroService::class)->abrirParaProyecto($otroP);

    $itemOtro = $otroP->libro->secciones()->first()->checklist()->first();

    $this->actingAs($this->gp)
        ->patch(route('libro.items.toggle', [$this->proyecto, $itemOtro]))
        ->assertNotFound();
});
