<?php

namespace App\Services\Finanzas\Parsers;

interface EstadoCuentaParser
{
    /**
     * Lee un archivo y devuelve un array de movimientos normalizados.
     *
     * @return array<int, array{fecha: string, descripcion: string, monto: float, tipo: 'ingreso'|'egreso'}>
     */
    public function parse(string $filePath): array;
}
