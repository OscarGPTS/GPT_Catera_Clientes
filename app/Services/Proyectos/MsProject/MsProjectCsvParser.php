<?php

namespace App\Services\Proyectos\MsProject;

use Illuminate\Support\Carbon;
use RuntimeException;

/**
 * Parser para CSV exportado de MS Project (vista de Tareas → File → Export).
 *
 * Columnas esperadas (cualquier orden):
 *   ID, Name (o Nombre), Start (o Comienzo), Finish (o Fin), % Complete (o % Completado),
 *   Predecessors (o Predecesoras), Outline Level (o Nivel)
 *
 * Tolerante a separador `,` o `;` y a encabezados en español/inglés.
 */
class MsProjectCsvParser implements MsProjectParser
{
    private const SINONIMOS = [
        'id' => ['ID', 'NRO', 'NO'],
        'nombre' => ['NAME', 'NOMBRE', 'TASK NAME', 'NOMBRE DE TAREA'],
        'inicio' => ['START', 'COMIENZO', 'INICIO'],
        'fin' => ['FINISH', 'FIN', 'TERMINO', 'TÉRMINO'],
        'avance' => ['% COMPLETE', '%COMPLETE', '% COMPLETADO', '%COMPLETADO', 'AVANCE'],
        'predecesoras' => ['PREDECESSORS', 'PREDECESORAS', 'PREDECESSOR', 'PREDECESSORS NAMES'],
        'nivel' => ['OUTLINE LEVEL', 'NIVEL', 'NIVEL DE ESQUEMA'],
    ];

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

        $actividades = [];
        $headerEncontrado = false;
        $idx = [];

        foreach ($lineas as $linea) {
            if (trim($linea) === '') {
                continue;
            }

            $cols = str_getcsv($linea, $separador);

            if (! $headerEncontrado) {
                $upper = array_map(fn ($c) => mb_strtoupper(trim($c)), $cols);
                $idx = $this->mapearColumnas($upper);

                if ($idx['nombre'] < 0) {
                    continue; // header no encontrado todavía
                }

                $headerEncontrado = true;

                continue;
            }

            $nombre = trim($cols[$idx['nombre']] ?? '');
            if ($nombre === '') {
                continue;
            }

            $actividades[] = [
                'codigo' => $idx['id'] >= 0 && trim($cols[$idx['id']] ?? '') !== '' ? trim($cols[$idx['id']]) : null,
                'nombre' => $nombre,
                'fecha_inicio_planeada' => $idx['inicio'] >= 0 ? $this->normalizarFecha($cols[$idx['inicio']] ?? '') : null,
                'fecha_fin_planeada' => $idx['fin'] >= 0 ? $this->normalizarFecha($cols[$idx['fin']] ?? '') : null,
                'porcentaje_avance' => $idx['avance'] >= 0 ? $this->normalizarAvance($cols[$idx['avance']] ?? '') : 0,
                'predecesoras' => $idx['predecesoras'] >= 0 ? $this->normalizarPredecesoras($cols[$idx['predecesoras']] ?? '') : null,
                'nivel' => $idx['nivel'] >= 0 ? (int) ($cols[$idx['nivel']] ?? 1) : 1,
            ];
        }

        if (! $headerEncontrado) {
            throw new RuntimeException('No se encontró encabezado válido (Name/Nombre) en el CSV.');
        }

        if (empty($actividades)) {
            throw new RuntimeException('El CSV no contiene filas de actividades.');
        }

        return $actividades;
    }

    /** @return array<string, int> */
    private function mapearColumnas(array $upperCols): array
    {
        $out = ['id' => -1, 'nombre' => -1, 'inicio' => -1, 'fin' => -1, 'avance' => -1, 'predecesoras' => -1, 'nivel' => -1];

        foreach (self::SINONIMOS as $key => $candidatos) {
            foreach ($candidatos as $cand) {
                $i = array_search($cand, $upperCols, true);
                if ($i !== false) {
                    $out[$key] = $i;
                    break;
                }
            }
        }

        return $out;
    }

    private function normalizarFecha(string $raw): ?string
    {
        $clean = trim($raw);
        if ($clean === '') {
            return null;
        }

        try {
            return Carbon::parse($clean)->toDateString();
        } catch (\Exception) {
            return null;
        }
    }

    private function normalizarAvance(string $raw): float
    {
        $clean = trim($raw);
        $clean = str_replace(['%', ' '], '', $clean);

        return (float) $clean;
    }

    /** @return array<int, string>|null */
    private function normalizarPredecesoras(string $raw): ?array
    {
        $clean = trim($raw);
        if ($clean === '') {
            return null;
        }

        $ids = [];
        foreach (preg_split('/[,;]+/', $clean) as $token) {
            $token = trim($token);
            if (preg_match('/^(\d+)/', $token, $m)) {
                $ids[] = $m[1];
            }
        }

        return $ids ?: null;
    }
}
