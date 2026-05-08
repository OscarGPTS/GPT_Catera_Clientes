<?php

namespace App\Http\Controllers\Kom;

use App\Http\Controllers\Controller;
use App\Models\KickOffMeeting;
use App\Models\Proyecto;
use App\Services\Proyectos\KomPdfGenerator;
use App\Services\Proyectos\KomService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use RuntimeException;
use Symfony\Component\HttpFoundation\StreamedResponse;

class KomController extends Controller
{
    public function index(Proyecto $proyecto)
    {
        $proyecto->load(['cliente:id,razon_social,alias_3letras', 'sublinea:id,codigo,nombre']);
        $koms = $proyecto->koms()->with('cronograma:id,version')->get();

        return view('koms.index', [
            'proyecto' => $proyecto,
            'koms' => $koms,
        ]);
    }

    public function store(Request $request, Proyecto $proyecto, KomService $service): RedirectResponse
    {
        $data = $request->validate([
            'tipo' => ['required', 'in:kom_interno,kom_cliente'],
            'fecha' => ['required', 'date'],
            'agenda' => ['nullable', 'string'],
            'minuta' => ['nullable', 'string'],
            'participantes' => ['nullable', 'array'],
            'participantes.*.nombre' => ['nullable', 'string', 'max:120'],
            'participantes.*.rol' => ['nullable', 'string', 'max:80'],
            'participantes.*.empresa' => ['nullable', 'string', 'max:80'],
        ]);

        $data['participantes'] = array_values(array_filter(
            $data['participantes'] ?? [],
            fn ($p) => filled($p['nombre'] ?? null),
        ));

        try {
            $kom = $service->crear($proyecto, $request->user()->id, $data);
        } catch (RuntimeException $e) {
            return back()->withErrors(['kom' => $e->getMessage()]);
        }

        return redirect()
            ->route('koms.show', [$proyecto, $kom])
            ->with('status', "KOM {$data['tipo']} creado.");
    }

    public function show(Proyecto $proyecto, KickOffMeeting $kom)
    {
        $this->ensureBelongsTo($proyecto, $kom);

        $kom->load(['cronograma.actividades']);
        $proyecto->load(['cliente', 'sublinea', 'cronogramas:id,proyecto_id,version,fecha_inicio,fecha_fin']);

        return view('koms.show', [
            'proyecto' => $proyecto,
            'kom' => $kom,
        ]);
    }

    public function update(Request $request, Proyecto $proyecto, KickOffMeeting $kom, KomService $service): RedirectResponse
    {
        $this->ensureBelongsTo($proyecto, $kom);

        $data = $request->validate([
            'fecha' => ['required', 'date'],
            'agenda' => ['nullable', 'string'],
            'minuta' => ['nullable', 'string'],
            'participantes' => ['nullable', 'array'],
            'participantes.*.nombre' => ['nullable', 'string', 'max:120'],
            'participantes.*.rol' => ['nullable', 'string', 'max:80'],
            'participantes.*.empresa' => ['nullable', 'string', 'max:80'],
            'cronograma_attached_id' => ['nullable', 'exists:cronogramas,id'],
        ]);

        $data['participantes'] = array_values(array_filter(
            $data['participantes'] ?? [],
            fn ($p) => filled($p['nombre'] ?? null),
        ));

        $service->actualizar($kom, $data);

        return back()->with('status', 'KOM actualizado.');
    }

    public function destroy(Proyecto $proyecto, KickOffMeeting $kom, KomService $service): RedirectResponse
    {
        $this->ensureBelongsTo($proyecto, $kom);
        $service->eliminar($kom);

        return redirect()
            ->route('koms.index', $proyecto)
            ->with('status', 'KOM eliminado.');
    }

    public function pdf(Proyecto $proyecto, KickOffMeeting $kom, KomPdfGenerator $generator): StreamedResponse
    {
        $this->ensureBelongsTo($proyecto, $kom);

        if (! $kom->minuta_pdf_path || ! Storage::disk('local')->exists($kom->minuta_pdf_path)) {
            $generator->generar($kom);
            $kom->refresh();
        }

        return Storage::disk('local')->download(
            $kom->minuta_pdf_path,
            sprintf('KOM-%s-%s.pdf', $kom->tipo, str_replace(['/', ' '], '-', $proyecto->cp_numero ?? $kom->id)),
        );
    }

    private function ensureBelongsTo(Proyecto $proyecto, KickOffMeeting $kom): void
    {
        if ($kom->proyecto_id !== $proyecto->id) {
            abort(404);
        }
    }
}
