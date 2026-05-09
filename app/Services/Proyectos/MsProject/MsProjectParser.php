<?php

namespace App\Services\Proyectos\MsProject;

interface MsProjectParser
{
    /**
     * Parsea un archivo y devuelve un array de actividades normalizadas.
     *
     * @return array<int, array{
     *   codigo: ?string,
     *   nombre: string,
     *   fecha_inicio_planeada: ?string,
     *   fecha_fin_planeada: ?string,
     *   porcentaje_avance: float,
     *   predecesoras: ?array<int, string>,
     *   nivel: int
     * }>
     */
    public function parse(string $filePath): array;
}
