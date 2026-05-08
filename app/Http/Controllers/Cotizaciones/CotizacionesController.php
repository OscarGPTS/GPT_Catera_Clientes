<?php

namespace App\Http\Controllers\Cotizaciones;

use App\Http\Controllers\Controller;
use App\Models\Cotizacion;
use App\Models\CotizacionPartida;
use App\Models\Proyecto;
use App\Services\Cotizaciones\CotizacionPdfGenerator;
use App\Services\Cotizaciones\CotizacionService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\Rule;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\StreamedResponse;

class CotizacionesController extends Controller
{
    public function index(Proyecto $proyecto)
    {
        $proyecto->load(['cliente:id,razon_social,alias_3letras', 'sublinea:id,codigo,nombre']);
        $cotizaciones = $proyecto->cotizaciones()->with('generadoPor:id,name')->get();

        return view('cotizaciones.index', [
            'proyecto' => $proyecto,
            'cotizaciones' => $cotizaciones,
        ]);
    }

    public function store(Request $request, Proyecto $proyecto, CotizacionService $service): RedirectResponse
    {
        if (! in_array($proyecto->estado, ['cotizando', 'cotizado', 'presentado'], true)) {
            return back()->withErrors(['estado' => 'El CP debe estar al menos en cotizando para crear cotizaciones.']);
        }

        $cotizacion = $service->crearBorrador($proyecto, $request->user()->id);

        return redirect()
            ->route('cotizaciones.edit', [$proyecto, $cotizacion])
            ->with('status', "Borrador v{$cotizacion->version} creado.");
    }

    public function edit(Proyecto $proyecto, Cotizacion $cotizacion)
    {
        $this->ensureBelongsTo($proyecto, $cotizacion);

        if ($cotizacion->status !== 'borrador') {
            return redirect()->route('cotizaciones.show', [$proyecto, $cotizacion]);
        }

        $cotizacion->load('partidas');
        $proyecto->load(['cliente:id,razon_social,alias_3letras', 'sublinea:id,codigo,nombre']);

        return view('cotizaciones.edit', [
            'proyecto' => $proyecto,
            'cotizacion' => $cotizacion,
        ]);
    }

    public function update(Request $request, Proyecto $proyecto, Cotizacion $cotizacion, CotizacionService $service): RedirectResponse
    {
        $this->ensureBelongsTo($proyecto, $cotizacion);
        $this->ensureBorrador($cotizacion);

        $data = $request->validate([
            'moneda' => ['required', Rule::in(['USD', 'MXN', 'EUR'])],
            'factor_indirectos' => ['required', 'numeric', 'min:0', 'max:1'],
            'factor_admin' => ['required', 'numeric', 'min:0', 'max:1'],
            'factor_utilidad' => ['required', 'numeric', 'min:0', 'max:1'],
            'precio_venta_final' => ['nullable', 'numeric', 'min:0'],
            'observaciones' => ['nullable', 'string', 'max:2000'],
        ]);

        $cotizacion->update([
            'moneda' => $data['moneda'],
            'factor_indirectos' => $data['factor_indirectos'],
            'factor_admin' => $data['factor_admin'],
            'factor_utilidad' => $data['factor_utilidad'],
            'precio_venta_final' => $data['precio_venta_final'] ?? 0,
            'observaciones' => $data['observaciones'] ?? null,
        ]);

        $service->recalcular($cotizacion);

        return back()->with('status', 'Cotización actualizada.');
    }

    public function storePartida(Request $request, Proyecto $proyecto, Cotizacion $cotizacion, CotizacionService $service): RedirectResponse
    {
        $this->ensureBelongsTo($proyecto, $cotizacion);
        $this->ensureBorrador($cotizacion);

        $data = $request->validate([
            'descripcion' => ['required', 'string', 'min:3'],
            'cantidad' => ['required', 'numeric', 'min:0.0001'],
            'unidad' => ['nullable', 'string', 'max:20'],
            'costo_unitario' => ['required', 'numeric', 'min:0'],
            'observaciones' => ['nullable', 'string'],
        ]);

        $numero = ($cotizacion->partidas()->max('numero_partida') ?? 0) + 1;

        $cotizacion->partidas()->create([
            'numero_partida' => $numero,
            'descripcion' => $data['descripcion'],
            'cantidad' => $data['cantidad'],
            'unidad' => $data['unidad'] ?? null,
            'costo_unitario' => $data['costo_unitario'],
            'costo_total' => round($data['cantidad'] * $data['costo_unitario'], 2),
            'observaciones' => $data['observaciones'] ?? null,
        ]);

        $service->recalcular($cotizacion);

        return back()->with('status', "Partida #{$numero} agregada.");
    }

