<?php

namespace App\Services\Ejecucion;

use App\Models\ReporteSemanal;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Support\Facades\Storage;

class ReporteSemanalPdfGenerator
{
    public function generar(ReporteSemanal $reporte): string
    {
        $reporte->loadMissing(['proyecto.cliente', 'proyecto.sublinea']);

        $pdf = Pdf::loadView('pdf.reporte_semanal', [
            'r' => $reporte,
            'p' => $reporte->proyecto,
        ])->setPaper('letter');

        Storage::disk('local')->makeDirectory('reportes');

        $filename = sprintf(
            'reportes/reporte-%s-%s.pdf',
            str_replace(['/', ' '], '-', $reporte->proyecto->cp_numero ?? $reporte->proyecto_id),
            $reporte->semana_inicio->format('Y-m-d'),
        );

        Storage::disk('local')->put($filename, $pdf->output());

        $reporte->update(['pdf_path' => $filename]);

        return $filename;
    }
}
