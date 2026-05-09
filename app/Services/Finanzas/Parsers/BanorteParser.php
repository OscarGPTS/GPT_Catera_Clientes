<?php

namespace App\Services\Finanzas\Parsers;

use Illuminate\Support\Carbon;
use RuntimeException;

/**
 * Parser de estados de cuenta de Banorte en formato CSV.
 *
 * Formato típico:
 *   FECHA;CONCEPTO;MONTO;NATURALEZA;SALDO
 *   2026-01-05;Transferencia recibida;125000.00;C;225000.00
 *   2026-01-06;Pago a proveedor;-25000.00;D;200000.00
 *
 * Naturaleza: C = abono (ingreso), D = cargo (egreso). Si no viene, se infiere por signo.
 */
class BanorteParser implements EstadoCuentaParser
{
    public function parse(string $filePath): array
    {
        if (! file_exists($filePath)) {
            throw new RuntimeException("Archivo no encontrado: {$filePath}");
        }

        $contenido = file_get_contents($filePath);
        if ($contenido === false || trim($contenido) === '') {
            throw new RuntimeException('Archivo vacío.');
        }

        $separador = substr_count($contenido, ';') > substr_count($contenido, ',') ? ';' : ',';
        $lineas = preg_split('/\r\n|\n|\r/', $contenido);

        $movimientos = [];
        $headerEncontrado = false;
        $idxFecha = $idxConcepto = $idxMonto = $idxNaturaleza = -1;

        foreach ($lineas as $linea) {
            if (trim($linea) === '') {
                continue;
            }

            $cols = str_getcsv($linea, $separador);

            if (! $headerEncontrado) {
                $upper = array_map(fn ($c) => mb_strtoupper(trim($c)), $cols);
                if (in_array('FECHA', $upper, true) && (in_array('CONCEPTO', $upper, true) || in_array('DESCRIPCION', $upper, true))) {
                    $idxFecha = array_search('FECHA', $upper, true);
                    $idxConcepto = array_search('CONCEPTO', $upper, true);
                    if ($idxConcepto === false) {
                        $idxConcepto = array_search('DESCRIPCION', $upper, true);
                    }
                    $idxMonto = array_search('MONTO', $upper, true);
                    if ($idxMonto === false) {
                        $idxMonto = array_search('IMPORTE', $upper, true);
                    }
                    $idxNaturaleza = array_search('NATURALEZA', $upper, true);
                    if ($idxNaturaleza === false) {
                        $idxNaturaleza = -1;
                    }
                    $headerEncontrado = true;
                }

                continue;
            }

            if (! isset($cols[$idxFecha]) || trim($cols[$idxFecha]) === '') {
                continue;
            }

            try {
                $fecha = Carbon::parse(trim($cols[$idxFecha]))->toDateString();
            } catch (\Exception) {
                continue;
            }

            $monto = $this->normalizarMonto($cols[$idxMonto] ?? '0');
            if ($monto == 0) {
                continue;
            }

            $naturaleza = $idxNaturaleza >= 0 ? mb_strtoupper(trim($cols[$idxNaturaleza] ?? '')) : '';
            if ($naturaleza === 'C' || $naturaleza === 'CR') {
                $tipo = 'ingreso';
                $monto = abs($monto);
            } elseif ($naturaleza === 'D' || $naturaleza === 'DB') {
                $tipo = 'egreso';
                $monto = abs($monto);
            } else {
                $tipo = $monto < 0 ? 'egreso' : 'ingreso';
                $monto = abs($monto);
            }

            $movimientos[] = [
                'fecha' => $fecha,
                'descripcion' => trim($cols[$idxConcepto] ?? ''),
                'monto' => $monto,
                'tipo' => $tipo,
            ];
        }

        if (! $headerEncontrado) {
            throw new RuntimeException('No se encontró encabezado FECHA/CONCEPTO en el CSV de Banorte.');
        }

        return $movimientos;
    }

    private function normalizarMonto(string $raw): float
    {
        $clean = trim($raw);
        if ($clean === '' || $clean === '-') {
            return 0.0;
        }
        $clean = str_replace(['$', ',', ' '], '', $clean);
        $clean = trim($clean, '"\'');

        return (float) $clean;
    }
}
