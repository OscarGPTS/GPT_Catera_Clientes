<?php

namespace App\Services\Proyectos;

use App\Models\Proyecto;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Support\Facades\Storage;

/**
 * Ficha de proyecto: carátula one-page con todos los datos clave del CP/DN
 * (datos generales, equipo, plazo, monto, estado, eventos recientes).
 */
class FichaProyectoPdfGenerator
{
    public function generar(Proyecto $proyecto): string
    {
        $proyecto->loadMissing([
            'cliente', 'sublinea',
            'directorDn', 'gerenteProyectos', 'gerenteOperaciones',
            'ingenieroCostos', 'ingenieroProyectos', 'trainee',
            'cotizaciones' => fn ($q) => $q->where('status', 'emitida')->orderByDesc('version'),
            'eventos' => fn ($q) => $q->latest()->limit(15),
            'eventos.user:id,name',
        ]);

        $pdf = Pdf::loadView('pdf.ficha_proyecto', [
            'p' => $proyecto,
            'cotizacion' => $proyecto->cotizaciones->first(),
        ])->setPaper('letter');

        Storage::disk('local')->makeDirectory('fichas');

        $filename = sprintf(
            'fichas/ficha-%s.pdf',
            str_replace(['/', ' '], '-', $proyecto->cp_numero ?? "p{$proyecto->id}"),
        );

        Storage::disk('local')->put($filename, $pdf->output());

        return $filename;
    }
}
