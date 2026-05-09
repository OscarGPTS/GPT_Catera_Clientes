<?php

namespace App\Http\Controllers\Ejecutivo;

use App\Http\Controllers\Controller;
use App\Services\Ejecutivo\KpisEjecutivoService;
use App\Services\Ejecutivo\ReporteEjecutivoPdfGenerator;
use App\Services\Ejecutivo\ResumenEjecutivoExcelExporter;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Symfony\Component\HttpFoundation\StreamedResponse;

class EjecutivoController extends Controller
{
    public function __invoke(Request $request, KpisEjecutivoService $svc)
    {
        $año = (int) $request->input('año', now()->year);

        return view('ejecutivo.index', [
            'año' => $año,
            'kpis' => $svc->snapshot($año),
        ]);
    }

    public function pdf(Request $request, ReporteEjecutivoPdfGenerator $generator): StreamedResponse
    {
        $año = (int) $request->input('año', now()->year);
        $path = $generator->generar($año);

        return Storage::disk('local')->download($path, "ReporteEjecutivo-{$año}.pdf");
    }

    public function excel(Request $request, ResumenEjecutivoExcelExporter $exporter): StreamedResponse
    {
        $año = (int) $request->input('año', now()->year);
        $path = $exporter->exportar($año);

        return Storage::disk('local')->download($path, "ResumenEjecutivo-{$año}.xlsx");
    }
}
