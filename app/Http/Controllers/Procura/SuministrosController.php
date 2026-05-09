<?php

namespace App\Http\Controllers\Procura;

use App\Http\Controllers\Controller;
use App\Models\ListadoSuministrosItem;
use App\Models\Proyecto;
use App\Services\Procura\SuministrosService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class SuministrosController extends Controller
{
    public function show(Proyecto $proyecto, SuministrosService $service)
    {
        $proyecto->load(['cliente:id,razon_social,alias_3letras', 'sublinea:id,codigo,nombre']);
        $listado = $service->abrirParaProyecto($proyecto);
        $listado->load('items');

        $porEtapa = $listado->items->groupBy('etapa');

        return view('suministros.show', [
            'proyecto' => $proyecto,
            'listado' => $listado,
            'porEtapa' => $porEtapa,
            'statusOptions' => SuministrosService::statusOptions(),
            'tieneBom' => $proyecto->bomBoeItems()->exists(),
        ]);
    }

    public function importar(Proyecto $proyecto, SuministrosService $service): RedirectResponse
    {
        $count = $service->importarDesdeBom($proyecto);

        return back()->with('status', "{$count} items importados desde el BOM al listado de suministros.");
    }

    public function store(Request $request, Proyecto $proyecto, SuministrosService $service): RedirectResponse
    {
        $data = $request->validate([
            'descripcion' => ['required', 'string', 'min:3'],
            'cantidad' => ['required', 'numeric', 'min:0.0001'],
            'unidad' => ['nullable', 'string', 'max:20'],
            'fecha_requerida' => ['nullable', 'date'],
            'status' => ['required', Rule::in(array_keys(SuministrosService::statusOptions()))],
        ]);

        $service->crearItem($proyecto, $data);

        return back()->with('status', 'Item agregado al listado.');
    }

    public function update(Request $request, Proyecto $proyecto, ListadoSuministrosItem $item, SuministrosService $service): RedirectResponse
    {
        if ($item->listado->proyecto_id !== $proyecto->id) {
            abort(404);
        }

        $data = $request->validate([
            'descripcion' => ['nullable', 'string', 'min:3'],
            'cantidad' => ['nullable', 'numeric', 'min:0.0001'],
            'unidad' => ['nullable', 'string', 'max:20'],
            'fecha_requerida' => ['nullable', 'date'],
            'status' => ['nullable', Rule::in(array_keys(SuministrosService::statusOptions()))],
        ]);

        $service->actualizarItem($item, $data);

        return back()->with('status', 'Item actualizado.');
    }

    public function destroy(Proyecto $proyecto, ListadoSuministrosItem $item, SuministrosService $service): RedirectResponse
    {
        if ($item->listado->proyecto_id !== $proyecto->id) {
            abort(404);
        }

        $service->eliminarItem($item);

        return back()->with('status', 'Item eliminado.');
    }
}
