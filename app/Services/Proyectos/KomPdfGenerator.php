<?php

namespace App\Services\Proyectos;

use App\Models\KickOffMeeting;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Support\Facades\Storage;

class KomPdfGenerator
{
    public function generar(KickOffMeeting $kom): string
    {
        $kom->loadMissing(['proyecto.cliente', 'proyecto.sublinea', 'cronograma.actividades']);

        $pdf = Pdf::loadView('pdf.kom', [
            'k' => $kom,
            'p' => $kom->proyecto,
        ])->setPaper('letter');

        Storage::disk('local')->makeDirectory('koms');

        $filename = sprintf(
            'koms/kom-%s-%s-%d.pdf',
            $kom->tipo,
            str_replace(['/', ' '], '-', $kom->proyecto->cp_numero ?? $kom->proyecto_id),
            $kom->id,
        );

        Storage::disk('local')->put($filename, $pdf->output());

        $kom->update(['minuta_pdf_path' => $filename]);

        return $filename;
    }
}
