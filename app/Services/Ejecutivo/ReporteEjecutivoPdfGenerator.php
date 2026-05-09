<?php

namespace App\Services\Ejecutivo;

use App\Models\Proyecto;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Support\Facades\Storage;

class ReporteEjecutivoPdfGenerator
{
    public function __construct(private readonly KpisEjecutivoService $kpis) {}

    public function generar(int $año): string
    {
        $kpis = $this->kpis->snapshot($año);

        $proyectos = Proyecto::with(['cliente:id,razon_social,alias_3letras', 'sublinea:id,codigo'])
            ->where('año', $año)
            ->orderBy('estado')
            ->get();

        $pdf = Pdf::loadView('pdf.reporte_ejecutivo', [
            'año' => $año,
            'kpis' => $kpis,
            'proyectos' => $proyectos,
        ])->setPaper('letter');

        Storage::disk('local')->makeDirectory('ejecutivo');
        $filename = "ejecutivo/reporte-ejecutivo-{$año}.pdf";
        Storage::disk('local')->put($filename, $pdf->output());

        return $filename;
    }
}
