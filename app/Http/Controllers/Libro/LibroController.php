<?php

namespace App\Http\Controllers\Libro;

use App\Http\Controllers\Controller;
use App\Models\LibroDocumento;
use App\Models\LibroSeccion;
use App\Models\LibroSeccionChecklist;
use App\Models\Proyecto;
use App\Models\User;
use App\Services\Libro\AperturaLibroService;
use App\Services\Libro\DossierConsolidadoGenerator;
use App\Services\Libro\LibroService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Symfony\Component\HttpFoundation\StreamedResponse;

class LibroController extends Controller
{
    public function show(Proyecto $proyecto, AperturaLibroService $apertura, LibroService $service)
    {
        if (! in_array($proyecto->estado, ['en_ejecucion', 'en_cierre', 'cerrado'], true)) {
            return redirect()->route('oportunidades.show', $proyecto)
                ->withErrors(['libro' => 'El libro de proyecto se levanta al iniciar ejecución.']);
        }

        $libro = $proyecto->libro ?? $apertura->abrirParaProyecto($proyecto);
        $libro->load(['secciones.checklist.evidencia', 'secciones.checklist.completadoPor:id,name', 'secciones.responsable:id,name', 'secciones.documentos.subidoPor:id,name']);

        $proyecto->load(['cliente:id,razon_social,alias_3letras', 'sublinea:id,codigo,nombre']);

        return view('libro.show', [
            'proyecto' => $proyecto,
            'libro' => $libro,
            'bloqueo' => $service->evaluarBloqueoCierre($libro),
            'responsables' => User::role([
                'gerente_proyectos', 'gerente_operaciones',
                'ingeniero_proyectos', 'ingeniero_costos',
                'qhse', 'serv_tecnicos', 'compras', 'almacen',
            ])->orderBy('name')->get(['id', 'name']),
        ]);
    }

    public function toggleItem(Request $request, Proyecto $proyecto, LibroSeccionChecklist $item, LibroService $service): RedirectResponse
    {
        $this->ensureChecklistBelongsTo($proyecto, $item);

        $service->toggleChecklistItem($item, $request->user()->id);
        $service->actualizarBloqueoCierre($proyecto->libro);

        return back();
    }

    public function storeItem(Request $request, Proyecto $proyecto, LibroSeccion $seccion, LibroService $service): RedirectResponse
    {
        $this->ensureSeccionBelongsTo($proyecto, $seccion);

        $data = $request->validate([
            'item_descripcion' => ['required', 'string', 'min:3', 'max:200'],
        ]);

        $service->agregarItem($seccion, $data['item_descripcion']);
        $service->actualizarBloqueoCierre($proyecto->libro);

        return back()->with('status', 'Item agregado al checklist.');
    }

    public function destroyItem(Proyecto $proyecto, LibroSeccionChecklist $item, LibroService $service): RedirectResponse
    {
        $this->ensureChecklistBelongsTo($proyecto, $item);

        $service->eliminarItem($item);
        $service->actualizarBloqueoCierre($proyecto->libro);

        return back()->with('status', 'Item eliminado.');
    }

    public function updateSeccion(Request $request, Proyecto $proyecto, LibroSeccion $seccion, LibroService $service): RedirectResponse
    {
        $this->ensureSeccionBelongsTo($proyecto, $seccion);

        $data = $request->validate([
            'descripcion' => ['nullable', 'string'],
            'observaciones' => ['nullable', 'string'],
            'responsable_id' => ['nullable', 'exists:users,id'],
        ]);

        $service->actualizarSeccion($seccion, $data);

        return back()->with('status', "Sección {$seccion->codigo} actualizada.");
    }

    public function uploadDocumento(Request $request, Proyecto $proyecto, LibroSeccion $seccion, LibroService $service): RedirectResponse
    {
        $this->ensureSeccionBelongsTo($proyecto, $seccion);

        $request->validate([
            'archivo' => ['required', 'file', 'max:51200'], // 50 MB
            'link_checklist_id' => ['nullable', 'integer', 'exists:libro_seccion_checklist,id'],
        ]);

        $service->subirDocumento(
            $seccion,
            $request->file('archivo'),
            $request->user()->id,
            $request->integer('link_checklist_id') ?: null,
        );

        $service->actualizarBloqueoCierre($proyecto->libro);

        return back()->with('status', 'Documento subido.');
    }

    public function descargarDocumento(Proyecto $proyecto, LibroDocumento $documento, LibroService $service): StreamedResponse
    {
        $this->ensureDocumentoBelongsTo($proyecto, $documento);

        return $service->descargarDocumento($documento);
    }

    public function destroyDocumento(Proyecto $proyecto, LibroDocumento $documento, LibroService $service): RedirectResponse
    {
        $this->ensureDocumentoBelongsTo($proyecto, $documento);

        $service->eliminarDocumento($documento);
        $service->actualizarBloqueoCierre($proyecto->libro);

        return back()->with('status', 'Documento eliminado.');
    }

    public function dossier(Proyecto $proyecto, DossierConsolidadoGenerator $generator): StreamedResponse
    {
        if (! $proyecto->libro) {
            abort(404, 'El libro no está abierto para este proyecto.');
        }

        $path = $generator->generar($proyecto->libro);

        return Storage::disk('local')->download(
            $path,
            'Dossier-'.str_replace(['/', ' '], '-', $proyecto->cp_numero ?? "p{$proyecto->id}").'.pdf',
        );
    }

    private function ensureSeccionBelongsTo(Proyecto $proyecto, LibroSeccion $seccion): void
    {
        if ($proyecto->libro?->id !== $seccion->libro_id) {
            abort(404);
        }
    }

    private function ensureChecklistBelongsTo(Proyecto $proyecto, LibroSeccionChecklist $item): void
    {
        $libroId = $item->seccion?->libro_id;
        if (! $libroId || $libroId !== $proyecto->libro?->id) {
            abort(404);
        }
    }

    private function ensureDocumentoBelongsTo(Proyecto $proyecto, LibroDocumento $documento): void
    {
        $libroId = $documento->seccion?->libro_id;
        if (! $libroId || $libroId !== $proyecto->libro?->id) {
            abort(404);
        }
    }
}
