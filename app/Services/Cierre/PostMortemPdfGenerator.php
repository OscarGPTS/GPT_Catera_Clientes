<?php

namespace App\Services\Cierre;

use App\Models\PostMortem;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Support\Facades\Storage;

class PostMortemPdfGenerator
{
    public function generar(PostMortem $pm): string
    {
        $pm->loadMissing(['proyecto.cliente', 'proyecto.sublinea']);

        $pdf = Pdf::loadView('pdf.post_mortem', [
            'pm' => $pm,
            'p' => $pm->proyecto,
        ])->setPaper('letter');

        Storage::disk('local')->makeDirectory('post_mortems');

        $filename = sprintf(
            'post_mortems/postmortem-%s.pdf',
            str_replace(['/', ' '], '-', $pm->proyecto->cp_numero ?? $pm->proyecto_id),
        );

        Storage::disk('local')->put($filename, $pdf->output());
        $pm->update(['pdf_path' => $filename]);

        return $filename;
    }
}
