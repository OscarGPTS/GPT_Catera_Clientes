<?php

namespace App\Services\Ejecucion;

use App\Models\SolicitudViaticos;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Support\Facades\Storage;

class ViaticosPdfGenerator
{
    public function generar(SolicitudViaticos $solicitud): string
    {
        $solicitud->loadMissing([
            'proyecto.cliente', 'personal.user', 'partidas',
            'solicitante', 'aprobadorServGrales', 'aprobadorDireccion',
        ]);

        $pdf = Pdf::loadView('pdf.viaticos', [
            's' => $solicitud,
            'p' => $solicitud->proyecto,
        ])->setPaper('letter');

        Storage::disk('local')->makeDirectory('viaticos');

        $filename = sprintf(
            'viaticos/viaticos-%s-%d.pdf',
            str_replace(['/', ' '], '-', $solicitud->proyecto->cp_numero ?? $solicitud->proyecto_id),
            $solicitud->id,
        );

        Storage::disk('local')->put($filename, $pdf->output());

        $solicitud->update(['pdf_path' => $filename]);

        return $filename;
    }
}
