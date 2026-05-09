<?php

namespace App\Services\Cierre;

use App\Models\CartaFiniquito;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Support\Facades\Storage;

class CartaFiniquitoPdfGenerator
{
    public function generar(CartaFiniquito $carta): string
    {
        $carta->loadMissing(['proyecto.cliente', 'proyecto.sublinea']);

        $pdf = Pdf::loadView('pdf.carta_finiquito', [
            'c' => $carta,
            'p' => $carta->proyecto,
        ])->setPaper('letter');

        Storage::disk('local')->makeDirectory('finiquitos');

        $filename = sprintf(
            'finiquitos/finiquito-%s.pdf',
            str_replace(['/', ' '], '-', $carta->proyecto->cp_numero ?? $carta->proyecto_id),
        );

        Storage::disk('local')->put($filename, $pdf->output());
        $carta->update(['pdf_path' => $filename]);

        return $filename;
    }
}
