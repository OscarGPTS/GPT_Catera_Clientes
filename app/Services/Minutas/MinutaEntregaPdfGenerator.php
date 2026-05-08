<?php

namespace App\Services\Minutas;

use App\Models\MinutaEntrega;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Support\Facades\Storage;

class MinutaEntregaPdfGenerator
{
    public function generar(MinutaEntrega $minuta): string
    {
        $minuta->loadMissing(['proyecto.cliente', 'proyecto.sublinea', 'participantes.user']);

        $pdf = Pdf::loadView('pdf.minuta_entrega', [
            'm' => $minuta,
            'p' => $minuta->proyecto,
        ])->setPaper('letter');

        Storage::disk('local')->makeDirectory('minutas');

        $filename = sprintf(
            'minutas/minuta-entrega-%s.pdf',
            str_replace(['/', ' '], '-', $minuta->proyecto->cp_numero ?? $minuta->id),
        );

        Storage::disk('local')->put($filename, $pdf->output());

        $minuta->update(['pdf_path' => $filename]);

        return $filename;
    }
}