    public function updatePartida(Request $request, Proyecto $proyecto, Cotizacion $cotizacion, CotizacionPartida $partida, CotizacionService $service): RedirectResponse
    {
        $this->ensureBelongsTo($proyecto, $cotizacion);
        $this->ensureBorrador($cotizacion);

        if ($partida->cotizacion_id !== $cotizacion->id) {
            abort(404);
        }

        $data = $request->validate([
            'descripcion' => ['required', 'string', 'min:3'],
            'cantidad' => ['required', 'numeric', 'min:0.0001'],
            'unidad' => ['nullable', 'string', 'max:20'],
            'costo_unitario' => ['required', 'numeric', 'min:0'],
            'observaciones' => ['nullable', 'string'],
        ]);

        $partida->update($data + [
            'costo_total' => round($data['cantidad'] * $data['costo_unitario'], 2),
        ]);

        $service->recalcular($cotizacion);

        return back()->with('status', "Partida #{$partida->numero_partida} actualizada.");
    }

    public function destroyPartida(Proyecto $proyecto, Cotizacion $cotizacion, CotizacionPartida $partida, CotizacionService $service): RedirectResponse
    {
        $this->ensureBelongsTo($proyecto, $cotizacion);
        $this->ensureBorrador($cotizacion);

        if ($partida->cotizacion_id !== $cotizacion->id) {
            abort(404);
        }

        $partida->delete();
        $service->recalcular($cotizacion);

        return back()->with('status', 'Partida eliminada.');
    }

    public function show(Proyecto $proyecto, Cotizacion $cotizacion)
    {
        $this->ensureBelongsTo($proyecto, $cotizacion);

        $cotizacion->load(['partidas', 'generadoPor:id,name']);
        $proyecto->load(['cliente', 'sublinea']);

        return view('cotizaciones.show', [
            'proyecto' => $proyecto,
            'cotizacion' => $cotizacion,
        ]);
    }

    public function emitir(Request $request, Proyecto $proyecto, Cotizacion $cotizacion, CotizacionService $service, CotizacionPdfGenerator $pdf): RedirectResponse
    {
        $this->ensureBelongsTo($proyecto, $cotizacion);

        if ($cotizacion->partidas()->count() === 0) {
            return back()->withErrors(['emitir' => 'Agrega al menos una partida antes de emitir.']);
        }

        $service->emitir($cotizacion, $request->user()->id);
        $pdf->generar($cotizacion->fresh());

        return redirect()
            ->route('cotizaciones.show', [$proyecto, $cotizacion])
            ->with('status', "Cotización v{$cotizacion->version} emitida y PDF generado.");
    }

    public function pdf(Proyecto $proyecto, Cotizacion $cotizacion, CotizacionPdfGenerator $generator): StreamedResponse|Response
    {
        $this->ensureBelongsTo($proyecto, $cotizacion);

        if (! $cotizacion->pdf_path || ! Storage::disk('local')->exists($cotizacion->pdf_path)) {
            $generator->generar($cotizacion);
            $cotizacion->refresh();
        }

        $filename = sprintf(
            'Cotizacion-%s-v%d.pdf',
            str_replace(['/', ' '], '-', $proyecto->cp_numero ?? $cotizacion->id),
            $cotizacion->version,
        );

        return Storage::disk('local')->download($cotizacion->pdf_path, $filename);
    }

    private function ensureBelongsTo(Proyecto $proyecto, Cotizacion $cotizacion): void
    {
        if ($cotizacion->proyecto_id !== $proyecto->id) {
            abort(404);
        }
    }

    private function ensureBorrador(Cotizacion $cotizacion): void
    {
        if ($cotizacion->status !== 'borrador') {
            abort(403, 'La cotización ya no es editable.');
        }
    }
}
