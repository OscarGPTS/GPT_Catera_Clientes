<?php

namespace App\Services\Libro;

use App\Models\LibroProyecto;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Support\Facades\Storage;

/**
 * Genera el dossier consolidado del libro de proyecto: portada + índice + listado de
 * cada sección con su checklist y documentos cargados. No concatena los archivos
 * binarios (eso requeriría merge de PDFs externos), pero genera un PDF maestro con
 * referencias y un manifiesto con MD5 para verificación.
 */
class DossierConsolidadoGenerator
{
    public function generar(LibroProyecto $libro): string
    {
        $libro->loadMissing([
            'proyecto.cliente', 'proyecto.sublinea',
            'secciones.checklist.completadoPor:id,name',
            'secciones.checklist.evidencia:id,nombre,version',
            'secciones.documentos.subidoPor:id,name',
            'secciones.responsable:id,name',
        ]);

        $pdf = Pdf::loadView('pdf.dossier_consolidado', [
            'libro' => $libro,
            'proyecto' => $libro->proyecto,
        ])->setPaper('letter');

        $contenido = $pdf->output();

        Storage::disk('local')->makeDirectory('dossiers');

        $filename = sprintf(
            'dossiers/dossier-%s.pdf',
            str_replace(['/', ' '], '-', $libro->proyecto->cp_numero ?? "p{$libro->proyecto_id}"),
        );

        Storage::disk('local')->put($filename, $contenido);

        $libro->update([
            'pdf_consolidado_path' => $filename,
            'pdf_consolidado_md5' => md5($contenido),
        ]);

        return $filename;
    }
}
