<?php

namespace App\Http\Controllers\Procura;

use App\Http\Controllers\Controller;
use App\Models\BomBoeItem;
use App\Models\Proyecto;
use App\Models\User;
use App\Services\Procura\BomService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use RuntimeException;

class BomController extends Controller
{
    public function index(Proyecto $proyecto, BomService $service)
    {
        $proyecto->load(['cliente:id,razon_social,alias_3letras', 'sublinea:id,codigo,nombre']);
        $items = $proyecto->bomBoeItems()->with('responsable:id,name')->orderBy('tipo')->orderBy('id')->get();

        return view('bom.index', [
            'proyecto' => $proyecto,
            'items' => $items,
            'resumen' => $service->resumenPorStatus($proyecto),
            'tieneCotizacion' => $proyecto->cotizaciones()->where('status', 'emitida')->exists(),
            'responsables' => User::role(['ingeniero_proyectos', 'ingeniero_costos', 'gerente_proyectos', 'gerente_operaciones', 'compras'])
                ->orderBy('name')
                ->get(['id', 'name']),
        ]);
    }

    public function importar(Request $request, Proyecto $proyecto, BomService $service): RedirectResponse
    {
        try {
            $count = $service->importarDesdeCotizacion($proyecto, $request->user()->id);
        } catch (RuntimeException $e) {
            return back()->withErrors(['bom' => $e->getMessage()]);
        }

        return back()->with('status', "{$count} partidas importadas al BOM/BOE.");
    }

    public function store(Request $request, Proyecto $proyecto, BomService $service): RedirectResponse
    {
        $data = $request->validate([
            'tipo' => ['required', Rule::in(['BOM', 'BOE'])],
            'descripcion' => ['required', 'string', 'min:3'],
            'cantidad' => ['required', 'numeric', 'min:0.0001'],
            'unidad' => ['nullable', 'string', 'max:20'],
            'status' => ['required', Rule::in(['en_almacen', 'por_afilar', 'por_fabricar', 'por_comprar', 'en_transito', 'entregado'])],
            'responsable_id' => ['nullable', 'exists:users,id'],
            'fecha_requerida' => ['nullable', 'date'],
            'observaciones' => ['nullable', 'string'],
        ]);

        $service->crearItem($proyecto, $data);

        return back()->with('status', 'Item agregado al BOM/BOE.');
    }

    public function update(Request $request, Proyecto $proyecto, BomBoeItem $item, BomService $service): RedirectResponse
    {
        if ($item->proyecto_id !== $proyecto->id) {
            abort(404);
        }

        $data = $request->validate([
            'tipo' => ['nullable', Rule::in(['BOM', 'BOE'])],
            'descripcion' => ['nullable', 'string', 'min:3'],
            'cantidad' => ['nullable', 'numeric', 'min:0.0001'],
            'unidad' => ['nullable', 'string', 'max:20'],
            'status' => ['nullable', Rule::in(['en_almacen', 'por_afilar', 'por_fabricar', 'por_comprar', 'en_transito', 'entregado'])],
            'responsable_id' => ['nullable', 'exists:users,id'],
            'fecha_requerida' => ['nullable', 'date'],
            'observaciones' => ['nullable', 'string'],
        ]);

        $service->actualizarItem($item, $data);

        return back()->with('status', 'Item actualizado.');
    }

    public function destroy(Proyecto $proyecto, BomBoeItem $item, BomService $service): RedirectResponse
    {
        if ($item->proyecto_id !== $proyecto->id) {
            abort(404);
        }

        $service->eliminarItem($item);

        return back()->with('status', 'Item eliminado.');
    }
}
