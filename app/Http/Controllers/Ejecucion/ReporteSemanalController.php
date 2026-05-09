<?php

namespace App\Http\Controllers\Ejecucion;

use App\Http\Controllers\Controller;
use App\Models\Proyecto;
use App\Models\ReporteSemanal;
use App\Services\Ejecucion\ReporteSemanalPdfGenerator;
use App\Services\Ejecucion\ReporteSemanalService;
use Carbon\CarbonImmutable;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use RuntimeException;
use Symfony\Component\HttpFoundation\StreamedResponse;

class ReporteSemanalController extends Controller
{
    public function index(Proyecto $proyecto)
    {
        $proyecto->load(['cliente:id,razon_social,alias_3letras']);
        $reportes = $proyecto->reportesSemanales()->get();

        return view('reportes.index', [
            'proyecto' => $proyecto,
            'reportes' => $reportes,
            'semanaActual' => CarbonImmutable::now()->startOfWeek()->toDateString(),
        ]);
    }

    public function store(Request $request, Proyecto $proyecto, ReporteSemanalService $service): RedirectResponse
    {
        $data = $request->validate([
            'semana_inicio' => ['required', 'date'],
        ]);

        try {
            $r = $service->generar($proyecto, CarbonImmutable::parse($data['semana_inicio']), $request->user()->id);
        } catch (RuntimeException $e) {
            return back()->withErrors(['reporte' => $e->getMessage()]);
        }

        return redirect()->route('reportes.show', [$proyecto, $r])->with('status', 'Reporte generado/actualizado.');
    }

    public function show(Proyecto $proyecto, ReporteSemanal $reporte)
    {
        $this->ensureBelongsTo($proyecto, $reporte);

        $proyecto->load(['cliente']);

        return view('reportes.show', [
            'proyecto' => $proyecto,
            'reporte' => $reporte,
        ]);
    }

    public function regenerar(Request $request, Proyecto $proyecto, ReporteSemanal $reporte, ReporteSemanalService $service): RedirectResponse
    {
        $this->ensureBelongsTo($proyecto, $reporte);

        $service->generar($proyecto, CarbonImmutable::parse($reporte->semana_inicio), $request->user()->id);

        return back()->with('status', 'Reporte regenerado con datos actuales.');
    }

    public function enviar(Request $request, Proyecto $proyecto, ReporteSemanal $reporte, ReporteSemanalService $service): RedirectResponse
    {
        $this->ensureBelongsTo($proyecto, $reporte);

        $data = $request->validate([
            'recipients' => ['required', 'string', 'max:1000'],
        ]);

        $emails = collect(explode(',', $data['recipients']))
            ->map(fn ($e) => trim($e))
            ->filter(fn ($e) => filter_var($e, FILTER_VALIDATE_EMAIL))
            ->values()
            ->all();

        if (empty($emails)) {
            return back()->withErrors(['recipients' => 'Ningún email válido.']);
        }

        $service->marcarEnviado($reporte, $emails);

        return back()->with('status', 'Reporte marcado como enviado a '.count($emails).' destinatarios. (TODO: integrar mailer).');
    }

    public function pdf(Proyecto $proyecto, ReporteSemanal $reporte, ReporteSemanalPdfGenerator $generator): StreamedResponse
    {
        $this->ensureBelongsTo($proyecto, $reporte);

        if (! $reporte->pdf_path || ! Storage::disk('local')->exists($reporte->pdf_path)) {
            $generator->generar($reporte);
            $reporte->refresh();
        }

        return Storage::disk('local')->download($reporte->pdf_path, "ReporteSemanal-{$proyecto->cp_numero}-{$reporte->semana_inicio->format('Y-m-d')}.pdf");
    }

    private function ensureBelongsTo(Proyecto $proyecto, ReporteSemanal $reporte): void
    {
        if ($reporte->proyecto_id !== $proyecto->id) {
            abort(404);
        }
    }
}
