<?php

namespace App\Services\Finanzas;

use App\Models\CierreMensual;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Support\Facades\Storage;

class CierrePdfGenerator
{
    public function generar(CierreMensual $cierre): string
    {
        $cierre->loadMissing(['secciones.lineas.proyecto:id,cp_numero,dn_numero', 'generadoPor', 'aprobadoPor']);

        $pdf = Pdf::loadView('pdf.cierre_mensual', [
            'c' => $cierre,
        ])->setPaper('letter', 'landscape');

        Storage::disk('local')->makeDirectory('cierres');

        $filename = sprintf(
            'cierres/cierre-%s-%02d-%d.pdf',
            $cierre->tipo,
            $cierre->mes,
            $cierre->año,
        );

        Storage::disk('local')->put($filename, $pdf->output());
        $cierre->update(['pdf_path' => $filename]);

        return $filename;
    }
}
