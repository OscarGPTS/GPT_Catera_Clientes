<?php

namespace App\Services\Ejecucion;

use App\Models\BitacoraDiaria;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Support\Facades\Storage;

class BitacoraPdfGenerator
{
    public function generar(BitacoraDiaria $bitacora): string
    {
        $bitacora->loadMissing(['proyecto.cliente', 'proyecto.sublinea', 'cargadoPor']);

        $pdf = Pdf::loadView('pdf.bitacora', [
            'b' => $bitacora,
            'p' => $bitacora->proyecto,
        ])->setPaper('letter');

        Storage::disk('local')->makeDirectory('bitacoras');

        $filename = sprintf(
            'bitacoras/bitacora-%s-%s.pdf',
            str_replace(['/', ' '], '-', $bitacora->proyecto->cp_numero ?? $bitacora->proyecto_id),
            $bitacora->fecha->format('Y-m-d'),
        );

        Storage::disk('local')->put($filename, $pdf->output());

        return $filename;
    }
}
