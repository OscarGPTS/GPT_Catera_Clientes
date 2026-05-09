<?php

namespace App\Services\Finanzas\Parsers;

use Illuminate\Support\Carbon;
use RuntimeException;

/**
 * Parser de estados de cuenta de BBVA en formato CSV.
 *
 * Formato esperado (separador coma o punto y coma):
 *   FECHA,DESCRIPCION,CARGO,ABONO,SALDO
 *   "01/01/2026","Pago de servicio","","12500.00","100000.00"
 *   "02/01/2026","Pago a proveedor","5000.00","","95000.00"
 *
 * Tolerante a:
 *   - Encabezados en cualquier posición (busca primera fila con FECHA)
 *   - Separador , o ;
 *   - Fechas en formato dd/mm/yyyy o yyyy-mm-dd
 *   - Montos con o sin coma de millares
 */
class BbvaParser implements EstadoCuentaParser
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

        // Detectar separador
        $separador = substr_count($contenido, ';') > substr_count($contenido, ',') ? ';' : ',';

        $lineas = preg_split('/\r\n|\n|\r/', $contenido);
        $movimientos = [];
        $headerEncontrado = false;
        $idxFecha = $idxDesc = $idxCargo = $idxAbono = -1;

        foreach ($lineas as $linea) {
            if (trim($linea) === '') {
                continue;
            }

            $cols = str_getcsv($linea, $separador);

            if (! $headerEncontrado) {
                $upper = array_map(fn ($c) => mb_strtoupper(trim($c)), $cols);
                if (in_array('FECHA', $upper, true)) {
                    $idxFecha = array_search('FECHA', $upper, true);
                    $idxDesc = $this->buscar($upper, ['DESCRIPCION', 'DESCRIPCIÓN', 'CONCEPTO']);
                    $idxCargo = $this->buscar($upper, ['CARGO', 'CARGOS', 'EGRESO']);
                    $idxAbono = $this->buscar($upper, ['ABONO', 'ABONOS', 'INGRESO', 'DEPOSITO']);
                    $headerEncontrado = true;
                }

                continue;
            }

            if (! isset($cols[$idxFecha]) || trim($cols[$idxFecha]) === '') {
                continue;
            }

            try {
                $fecha = $this->normalizarFecha(trim($cols[$idxFecha]));
            } catch (\Exception) {
                continue; // saltar línea no parseable
            }

            $cargo = $idxCargo >= 0 ? $this->normalizarMonto($cols[$idxCargo] ?? '') : 0;
            $abono = $idxAbono >= 0 ? $this->normalizarMonto($cols[$idxAbono] ?? '') : 0;

            if ($cargo == 0 && $abono == 0) {
                continue;
            }

            $movimientos[] = [
                'fecha' => $fecha,
                'descripcion' => trim($cols[$idxDesc] ?? ''),
                'monto' => $cargo > 0 ? $cargo : $abono,
                'tipo' => $cargo > 0 ? 'egreso' : 'ingreso',
            ];
        }

        if (! $headerEncontrado) {
            throw new RuntimeException('No se encontró encabezado FECHA en el CSV de BBVA.');
        }

        return $movimientos;
    }

    private function buscar(array $cols, array $candidatos): int
    {
        foreach ($candidatos as $cand) {
            $idx = array_search($cand, $cols, true);
            if ($idx !== false) {
                return $idx;
            }
        }

        return -1;
    }

    private function normalizarFecha(string $raw): string
    {
        if (preg_match('/^\d{2}\/\d{2}\/\d{4}$/', $raw)) {
            return Carbon::createFromFormat('d/m/Y', $raw)->toDateString();
        }
        if (preg_match('/^\d{4}-\d{2}-\d{2}$/', $raw)) {
            return $raw;
        }

        return Carbon::parse($raw)->toDateString();
    }

    private function normalizarMonto(string $raw): float
    {
        $clean = trim($raw);
        if ($clean === '' || $clean === '-' || $clean === '0' || $clean === '0.00') {
            return 0.0;
        }
        // Quitar comas de millares, signo $, paréntesis (negativos)
        $clean = str_replace(['$', ',', ' '], '', $clean);
        $clean = trim($clean, '"\'');

        return (float) $clean;
    }
}
