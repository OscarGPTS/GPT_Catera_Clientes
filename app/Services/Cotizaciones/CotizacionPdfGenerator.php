<?php

namespace App\Services\Cotizaciones;

use App\Models\Cotizacion;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Support\Facades\Storage;

class CotizacionPdfGenerator
{
    public function generar(Cotizacion $cotizacion): string
    {
        $cotizacion->loadMissing(['proyecto.cliente', 'proyecto.sublinea', 'partidas', 'generadoPor']);

        $pdf = Pdf::loadView('pdf.cotizacion', [
            'c' => $cotizacion,
            'p' => $cotizacion->proyecto,
        ])->setPaper('letter');

        $folder = 'cotizaciones';
        Storage::disk('local')->makeDirectory($folder);

        $filename = sprintf(
            '%s/cotizacion-%s-v%d.pdf',
            $folder,
            str_replace(['/', ' '], '-', $cotizacion->proyecto->cp_numero ?? "cot{$cotizacion->id}"),
            $cotizacion->version,
        );

        Storage::disk('local')->put($filename, $pdf->output());

        $cotizacion->update(['pdf_path' => $filename]);

        return $filename;
    }
}
