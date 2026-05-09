<?php

namespace App\Http\Controllers\Cierre;

use App\Http\Controllers\Controller;
use App\Models\Proyecto;
use App\Services\Cierre\CartaFiniquitoPdfGenerator;
use App\Services\Cierre\CartaFiniquitoService;
use App\Services\Cierre\PostMortemPdfGenerator;
use App\Services\Cierre\PostMortemService;
use App\Services\Libro\LibroService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use RuntimeException;
use Symfony\Component\HttpFoundation\StreamedResponse;

class CierreController extends Controller
{
    public function show(Proyecto $proyecto, LibroService $libroService)
    {
        if (! in_array($proyecto->estado, ['en_cierre', 'cerrado'], true)) {
            return redirect()->route('oportunidades.show', $proyecto)
                ->withErrors(['cierre' => 'El cierre se levanta cuando el proyecto está en cierre.']);
        }

        $proyecto->load(['cliente:id,razon_social,alias_3letras', 'sublinea:id,codigo,nombre', 'libro', 'cartaFiniquito', 'postMortem']);
        $bloqueoLibro = $proyecto->libro ? $libroService->evaluarBloqueoCierre($proyecto->libro) : ['bloqueado' => true, 'razones' => ['Libro no abierto.']];

        return view('cierre.show', [
            'proyecto' => $proyecto,
            'bloqueoLibro' => $bloqueoLibro,
        ]);
    }

    public function storeCarta(Request $request, Proyecto $proyecto, CartaFiniquitoService $service): RedirectResponse
    {
        $data = $request->validate([
            'fecha_emision' => ['nullable', 'date'],
            'observaciones' => ['nullable', 'string', 'max:2000'],
            'personal_liberado' => ['nullable', 'array'],
            'personal_liberado.*.nombre' => ['nullable', 'string', 'max:120'],
            'personal_liberado.*.rol' => ['nullable', 'string', 'max:80'],
            'equipos_liberados' => ['nullable', 'array'],
            'equipos_liberados.*.nombre' => ['nullable', 'string', 'max:120'],
        ]);

        try {
            $service->crearOActualizar($proyecto, $request->user()->id, $data);
        } catch (RuntimeException $e) {
            return back()->withErrors(['carta' => $e->getMessage()]);
        }

        return back()->with('status', 'Carta finiquito guardada.');
    }

    public function firmarGpt(Request $request, Proyecto $proyecto, CartaFiniquitoService $service, CartaFiniquitoPdfGenerator $pdf): RedirectResponse
    {
        $carta = $proyecto->cartaFiniquito;
        abort_if(! $carta, 404);

        try {
            $service->firmarGpt($carta, $request->user()->id);
            $pdf->generar($carta->fresh());
        } catch (RuntimeException $e) {
            return back()->withErrors(['carta' => $e->getMessage()]);
        }

        return back()->with('status', 'GPT firmó la carta finiquito.');
    }

    public function firmarCliente(Request $request, Proyecto $proyecto, CartaFiniquitoService $service, CartaFiniquitoPdfGenerator $pdf): RedirectResponse
    {
        $carta = $proyecto->cartaFiniquito;
        abort_if(! $carta, 404);

        $data = $request->validate([
            'cliente_nombre' => ['required', 'string', 'max:120'],
        ]);

        try {
            $service->firmarCliente($carta, $request->user()->id, $data['cliente_nombre']);
            $pdf->generar($carta->fresh());
        } catch (RuntimeException $e) {
            return back()->withErrors(['carta' => $e->getMessage()]);
        }

        return back()->with('status', 'Firma del cliente registrada.');
    }

    public function cerrarProyecto(Request $request, Proyecto $proyecto, CartaFiniquitoService $service): RedirectResponse
    {
        $carta = $proyecto->cartaFiniquito;
        abort_if(! $carta, 404);

        try {
            $service->cerrarProyecto($carta, $request->user()->id);
        } catch (RuntimeException $e) {
            return back()->withErrors(['cerrar' => $e->getMessage()]);
        }

        return back()->with('status', 'Proyecto cerrado.');
    }

    public function pdfCarta(Proyecto $proyecto, CartaFiniquitoPdfGenerator $generator): StreamedResponse
    {
        $carta = $proyecto->cartaFiniquito;
        abort_if(! $carta, 404);

        if (! $carta->pdf_path || ! Storage::disk('local')->exists($carta->pdf_path)) {
            $generator->generar($carta);
            $carta->refresh();
        }

        return Storage::disk('local')->download($carta->pdf_path, "Finiquito-{$proyecto->cp_numero}.pdf");
    }

    public function storePostMortem(Request $request, Proyecto $proyecto, PostMortemService $service): RedirectResponse
    {
        $data = $request->validate([
            'fecha_sesion' => ['nullable', 'date'],
            'lecciones_aprendidas' => ['nullable', 'string', 'max:5000'],
            'presupuesto_planeado' => ['nullable', 'numeric', 'min:0'],
            'presupuesto_real' => ['nullable', 'numeric', 'min:0'],
            'participantes' => ['nullable', 'array'],
            'participantes.*.nombre' => ['nullable', 'string', 'max:120'],
            'participantes.*.rol' => ['nullable', 'string', 'max:80'],
            'recomendaciones_mejora' => ['nullable', 'array'],
            'recomendaciones_mejora.*.texto' => ['nullable', 'string', 'max:500'],
            'recomendaciones_mejora.*.responsable' => ['nullable', 'string', 'max:120'],
        ]);

        try {
            $service->crearOActualizar($proyecto, $request->user()->id, $data);
        } catch (RuntimeException $e) {
            return back()->withErrors(['postmortem' => $e->getMessage()]);
        }

        return back()->with('status', 'Post-mortem guardado.');
    }

    public function pdfPostMortem(Proyecto $proyecto, PostMortemPdfGenerator $generator): StreamedResponse
    {
        $pm = $proyecto->postMortem;
        abort_if(! $pm, 404);

        if (! $pm->pdf_path || ! Storage::disk('local')->exists($pm->pdf_path)) {
            $generator->generar($pm);
            $pm->refresh();
        }

        return Storage::disk('local')->download($pm->pdf_path, "PostMortem-{$proyecto->cp_numero}.pdf");
    }
}
