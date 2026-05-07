<?php

namespace App\Services\Proyectos;

use InvalidArgumentException;

/**
 * Tech Reference: 250121-0-IGA-HTP x _HT 30"x 10" Texmelucan
 *
 * Regex (sección 5.12 del plan):
 * ^\d{6}-\d{1,2}-[A-Z]{3}-[A-Z&]{3,4}\s+(\d+x\d+|x)\s+_.{1,80}$
 */
class TechReferenceService
{
    public const REGEX = '/^\d{6}-\d{1,2}-[A-Z]{3}-[A-Z&]{3,4}\s+(\d+x\d+|x)\s+_.{1,80}$/u';

    public function validar(string $techReference): bool
    {
        return (bool) preg_match(self::REGEX, $techReference);
    }

    public function exigir(string $techReference): void
    {
        if (! $this->validar($techReference)) {
            throw new InvalidArgumentException("Tech Reference inválido: {$techReference}");
        }
    }

    /**
     * Construye un Tech Reference a partir de los campos.
     *
     * @param  string  $fecha  YYMMDD
     * @param  int  $consecutivo  consecutivo del día (0..99)
     * @param  string  $aliasCliente  3 letras
     * @param  string  $sublineaCodigo  3-4 letras (HTP, LSP, VLV, SOL, SG, SG&S...)
     * @param  string  $dimensiones  ej. "30x10" o "x" si no aplica
     * @param  string  $descripcion  texto libre 1..80
     */
    public function construir(string $fecha, int $consecutivo, string $aliasCliente, string $sublineaCodigo, string $dimensiones, string $descripcion): string
    {
        $alias = strtoupper($aliasCliente);
        $sub = strtoupper($sublineaCodigo);
        $tr = sprintf('%s-%d-%s-%s %s _%s', $fecha, $consecutivo, $alias, $sub, $dimensiones, $descripcion);
        $this->exigir($tr);

        return $tr;
    }
}
